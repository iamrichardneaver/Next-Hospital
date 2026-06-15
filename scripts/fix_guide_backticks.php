<?php
$path = dirname(__DIR__) . '/../documentation/MOBILE_API_DEVELOPER_GUIDE.md';
$c = file_get_contents($path);
$c = str_replace('\\`', '`', $c);
file_put_contents($path, $c);
echo "Fixed backticks in MOBILE_API_DEVELOPER_GUIDE.md\n";
