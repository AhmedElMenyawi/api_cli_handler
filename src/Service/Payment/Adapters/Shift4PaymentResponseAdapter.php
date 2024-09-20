<?php

namespace App\Service\Payment\Adapters;

use Psr\Log\LoggerInterface;
use App\DTO\UnifiedTransactionResponse;

class Shift4PaymentResponseAdapter
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function returnResponse(array $response): UnifiedTransactionResponse
    {
        $success = $response['success'];
        $this->logger->info('returnResponse data: ' . json_encode($response));
        if(!$response['success']){
            $this->logger->info('returnResponse data: ' . json_encode($response));
        }
        $transactionId = $response['data']['id'] ?? ''; 
        $createdAt = date('Y-m-d H:i:s', $response['data']['created'] ?? time());
        $amount = ($response['data']['amount'] ?? 0) / 100; 
        $currency = $response['data']['currency'] ?? 'USD';
        $cardBin = substr($response['data']['card']['first6'] ?? '', 0, 6); 

        if ($success) {
            $message = 'Payment processed successfully via Shift4';
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
