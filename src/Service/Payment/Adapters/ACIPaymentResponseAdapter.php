<?php

namespace App\Service\Payment\Adapters;

use Psr\Log\LoggerInterface;
use App\DTO\UnifiedTransactionResponse;

class ACIPaymentResponseAdapter
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function returnResponse(array $response): UnifiedTransactionResponse
    {
        $success = $response['success'];

        $transactionId = $response['id'] ?? ''; 
        $createdAt = date('Y-m-d H:i:s', $response['created'] ?? time());
        $amount = ($response['amount'] ?? 0) / 100; 
        $currency = $response['currency'] ?? 'USD';
        $cardBin = substr($response['card']['number'] ?? '', 0, 6); 

        if ($success) {
            $message = 'Payment processed successfully via ACI';
        } else {
            $message = $response['error']['message'] ?? 'Payment failed';
        }

        return new UnifiedTransactionResponse(
            $success,
            $message,
            $transactionId,
            $createdAt,
            $amount,
            $currency,
            $cardBin
        );
    }
}
