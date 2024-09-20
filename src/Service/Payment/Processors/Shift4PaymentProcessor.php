<?php

namespace App\Service\Payment\Processors;

use Exception;
use Psr\Log\LoggerInterface;
use App\DTO\TransactionRequest;
use App\Service\Payment\PaymentProcessorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class Shift4PaymentProcessor implements PaymentProcessorInterface
{
    private $logger;
    private $httpClient;
    private $apiUsername;
    public function __construct(LoggerInterface $logger, HttpClientInterface $httpClient, string $shift4ApiUsername)
    {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->apiUsername = $shift4ApiUsername;
    }

    public function processPayment(TransactionRequest $transactionRequest): array
    {
        // $this->logger->info('Inside Shift4PaymentProcessor');
        try {
            $requestData = [
                'amount' => $transactionRequest->getAmount() * 100,
                'currency' => $transactionRequest->getCurrency(),
                'card' => [
                    'number' => $transactionRequest->getCardNumber(),
                    'expMonth' => $transactionRequest->getCardExpMonth(),
                    'expYear' => $transactionRequest->getCardExpYear(),
                    'cvc' => $transactionRequest->getCardCvv(),
                ],
                'description' => 'Description we agreed on',
            ];
            
            $response = $this->httpClient->request('POST', 'https://api.shift4.com/charges', [
                'auth_basic' => [$this->apiUsername, ''],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData,
            ]);
            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray(false);
            $this->logger->info('responseData processPayment Shift4PaymentProcessor: ' . json_encode($responseData));
            if ($statusCode == 200) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Payment processed successfully'
                ];
            } else {

                if (isset($responseData['error'])) {
                    $errorType = $responseData['error']['type'] ?? 'unknown_error';
                    $errorMessage = $responseData['error']['message'] ?? 'An unknown error occurred';
                    $this->logger->error("Error from Shift4: $errorType - $errorMessage");
                    return [
                        'success' => false,
                        'message' => $errorMessage,
                    ];
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error during Shift4 payment: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while processing payment through Shift4'
            ];
        }
        return ['success' => false, 'message' => 'An error occurred, please try again'];
    }
}
