<?php

namespace App\Support\Payments;

use App\Support\Herepay\HerepayGateway;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * The gateways Neekah can take payments through, by the name stored on each
 * payment. Adding one is a class and a line here.
 */
class PaymentGateways
{
    /** @var array<string, class-string<PaymentGateway>> */
    public const DRIVERS = [
        'herepay' => HerepayGateway::class,
    ];

    public function __construct(private Container $container) {}

    public function has(string $name): bool
    {
        return isset(self::DRIVERS[$name]);
    }

    public function for(string $name): PaymentGateway
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException("No payment gateway is called [{$name}].");
        }

        return $this->container->make(self::DRIVERS[$name]);
    }
}
