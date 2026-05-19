<?php
namespace Core\Plugins\Stripe\Providers;

use Illuminate\Support\ServiceProvider;
use Core\Facades\Payment;
use Core\Plugins\Stripe\Gateways\StripeGateway;

class StripeServiceProvider extends ServiceProvider {
    public function register(): void {
        // Bind Stripe SDK
    }

    public function boot(): void {
        Payment::register(new StripeGateway());
    }
}

