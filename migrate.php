<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<pre>';

echo "\n>> key:generate --force\n";
$kernel->call('key:generate --force');
echo $kernel->output();

echo "\n>> migrate --force\n";
try {
    $kernel->call('migrate --force');
    echo $kernel->output();
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo '</pre>';
