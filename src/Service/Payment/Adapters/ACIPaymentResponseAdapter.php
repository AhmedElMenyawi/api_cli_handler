<?php

namespace App\Service\Payment\Adapters;

use DateTime;
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
        $success = $response['success'] ?? false;
        $transactionId = $response['data']['id'] ?? '';
        $createdAt = DateTime::createFromFormat('Y-m-d H:i:s.uO', $response['data']['timestamp'] ?? '') ?: new DateTime();
        $createdAt = $createdAt->format('Y-m-d H:i:s');
        $amount = $response['data']['amount'] ?? 0;
        $currency = $response['data']['currency'] ?? 'EUR';
        $cardBin = isset($response['data']['card']['bin']) ? substr($response['data']['card']['bin'], 0, 6) : '';

        if ($success) {
            $message = 'Payment processed successfully via ACI';
        } else {
            $message = $response['message'] ?? 'Payment failed, Please try again later';
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
