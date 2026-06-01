<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(180);

echo '<pre>';
echo "\n>> db:seed --force\n";
try {
    $kernel->call('db:seed --force');
    echo $kernel->output();
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
echo '</pre>';
