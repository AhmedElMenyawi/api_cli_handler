<?php

namespace App\Service\Payment\Processors;

use Psr\Log\LoggerInterface;
use App\DTO\TransactionRequest;
use App\Service\Payment\PaymentProcessorInterface;

class ACIPaymentProcessor implements PaymentProcessorInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function processPayment(TransactionRequest $transactionRequest): array
    {
        // $this->logger->info('Inside ACIPaymentProcessor');
        return ['success' => true, 'message' => 'Payment processed via ACI'];
    }
}