<?php

namespace Core\Plugins\PayPal\Providers;

use Illuminate\Support\ServiceProvider;
use Core\Facades\Payment;
use Core\Plugins\PayPal\Gateways\PayPalGateway;

class PayPalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind PayPal SDK
    }

    public function boot(): void
    {
        Payment::register(new PayPalGateway());
    }
}
