<?php

namespace App\Tests;

use App\Service\Payment\Processors\Shift4PaymentProcessor;
use App\DTO\TransactionRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Shift4PaymentProcessorTest extends TestCase
{
    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var HttpClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $httpClient;

    /**
     * @var Shift4PaymentProcessor
     */
    private $shift4PaymentProcessor;

    protected function setUp(): void
    {
        // Mock the logger and HTTP client, explicitly type hinting with the correct interface
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        // Create an instance of Shift4PaymentProcessor with mocked dependencies
        $this->shift4PaymentProcessor = new Shift4PaymentProcessor(
            $this->logger,  // Correctly type-hinted mock object
            $this->httpClient,
            'testApiKey',
            'https://api.shift4.com/charges'
        );
    }

    public function testProcessPaymentSuccess(): void
    {
        // Mock the response from the HTTP client
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([
            'success' => true,
            'data' => [
                'id' => 'txn123',
                'amount' => 10000,
                'currency' => 'USD',
                'card' => [
                    'first6' => '411111',
                    'last4' => '1111'
                ]
            ]
        ]);

        // Mock the HTTP client's request method
        $this->httpClient->method('request')->willReturn($response);

        // Create a TransactionRequest object
        $transactionRequest = new TransactionRequest();
        $transactionRequest->amount = 100.00;
        $transactionRequest->currency = 'USD';
        $transactionRequest->cardNumber = '4111111111111111';
        $transactionRequest->cardExpYear = 2024;
        $transactionRequest->cardExpMonth = 12;
        $transactionRequest->cardCvv = '123';

        // Call the processPayment method
        $result = $this->shift4PaymentProcessor->processPayment($transactionRequest);
        // Assert that the result contains 'success' and correct transaction data
        $this->assertTrue($result['success']);
        $this->assertEquals('Payment processed successfully', $result['message']);
        $this->assertEquals('txn123', $result['data']['data']['id']);
    }

    public function testProcessPaymentFailure(): void
    {
        // Mock a failed response from the HTTP client
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(400);
        $response->method('toArray')->willReturn([
            'error' => [
                'message' => 'Invalid card number'
            ]
        ]);

        // Mock the HTTP client's request method
        $this->httpClient->method('request')->willReturn($response);

        // Create a TransactionRequest object with invalid data
        $transactionRequest = new TransactionRequest();
        $transactionRequest->amount = 100.00;
        $transactionRequest->currency = 'USD';
        $transactionRequest->cardNumber = 'invalid_card';
        $transactionRequest->cardExpYear = 2024;
        $transactionRequest->cardExpMonth = 12;
        $transactionRequest->cardCvv = '123';

        // Call the processPayment method
        $result = $this->shift4PaymentProcessor->processPayment($transactionRequest);

        // Assert that the result contains 'success' as false and appropriate error message
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid card number', $result['message']);
    }
}
