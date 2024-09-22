<?php

namespace App\Service\Payment;

use InvalidArgumentException;
use App\Service\Payment\Processors\ACIPaymentProcessor;
use App\Service\Payment\Processors\Shift4PaymentProcessor;

    class PaymentProcessorFactory
{
    private $aciPaymentProcessor;
    private $shift4PaymentProcessor;

    public function __construct(ACIPaymentProcessor $aciPaymentProcessor, Shift4PaymentProcessor $shift4PaymentProcessor)
    {
        $this->aciPaymentProcessor = $aciPaymentProcessor;
        $this->shift4PaymentProcessor = $shift4PaymentProcessor;
    }

    public function create(string $provider)
    {
        return match (strtolower($provider)) {
            'shift4' => $this->shift4PaymentProcessor,
            'aci' => $this->aciPaymentProcessor,
            default => throw new InvalidArgumentException("Unsupported payment provider: $provider"),
        };
    }
}