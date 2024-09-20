<?php

namespace App\Service\Payment;

use App\DTO\TransactionRequest;

interface PaymentProcessorInterface
{
    public function processPayment(TransactionRequest $transactionRequest): array;
}