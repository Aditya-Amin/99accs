<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentGateway;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            ['name' => 'Stripe', 'slug' => 'stripe'],
            ['name' => 'PayPal', 'slug' => 'paypal'],
            ['name' => 'Razorpay', 'slug' => 'razorpay'],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::firstOrCreate(['slug' => $gateway['slug']], $gateway);
        }
    }
}
