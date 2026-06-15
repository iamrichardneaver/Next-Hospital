<?php
/**
 * Reverse-engineer live MySQL schema into Laravel migrations + seeders.
 * READ-ONLY on source database. Run: php scripts/generate_schema_from_db.php
 */

declare(strict_types=1);

$basePath = dirname(__DIR__);
$migrationsPath = $basePath . '/database/migrations';
$seedersPath = $basePath . '/database/seeders/Generated';

$config = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'nexthospital',
    'username' => 'root',
    'password' => '',
];

$skipTables = ['migrations'];
$chunkSize = 100;
$largeTableThreshold = 500;
$migrationDatePrefix = '2024_01_01';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']),
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

echo "Connected to {$config['database']}\n";

$tables = fetchTables($pdo, $config['database'], $skipTables);
echo 'Found ' . count($tables) . " tables\n";

$columns = fetchColumns($pdo, $config['database'], $tables);
$indexes = fetchIndexes($pdo, $config['database'], $tables);
$foreignKeys = fetchForeignKeys($pdo, $config['database'], $tables);

[$sortedTables, $deferredForeignKeys, $inlineForeignKeys] = topologicalSort($tables, $foreignKeys);
if (!empty($deferredForeignKeys)) {
    echo 'Deferred ' . count($deferredForeignKeys) . " FK constraints (dependency order)\n";
}

// --- Clean output directories ---
foreach (glob($migrationsPath . '/*.php') ?: [] as $file) {
    unlink($file);
}
if (is_dir($seedersPath)) {
    foreach (glob($seedersPath . '/*.php') ?: [] as $file) {
        unlink($file);
    }
} else {
    mkdir($seedersPath, 0755, true);
}

// --- Generate migrations ---
$seq = 1;
$tableMigrationMap = [];
foreach ($sortedTables as $table) {
    $seqStr = str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    $filename = "{$migrationDatePrefix}_{$seqStr}_create_{$table}_table.php";
    $content = generateMigration(
        $table,
        $columns[$table] ?? [],
        $indexes[$table] ?? [],
        $inlineForeignKeys[$table] ?? []
    );
    file_put_contents("$migrationsPath/$filename", $content);
    $tableMigrationMap[$table] = $filename;
    $seq++;
}

// Deferred FK migration if needed
if (!empty($deferredForeignKeys)) {
    $seqStr = str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    $filename = "{$migrationDatePrefix}_{$seqStr}_add_deferred_foreign_keys.php";
    file_put_contents("$migrationsPath/$filename", generateDeferredFkMigration($deferredForeignKeys));
    $seq++;
}

echo "Generated " . ($seq - 1) . " migration files\n";

// --- Generate seeders ---
$seedersWithData = [];
$seederOrder = [];
foreach ($sortedTables as $table) {
    $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    if ($count === 0) {
        continue;
    }
    $className = studly($table) . 'Seeder';
    $rows = fetchAllRows($pdo, $table);
    $content = generateSeeder($className, $table, $rows, $chunkSize, $largeTableThreshold);
    file_put_contents("$seedersPath/{$className}.php", $content);
    $seedersWithData[] = ['table' => $table, 'class' => $className, 'count' => $count];
    $seederOrder[] = $className;
}

echo 'Generated ' . count($seedersWithData) . " seeder files\n";

// --- DatabaseSeeder ---
file_put_contents(
    $basePath . '/database/seeders/DatabaseSeeder.php',
    generateDatabaseSeeder($seederOrder)
);

// --- Report ---
$reportPath = $basePath . '/database/SCHEMA_REGENERATION_REPORT.md';
file_put_contents($reportPath, generateReport($sortedTables, $tableMigrationMap, $seedersWithData, $deferredForeignKeys, $skipTables));

echo "Report written to database/SCHEMA_REGENERATION_REPORT.md\n";
echo "Done.\n";

// ===================== FUNCTIONS =====================

function fetchTables(PDO $pdo, string $schema, array $skip): array
{
    $stmt = $pdo->prepare(
        "SELECT TABLE_NAME FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
         ORDER BY TABLE_NAME"
    );
    $stmt->execute([$schema]);
    $tables = array_column($stmt->fetchAll(), 'TABLE_NAME');
    return array_values(array_diff($tables, $skip));
}

function fetchColumns(PDO $pdo, string $schema, array $tables): array
{
    $result = [];
    $placeholders = implode(',', array_fill(0, count($tables), '?'));
    $stmt = $pdo->prepare(
        "SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE, COLUMN_TYPE,
                IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLUMN_KEY, CHARACTER_MAXIMUM_LENGTH,
                NUMERIC_PRECISION, NUMERIC_SCALE, COLLATION_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ($placeholders)
         ORDER BY TABLE_NAME, ORDINAL_POSITION"
    );
    $stmt->execute(array_merge([$schema], $tables));
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['TABLE_NAME']][] = $row;
    }
    return $result;
}

function fetchIndexes(PDO $pdo, string $schema, array $tables): array
{
    $result = [];
    $placeholders = implode(',', array_fill(0, count($tables), '?'));
    $stmt = $pdo->prepare(
        "SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, INDEX_TYPE
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ($placeholders)
         ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX"
    );
    $stmt->execute(array_merge([$schema], $tables));
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['TABLE_NAME']][$row['INDEX_NAME']]['non_unique'] = (int) $row['NON_UNIQUE'];
        $result[$row['TABLE_NAME']][$row['INDEX_NAME']]['type'] = $row['INDEX_TYPE'];
        $result[$row['TABLE_NAME']][$row['INDEX_NAME']]['columns'][] = $row['COLUMN_NAME'];
    }
    return $result;
}

function fetchForeignKeys(PDO $pdo, string $schema, array $tables): array
{
    $result = [];
    $placeholders = implode(',', array_fill(0, count($tables), '?'));
    $stmt = $pdo->prepare(
        "SELECT kcu.TABLE_NAME, kcu.CONSTRAINT_NAME, kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME,
                kcu.ORDINAL_POSITION,
                rc.UPDATE_RULE, rc.DELETE_RULE
         FROM information_schema.KEY_COLUMN_USAGE kcu
         JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
           ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
          AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
          AND rc.TABLE_NAME = kcu.TABLE_NAME
         WHERE kcu.CONSTRAINT_SCHEMA = ?
           AND kcu.TABLE_NAME IN ($placeholders)
           AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
         ORDER BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION"
    );
    $stmt->execute(array_merge([$schema], $tables));
    foreach ($stmt->fetchAll() as $row) {
        $key = $row['CONSTRAINT_NAME'];
        if (!isset($result[$row['TABLE_NAME']][$key])) {
            $result[$row['TABLE_NAME']][$key] = [
                'name' => $row['CONSTRAINT_NAME'],
                'columns' => [],
                'referenced_table' => $row['REFERENCED_TABLE_NAME'],
                'referenced_columns' => [],
                'on_update' => strtolower($row['UPDATE_RULE']),
                'on_delete' => strtolower($row['DELETE_RULE']),
            ];
        }
        $result[$row['TABLE_NAME']][$key]['columns'][] = $row['COLUMN_NAME'];
        $result[$row['TABLE_NAME']][$key]['referenced_columns'][] = $row['REFERENCED_COLUMN_NAME'];
    }
    return $result;
}

function topologicalSort(array $tables, array $allForeignKeys): array
{
    $deps = [];
    foreach ($tables as $t) {
        $deps[$t] = [];
    }
    $fkList = [];
    foreach ($allForeignKeys as $table => $fks) {
        foreach ($fks as $fk) {
            $ref = $fk['referenced_table'];
            if ($ref === $table) {
                continue;
            }
            if (in_array($ref, $tables, true)) {
                $deps[$table][$ref] = true;
            }
            $fkList[] = array_merge(['table' => $table], $fk);
        }
    }

    $sorted = [];
    $remaining = array_fill_keys($tables, true);
    while (count($sorted) < count($tables)) {
        $ready = [];
        foreach (array_keys($remaining) as $t) {
            $tableDeps = array_keys($deps[$t] ?? []);
            $unmet = array_filter($tableDeps, fn ($d) => isset($remaining[$d]));
            if (empty($unmet)) {
                $ready[] = $t;
            }
        }
        if (empty($ready)) {
            $ready = [array_key_first($remaining)];
        }
        sort($ready);
        foreach ($ready as $t) {
            $sorted[] = $t;
            unset($remaining[$t]);
        }
    }

    $position = array_flip($sorted);
    $inline = [];
    $deferred = [];
    foreach ($fkList as $fk) {
        $refPos = $position[$fk['referenced_table']] ?? -1;
        $tablePos = $position[$fk['table']] ?? -1;
        if ($refPos > $tablePos) {
            $deferred[] = $fk;
        } else {
            $inline[$fk['table']][$fk['name']] = $fk;
        }
    }

    return [$sorted, $deferred, $inline];
}

function generateMigration(string $table, array $columns, array $indexes, array $foreignKeys): string
{
    $lines = [];
    $pkColumns = [];
    $autoIncrementCol = null;

    foreach ($columns as $col) {
        if (str_contains($col['EXTRA'], 'auto_increment')) {
            $autoIncrementCol = $col['COLUMN_NAME'];
        }
        if ($col['COLUMN_KEY'] === 'PRI') {
            $pkColumns[] = $col['COLUMN_NAME'];
        }
    }

    $isSimpleId = count($pkColumns) === 1
        && $pkColumns[0] === 'id'
        && $autoIncrementCol === 'id'
        && str_contains($columns[0]['COLUMN_TYPE'] ?? '', 'bigint');

    $usedId = false;
    if ($isSimpleId) {
        $lines[] = '            $table->id();';
        $usedId = true;
    }

    foreach ($columns as $col) {
        $name = $col['COLUMN_NAME'];
        if ($usedId && $name === 'id') {
            continue;
        }
        $lines[] = '            ' . columnBlueprint($col, $pkColumns, $usedId);
    }

    // Primary key (composite)
    if (count($pkColumns) > 1) {
        $pk = implode("', '", $pkColumns);
        $lines[] = "            \$table->primary(['{$pk}']);";
    } elseif (count($pkColumns) === 1 && !$usedId) {
        // Single non-standard PK already defined on column
    }

    $fkIndexNames = array_map(fn ($fk) => $fk['name'], $foreignKeys);

    foreach ($indexes as $indexName => $index) {
        if ($indexName === 'PRIMARY' || in_array($indexName, $fkIndexNames, true)) {
            continue;
        }
        $cols = $index['columns'];
        $colsStr = "['" . implode("', '", $cols) . "']";
        $defaultName = $table . '_' . implode('_', $cols) . ((int) $index['non_unique'] === 0 ? '_unique' : '_index');
        $idxArg = $indexName !== $defaultName ? ", '{$indexName}'" : '';
        if ((int) $index['non_unique'] === 0) {
            $lines[] = "            \$table->unique({$colsStr}{$idxArg});";
        } else {
            $lines[] = "            \$table->index({$colsStr}{$idxArg});";
        }
    }

    // Foreign keys
    foreach ($foreignKeys as $fk) {
        $lines[] = foreignKeyBlueprint($fk);
    }

    $body = implode("\n", $lines);

    return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
{$body}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};

PHP;
}

function columnBlueprint(array $col, array $pkColumns, bool $usedLaravelId): string
{
    $name = $col['COLUMN_NAME'];
    $type = $col['COLUMN_TYPE'];
    $nullable = $col['IS_NULLABLE'] === 'YES';
    $default = $col['COLUMN_DEFAULT'];
    $extra = $col['EXTRA'];

    $chain = mapColumnType($name, $type, $col);

    if ($nullable) {
        $chain .= '->nullable()';
    }

    if ($default !== null) {
        $defaultClause = defaultBlueprint($default, $type, $nullable);
        if ($defaultClause !== '') {
            $chain .= $defaultClause;
        }
    }

    if (str_contains($extra, 'auto_increment') && !($usedLaravelId && $name === 'id')) {
        $chain .= '->autoIncrement()';
    }

    if (count($pkColumns) === 1 && $pkColumns[0] === $name && !($usedLaravelId && $name === 'id')) {
        $chain .= '->primary()';
    }

    return $chain . ';';
}

function mapColumnType(string $name, string $columnType, array $col): string
{
    $lower = strtolower($columnType);

    if (str_starts_with($lower, 'enum(')) {
        $values = parseEnumValues($columnType);
        $vals = implode("', '", array_map('addslashes', $values));
        return "\$table->enum('{$name}', ['{$vals}'])";
    }

    if (preg_match('/^varchar\((\d+)\)/', $lower, $m)) {
        return "\$table->string('{$name}', {$m[1]})";
    }
    if (preg_match('/^char\((\d+)\)/', $lower, $m)) {
        return "\$table->char('{$name}', {$m[1]})";
    }
    if ($lower === 'varchar(255)' || ($col['DATA_TYPE'] === 'varchar' && !preg_match('/varchar/', $lower))) {
        return "\$table->string('{$name}')";
    }

    if (preg_match('/^bigint\(\d+\)\s+unsigned/', $lower)) {
        return "\$table->unsignedBigInteger('{$name}')";
    }
    if (preg_match('/^int\(\d+\)\s+unsigned/', $lower)) {
        return "\$table->unsignedInteger('{$name}')";
    }
    if (preg_match('/^smallint\(\d+\)\s+unsigned/', $lower)) {
        return "\$table->unsignedSmallInteger('{$name}')";
    }
    if (preg_match('/^tinyint\(\d+\)\s+unsigned/', $lower)) {
        return "\$table->unsignedTinyInteger('{$name}')";
    }
    if (preg_match('/^bigint/', $lower)) {
        return "\$table->bigInteger('{$name}')";
    }
    if (preg_match('/^int/', $lower)) {
        return "\$table->integer('{$name}')";
    }
    if (preg_match('/^smallint/', $lower)) {
        return "\$table->smallInteger('{$name}')";
    }
    if (preg_match('/^tinyint\(1\)/', $lower)) {
        return "\$table->boolean('{$name}')";
    }
    if (preg_match('/^tinyint/', $lower)) {
        return "\$table->tinyInteger('{$name}')";
    }

    if (preg_match('/^decimal\((\d+),(\d+)\)/', $lower, $m)) {
        return "\$table->decimal('{$name}', {$m[1]}, {$m[2]})";
    }
    if (str_starts_with($lower, 'double')) {
        return "\$table->double('{$name}')";
    }
    if (str_starts_with($lower, 'float')) {
        return "\$table->float('{$name}')";
    }

    if ($lower === 'text') {
        return "\$table->text('{$name}')";
    }
    if ($lower === 'mediumtext') {
        return "\$table->mediumText('{$name}')";
    }
    if ($lower === 'longtext') {
        return "\$table->longText('{$name}')";
    }
    if ($lower === 'json') {
        return "\$table->json('{$name}')";
    }
    if (str_starts_with($lower, 'blob') || str_starts_with($lower, 'mediumblob') || str_starts_with($lower, 'longblob')) {
        return "\$table->binary('{$name}')";
    }

    if ($lower === 'date') {
        return "\$table->date('{$name}')";
    }
    if ($lower === 'datetime') {
        return "\$table->dateTime('{$name}')";
    }
    if ($lower === 'timestamp') {
        return "\$table->timestamp('{$name}')";
    }
    if ($lower === 'time') {
        return "\$table->time('{$name}')";
    }
    if ($lower === 'year') {
        return "\$table->year('{$name}')";
    }

    // Fallback
    return "\$table->string('{$name}')";
}

function parseEnumValues(string $columnType): array
{
    preg_match("/^enum\((.*)\)$/i", $columnType, $m);
    preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $m[1], $matches);
    return $matches[1] ?? [];
}

function defaultBlueprint($default, string $columnType, bool $nullable = false): string
{
    $normalized = normalizeMySqlDefault($default);

    if ($normalized === null) {
        return '';
    }

    if ($normalized === 'CURRENT_TIMESTAMP' || $normalized === 'current_timestamp()') {
        return '->useCurrent()';
    }

    if (is_numeric($normalized) && !str_contains(strtolower($columnType), 'char') && !str_contains(strtolower($columnType), 'text') && !str_contains(strtolower($columnType), 'enum')) {
        return "->default({$normalized})";
    }

    $escaped = addslashes((string) $normalized);

    return "->default('{$escaped}')";
}

function normalizeMySqlDefault($default): ?string
{
    if ($default === null) {
        return null;
    }

    $value = trim((string) $default);

    if ($value === '' || strtoupper($value) === 'NULL') {
        return null;
    }

    // MySQL information_schema wraps string defaults in single quotes
    if (preg_match("/^'(.*)'$/s", $value, $m)) {
        return str_replace("\\'", "'", $m[1]);
    }

    return $value;
}

function foreignKeyBlueprint(array $fk): string
{
    $cols = "['" . implode("', '", $fk['columns']) . "']";
    $refCols = "['" . implode("', '", $fk['referenced_columns']) . "']";
    $onDelete = mapReferentialAction($fk['on_delete']);
    $onUpdate = mapReferentialAction($fk['on_update']);
    $name = $fk['name'];

    if (count($fk['columns']) === 1) {
        $col = $fk['columns'][0];
        return "\$table->foreign('{$col}', '{$name}')->references('{$fk['referenced_columns'][0]}')->on('{$fk['referenced_table']}')->onDelete('{$onDelete}')->onUpdate('{$onUpdate}');";
    }

    return "\$table->foreign({$cols}, '{$name}')->references({$refCols})->on('{$fk['referenced_table']}')->onDelete('{$onDelete}')->onUpdate('{$onUpdate}');";
}

function mapReferentialAction(string $rule): string
{
    return match ($rule) {
        'no action' => 'no action',
        'restrict' => 'restrict',
        'cascade' => 'cascade',
        'set null' => 'set null',
        default => $rule,
    };
}

function generateDeferredFkMigration(array $deferred): string
{
    $upLines = [];
    $downLines = [];
    foreach ($deferred as $fk) {
        $table = $fk['table'];
        $upLines[] = "        Schema::table('{$table}', function (Blueprint \$table) {";
        $upLines[] = '            ' . foreignKeyBlueprint($fk);
        $upLines[] = '        });';
        $downLines[] = "        Schema::table('{$table}', function (Blueprint \$table) {";
        $downLines[] = "            \$table->dropForeign('{$fk['name']}');";
        $downLines[] = '        });';
    }

    $up = implode("\n", $upLines);
    $down = implode("\n", array_reverse($downLines));

    return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
{$up}
    }

    public function down(): void
    {
{$down}
    }
};

PHP;
}

function fetchAllRows(PDO $pdo, string $table): array
{
    return $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
}

function generateSeeder(string $className, string $table, array $rows, int $chunkSize, int $largeThreshold): string
{
    $chunks = array_chunk($rows, $chunkSize);
    $insertBlocks = [];

    foreach ($chunks as $i => $chunk) {
        $arrayExport = exportPhpArray($chunk, 2);
        $insertBlocks[] = "        DB::table('{$table}')->insertOrIgnore({$arrayExport});";
    }

    $inserts = implode("\n\n", $insertBlocks);
    $rowCount = count($rows);

    return <<<PHP
<?php

namespace Database\Seeders\Generated;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class {$className} extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

{$inserts}

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

PHP;
}

function exportPhpArray(array $rows, int $indent): string
{
    if (empty($rows)) {
        return '[]';
    }
    $pad = str_repeat(' ', $indent);
    $items = [];
    foreach ($rows as $row) {
        $pairs = [];
        foreach ($row as $k => $v) {
            $pairs[] = var_export($k, true) . ' => ' . exportValue($v);
        }
        $items[] = $pad . '    [' . implode(', ', $pairs) . ']';
    }
    return "[\n" . implode(",\n", $items) . ",\n{$pad}]";
}

function exportValue($v): string
{
    if ($v === null) {
        return 'null';
    }
    if (is_int($v) || is_float($v)) {
        return (string) $v;
    }
    if (is_bool($v)) {
        return $v ? 'true' : 'false';
    }
    return var_export((string) $v, true);
}

function studly(string $value): string
{
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $value)));
}

function generateDatabaseSeeder(array $seederClasses): string
{
    $date = date('Y-m-d H:i:s');
    $calls = [];
    foreach ($seederClasses as $class) {
        $calls[] = "            \\Database\\Seeders\\Generated\\{$class}::class,";
    }
    $callBlock = implode("\n", $calls);

    return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Auto-generated from live nexthospital database schema.
 * Generated: {$date}
 *
 * Test on a SEPARATE database only (e.g. nexthospital_schema_test):
 *   PERMISSIONS_AUTO_SYNC=false DB_DATABASE=nexthospital_schema_test php artisan migrate --seed
 *
 * Set PERMISSIONS_AUTO_SYNC=false — otherwise boot-time permission sync overwrites
 * Generated permission rows before PermissionsSeeder runs (ID mismatch).
 *
 * NEVER run migrate:fresh on the operational nexthospital database.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        \$this->call([
{$callBlock}
        ]);
    }
}

PHP;
}

function generateReport(array $tables, array $migrationMap, array $seeders, array $deferred, array $skipped): string
{
    $date = date('Y-m-d H:i:s');
    $lines = [];
    $lines[] = "# Schema Regeneration Report";
    $lines[] = '';
    $lines[] = "Generated: {$date}";
    $lines[] = "Source: nexthospital @ 127.0.0.1 (READ-ONLY introspection)";
    $lines[] = '';
    $lines[] = '## Summary';
    $lines[] = '- Migration files: ' . count($migrationMap);
    $lines[] = '- Seeder files (tables with data): ' . count($seeders);
    $lines[] = '- Skipped tables: ' . implode(', ', $skipped);
    $lines[] = '- Deferred FK constraints: ' . count($deferred);
    $lines[] = '';
    $lines[] = '## Migrations (run order)';
    $lines[] = '';
    $i = 1;
    foreach ($tables as $table) {
        $file = $migrationMap[$table] ?? 'N/A';
        $lines[] = "{$i}. `{$file}` — `{$table}`";
        $i++;
    }
    if (!empty($deferred)) {
        $lines[] = "{$i}. deferred FK migration";
    }
    $lines[] = '';
    $lines[] = '## Seeders (run order)';
    $lines[] = '';
    $i = 1;
    foreach ($seeders as $s) {
        $lines[] = "{$i}. `Generated/{$s['class']}.php` — `{$s['table']}` ({$s['count']} rows)";
        $i++;
    }
    $lines[] = '';
    $lines[] = '## Tables with zero rows (no seeder generated)';
    $lines[] = '';
    $seededTables = array_column($seeders, 'table');
    foreach ($tables as $table) {
        if (!in_array($table, $seededTables, true)) {
            $lines[] = "- `{$table}`";
        }
    }
    $lines[] = '';
    $lines[] = '## Test commands (SEPARATE database only)';
    $lines[] = '';
    $lines[] = '```bash';
    $lines[] = '# Create test database';
    $lines[] = 'mysql -u root -e "CREATE DATABASE IF NOT EXISTS nexthospital_schema_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"';
    $lines[] = '';
    $lines[] = '# Run migrations + seeders against test DB';
    $lines[] = '# IMPORTANT: disable permission auto-sync so Generated seeders preserve live IDs';
    $lines[] = 'cd backend';
    $lines[] = 'set DB_DATABASE=nexthospital_schema_test';
    $lines[] = 'set PERMISSIONS_AUTO_SYNC=false';
    $lines[] = 'php artisan migrate --seed';
    $lines[] = '```';
    $lines[] = '';
    $lines[] = '## Ambiguous items for manual review';
    $lines[] = '';
    if (!empty($deferred)) {
        $lines[] = '- Circular FK dependencies deferred to final migration:';
        foreach ($deferred as $fk) {
            $lines[] = "  - `{$fk['table']}`.{$fk['name']} -> `{$fk['referenced_table']}`";
        }
    } else {
        $lines[] = '- None identified.';
    }
    $lines[] = '';
    $lines[] = '## Notes';
    $lines[] = '- Credentials used: root (from backend/.env), not nexthospital user';
    $lines[] = '- Laravel `migrations` table excluded (managed by framework)';
    $lines[] = '- Old migration files deleted; old seeders remain on disk but are not called by DatabaseSeeder';

    return implode("\n", $lines);
}
