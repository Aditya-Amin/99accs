<?php

namespace App\Providers;

use App\Listeners\NotifyAdminsOfPasswordReset;
use App\Models\Customer;
use App\Models\Order;
use App\Observers\CustomerObserver;
use App\Observers\OrderObserver;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Eloquent model observers → admin dashboard notifications.
        Order::observe(OrderObserver::class);
        Customer::observe(CustomerObserver::class);

        // Auth event (not a model) → admin notification on completed reset.
        Event::listen(PasswordReset::class, NotifyAdminsOfPasswordReset::class);
    }
}
