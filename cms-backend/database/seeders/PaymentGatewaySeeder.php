<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Seed the supported payment gateways as disabled rows so an admin can
     * fill in the credentials from the Filament dashboard and flip them on.
     * No real secrets ever live in code or .env.
     */
    public function run(): void
    {
        $gateways = [
            [
                'name'         => 'Stripe',
                'slug'         => 'stripe',
                'description'  => 'Credit / debit card payments via Stripe PaymentIntents.',
                'credentials'  => ['secret_key' => null, 'publishable_key' => null, 'webhook_secret' => null],
                'config'       => [],
                'is_active'    => false,
                'is_test_mode' => true,
                'sort_order'   => 10,
            ],
            [
                'name'         => 'Cryptomus',
                'slug'         => 'crypto',
                'description'  => 'Crypto payments via Cryptomus (BTC, USDT, etc.).',
                'credentials'  => ['merchant_id' => null, 'payment_key' => null],
                'config'       => ['callback_url' => null],
                'is_active'    => false,
                'is_test_mode' => true,
                'sort_order'   => 20,
            ],
        ];

        foreach ($gateways as $row) {
            // Use updateOrCreate so re-running the seeder doesn't wipe credentials
            // an admin has already entered through the dashboard.
            PaymentGateway::firstOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
