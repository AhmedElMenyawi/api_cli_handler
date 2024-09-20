<?php

namespace App\Service\Payment;

use InvalidArgumentException;
use App\Service\Payment\PaymentResponseAdapterInterface;
use App\Service\Payment\Adapters\ACIPaymentResponseAdapter;
use App\Service\Payment\Adapters\Shift4PaymentResponseAdapter;

class PaymentResponseAdapterFactory
{
    private $shift4Adapter;
    private $aciAdapter;

    public function __construct(Shift4PaymentResponseAdapter $shift4Adapter,ACIPaymentResponseAdapter $aciAdapter) 
    {
        $this->shift4Adapter = $shift4Adapter;
        $this->aciAdapter = $aciAdapter;
    }

    public function create(string $provider)
    {
        switch (strtolower($provider)) {
            case 'shift4':
                return $this->shift4Adapter;
            case 'aci':
                return $this->aciAdapter;
            default:
                throw new InvalidArgumentException("Unsupported payment provider: $provider");
        }
    }
}