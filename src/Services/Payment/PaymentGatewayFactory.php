<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Drivers\BkashGateway;
use App\Services\Payment\Drivers\ManualGateway;
use App\Services\Payment\Drivers\NagadGateway;
use App\Services\Payment\Drivers\PaykureghorGateway;
use App\Services\Payment\Drivers\PaypalGateway;
use App\Services\Payment\Drivers\RokopayGateway;
use App\Services\Payment\Drivers\SslcommerzGateway;
use App\Services\Payment\Drivers\StripeGateway;
use App\Services\Payment\Drivers\WalletGateway;

final class PaymentGatewayFactory
{
    public static function make(string $driver): PaymentGatewayInterface
    {
        return match ($driver) {
            'stripe' => new StripeGateway(),
            'paypal' => new PaypalGateway(),
            'bkash' => new BkashGateway(),
            'nagad' => new NagadGateway(),
            'sslcommerz' => new SslcommerzGateway(),
            'paykureghor' => new PaykureghorGateway(),
            'rokopay' => new RokopayGateway(),
            'wallet' => new WalletGateway(),
            default => new ManualGateway(),
        };
    }

    /** @return PaymentGatewayInterface[] keyed by name */
    public static function enabled(): array
    {
        $list = (array) (config('payments.gateways') ?? []);
        $out = [];
        foreach ($list as $key => $cfg) {
            if (!empty($cfg['enabled'])) {
                $out[$key] = self::make($key);
            }
        }
        if (empty($out)) {
            $out['manual'] = new ManualGateway();
            $out['wallet'] = new WalletGateway();
        }
        return $out;
    }
}
