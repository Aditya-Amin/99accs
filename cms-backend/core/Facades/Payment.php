<?php

namespace Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(\Core\Contracts\PaymentGatewayContract $gateway)
 * @method static \Core\Contracts\PaymentGatewayContract|null get(string $slug)
 * @method static array all()
 * @method static array available()
 * @method static bool has(string $slug)
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payment.manager';
    }
}
