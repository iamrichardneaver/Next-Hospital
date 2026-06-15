<?php
$json = file_get_contents(__DIR__ . '/../routes_api.json');
$routes = json_decode($json, true);
if (!$routes) {
    fwrite(STDERR, "Failed to parse routes_api.json\n");
    exit(1);
}

$groups = [];
$lines = [];
foreach ($routes as $r) {
    $uri = $r['uri'] ?? '';
    if (!str_starts_with($uri, 'api/')) {
        continue;
    }
    $path = substr($uri, 4);
    $seg = explode('/', $path)[0] ?? 'root';
    $groups[$seg] = ($groups[$seg] ?? 0) + 1;
    $method = is_array($r['method'] ?? null) ? implode('|', $r['method']) : ($r['method'] ?? 'GET');
    $middleware = implode(', ', $r['middleware'] ?? []);
    $lines[] = [
        'method' => $method,
        'path' => $path,
        'group' => $seg,
        'middleware' => $middleware,
        'action' => $r['action'] ?? '',
    ];
}

ksort($groups);
echo "=== Route groups ===\n";
foreach ($groups as $k => $v) {
    echo "$k: $v\n";
}
echo 'TOTAL: ' . count($lines) . "\n";

file_put_contents(__DIR__ . '/../routes_api_grouped.json', json_encode($lines, JSON_PRETTY_PRINT));
echo "Written routes_api_grouped.json\n";
