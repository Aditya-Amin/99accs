<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo '<pre>';
foreach (['cache:clear', 'config:clear', 'route:clear', 'view:clear'] as $cmd) {
    echo "\n>> $cmd\n";
    $kernel->call($cmd);
    echo $kernel->output();
}
echo '</pre>';
