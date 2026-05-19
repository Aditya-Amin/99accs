<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$app->make('config')->set('app.debug', true);
$request = Illuminate\Http\Request::create('/admin');
$response = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle($request);
echo $response->getContent();
