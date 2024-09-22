<?php

namespace App\Tests;

use App\Service\Payment\Processors\ACIPaymentProcessor;
use App\DTO\TransactionRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ACIPaymentProcessorTest extends TestCase
{
    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var ACIPaymentProcessor
     */
    private $aciPaymentProcessor;

    protected function setUp(): void
    {
        // Mock the logger
        $this->logger = $this->createMock(LoggerInterface::class);

        // Create an instance of ACIPaymentProcessor with mocked dependencies
        $this->aciPaymentProcessor = new ACIPaymentProcessor(
            $this->logger,
            'testEntityId',
            'testBearerToken',
            'https://api.aci.com/payments'
        );
    }

    public function testProcessPaymentSuccess(): void
    {
        // Mock cURL success response
        $this->mockCurlResponse('{
            "result": {
                "code": "000.100.110",
                "description": "Request successfully processed"
            },
            "id": "txn123",
            "amount": 10000,
            "currency": "USD",
            "card": {
                "first6": "411111",
                "last4": "1111"
            }
        }', 200);

        // Create a TransactionRequest object
        $transactionRequest = new TransactionRequest();
        $transactionRequest->amount = 100.00;
        $transactionRequest->currency = 'USD';
        $transactionRequest->cardNumber = '4111111111111111';
        $transactionRequest->cardExpYear = 2024;
        $transactionRequest->cardExpMonth = 12;
        $transactionRequest->cardCvv = '123';

        // Call the processPayment method
        $result = $this->aciPaymentProcessor->processPayment($transactionRequest);

        // Assert that the result contains 'success' and correct transaction data
        $this->assertTrue($result['success']);
        $this->assertEquals('Request successfully processed', $result['message']);
        $this->assertEquals('txn123', $result['data']['id']);
    }

    public function testProcessPaymentFailure(): void
    {
        // Mock cURL failure response
        $this->mockCurlResponse('{
            "result": {
                "code": "100.100.101",
                "description": "Invalid card number"
            }
        }', 400);

        // Create a TransactionRequest object with invalid data
        $transactionRequest = new TransactionRequest();
        $transactionRequest->amount = 100.00;
        $transactionRequest->currency = 'USD';
        $transactionRequest->cardNumber = 'invalid_card';
        $transactionRequest->cardExpYear = 2024;
        $transactionRequest->cardExpMonth = 12;
        $transactionRequest->cardCvv = '123';

        // Call the processPayment method
        $result = $this->aciPaymentProcessor->processPayment($transactionRequest);

        // Assert that the result contains 'success' as false and appropriate error message
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid card number', $result['message']);
    }

    /**
     * Helper function to mock cURL responses.
     */
    private function mockCurlResponse(string $responseBody, int $httpCode)
    {
        $mockCurl = $this->getMockBuilder('stdClass')->onlyMethods(['curl_exec', 'curl_getinfo'])->getMock();
        $mockCurl->expects($this->any())->method('curl_exec')->willReturn($responseBody);
        $mockCurl->expects($this->any())->method('curl_getinfo')->willReturn($httpCode);

        // Override cURL functions
        global $mockCurlHandle;
        $mockCurlHandle = $mockCurl;

        // Use this mock when `curl_exec()` and `curl_getinfo()` are called in your class
        function curl_exec($curl)
        {
            global $mockCurlHandle;
            return $mockCurlHandle->curl_exec($curl);
        }

        function curl_getinfo($curl, $opt)
        {
            global $mockCurlHandle;
            return $mockCurlHandle->curl_getinfo($curl, $opt);
        }
    }
}
