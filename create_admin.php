<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<pre>';

$email    = 'adittyaamin@gmail.com';
$name     = 'Admin';
$password = 'Admin@99accs2026!';

try {
    if (User::where('email', $email)->exists()) {
        echo "User already exists: $email\n";
        echo "Updating password...\n";
        User::where('email', $email)->update([
            'password' => Hash::make($password),
        ]);
        echo "Password updated.\n";
    } else {
        User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);
        echo "Admin user created!\n";
    }

    echo "\n--- Login credentials ---\n";
    echo "URL:      https://99acss.themegenix.net/admin\n";
    echo "Email:    $email\n";
    echo "Password: $password\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo '</pre>';
