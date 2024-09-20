<?php

namespace App\DTO;

class UnifiedTransactionResponse
{
    public bool $success;
    public string $message;
    public array $data;

    public function __construct(
        bool $success,
        string $message,
        string $transactionId,
        string $createdAt,
        float $amount,
        string $currency,
        string $cardBin,
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->data = [
            "transactionId" => $transactionId,
            "createdAt" => $createdAt,
            "amount" => $amount,
            "currency" => $currency,
            "cardBin" => $cardBin
        ];
    }
}
