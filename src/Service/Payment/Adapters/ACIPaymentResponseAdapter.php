<?php

namespace App\Service\Payment\Adapters;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use App\DTO\UnifiedTransactionResponse;
use App\Service\Payment\PaymentResponseAdapterInterface;

class ACIPaymentResponseAdapter implements PaymentResponseAdapterInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function returnResponse(array $response): UnifiedTransactionResponse
    {
        try {
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
        } catch (Exception $e) {
            $this->logger->error('Exception in ACIPaymentResponseAdapter: ' . $e->getMessage());

            return new UnifiedTransactionResponse(
                false,
                "An error occurred while processing the payment",
                '',
                date('Y-m-d H:i:s', time()),
                0,
                'USD',
                ''
            );
        }
    }
}
