<?php

namespace App\Service\Payment;

use App\DTO\UnifiedTransactionResponse;

interface PaymentResponseAdapterInterface
{
    public function returnResponse(array $response): UnifiedTransactionResponse;
}