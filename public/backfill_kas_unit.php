<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$envFile = '/home/aljawahi/public_html/.env';
$lines = file($envFile);
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;
    if (strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}

$dsn = "host={$env['database.default.hostname']} port={$env['database.default.port']} dbname={$env['database.default.database']} user={$env['database.default.username']} password={$env['database.default.password']}";
$conn = pg_connect($dsn);
if (!$conn) die("CONNECT ERROR: " . pg_last_error());

$t = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name='tb_users' ORDER BY ordinal_position");
echo "=== tb_users columns ===\n";
while ($c = pg_fetch_assoc($t)) echo "  {$c['column_name']}\n";

$r = pg_query($conn, "SELECT * FROM tb_users LIMIT 5");
if (!$r) die("QUERY ERROR: " . pg_last_error($conn));

echo "=== USERS ===\n";
while ($row = pg_fetch_assoc($r)) echo json_encode($row) . "\n";
pg_close($conn);
echo "\nDONE\n";
