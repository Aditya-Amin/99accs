<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/admin');
$response = $kernel->handle($request);
echo get_class($response);
if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 302) {
    echo "\nStatus code: " . $response->getStatusCode();
    echo "\n" . substr($response->getContent(), 0, 500);
}
