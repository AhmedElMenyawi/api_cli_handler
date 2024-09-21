<?php

namespace App\Service;

use Exception;
use Psr\Log\LoggerInterface;
use App\Dto\TransactionRequest;
use App\Service\Payment\PaymentProcessorFactory;
use App\Service\Payment\PaymentResponseAdapterFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionService
{
    private $validator;
    private $logger;
    private $paymentProcessorFactory;
    private $paymentResponseAdapterFactory;


    public function __construct(ValidatorInterface $validator, LoggerInterface $logger, PaymentProcessorFactory $paymentProcessorFactory, PaymentResponseAdapterFactory $paymentResponseAdapterFactory)
    {
        $this->validator = $validator;
        $this->logger = $logger;
        $this->paymentProcessorFactory = $paymentProcessorFactory;
        $this->paymentResponseAdapterFactory = $paymentResponseAdapterFactory;
    }

    public function processTransaction(string $provider, array $data): array
    {
        // $this->logger->info('inside processTransaction');
        try {
            $transactionRequest = $this->mapDTO($data);
            $errors = $this->validator->validate($transactionRequest);
            $this->logger->info("Count of errors : " . count($errors));
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return ['errors' => $messages];
            }
            $paymentProcessor = $this->paymentProcessorFactory->create($provider);
            $paymentProcessed =  $paymentProcessor->processPayment($transactionRequest);
            $paymentAdapter = $this->paymentResponseAdapterFactory->create($provider);
            $unifiedResponse =  $paymentAdapter->returnResponse($paymentProcessed);
            $this->logger->info('Payment processed: ' . json_encode($unifiedResponse));
            $this->logger->info('Payment processed: ' . $unifiedResponse->message);
            if ($unifiedResponse->success) {
                return ['data' => $unifiedResponse->data, 'message' => 'Transaction processed successfully'];
            } else {
                return ['errors' => [$unifiedResponse->message]];
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return ['errors' => ["An unexpected error occurred."]];
        }
    }

    private function mapDTO(array $data): TransactionRequest
    {
        $transactionRequest = new TransactionRequest();
        $transactionRequest->amount = $data['amount'] ?? null;
        $transactionRequest->currency = $data['currency'] ?? null;
        $transactionRequest->cardNumber = $data['card_number'] ?? null;
        $transactionRequest->cardExpYear = $data['card_exp_year'] ?? null;
        $transactionRequest->cardExpMonth = $data['card_exp_month'] ?? null;
        $transactionRequest->cardCvv = $data['card_cvv'] ?? null;
        return $transactionRequest;
    }
}
