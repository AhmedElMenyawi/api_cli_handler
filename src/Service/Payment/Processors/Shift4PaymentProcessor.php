<?php

namespace App\Service\Payment\Processors;

use Exception;
use Psr\Log\LoggerInterface;
use App\DTO\TransactionRequest;
use App\Service\Payment\PaymentProcessorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\Exception\TimeoutException;


class Shift4PaymentProcessor implements PaymentProcessorInterface
{
    private $logger;
    private $httpClient;
    private $apiUsername;
    private $paymentURL;
    public function __construct(LoggerInterface $logger, HttpClientInterface $httpClient, string $shift4ApiUsername, string $paymentURL)
    {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->apiUsername = $shift4ApiUsername;
        $this->paymentURL = $paymentURL;
    }

    public function processPayment(TransactionRequest $transactionRequest): array
    {
        try {
            $requestData = $this->prepareRequestData($transactionRequest);
            $response = $this->httpClient->request('POST', $this->paymentURL, [
                'auth_basic' => [$this->apiUsername, ''],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData,
                'timeout' => 60
            ]);
            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray(false);
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
        } catch (TimeoutException $e) {
            return [
                'success' => false,
                'message' => 'The request timed out. Please try again later.',
            ];
        } catch (Exception $e) {
            $this->logger->error('Error during Shift4 payment: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while processing payment through Shift4'
            ];
        }
        return ['success' => false, 'message' => 'An error occurred, please try again'];
    }

    private function prepareRequestData(TransactionRequest $transactionRequest)
    {
        return [
            'amount' => (int)$transactionRequest->getAmount() * 100,
            'currency' => $transactionRequest->getCurrency(),
            'card' => [
                'number' => $transactionRequest->getCardNumber(),
                'expMonth' => $transactionRequest->getCardExpMonth(),
                'expYear' => $transactionRequest->getCardExpYear(),
                'cvc' => $transactionRequest->getCardCvv(),
            ],
            'description' => 'Description we agreed on',
        ];
    }
}
