<?php

namespace App\Tests;

use App\Service\TransactionService;
use App\Service\Payment\PaymentProcessorFactory;
use App\Service\Payment\PaymentResponseAdapterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;
use App\DTO\UnifiedTransactionResponse;

class TransactionServiceTest extends TestCase
{
    private $validator;
    private $logger;
    private $paymentProcessorFactory;
    private $paymentResponseAdapterFactory;

    protected function setUp(): void
    {
        $this->validator = $this->createStub(ValidatorInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->paymentProcessorFactory = $this->createStub(PaymentProcessorFactory::class);
        $this->paymentResponseAdapterFactory = $this->createStub(PaymentResponseAdapterFactory::class);
    }

    /**
     * Test success case with valid input.
     */
    public function testProcessTransactionSuccess(): void
    {
        $transactionService = new TransactionService(
            $this->validator,
            $this->logger,
            $this->paymentProcessorFactory,
            $this->paymentResponseAdapterFactory
        );

        $paymentProcessor = $this->createMock(\App\Service\Payment\PaymentProcessorInterface::class);
        $paymentProcessor->method('processPayment')->willReturn(['success' => true]);

        $paymentAdapter = $this->createMock(\App\Service\Payment\PaymentResponseAdapterInterface::class);
        $unifiedResponse = new UnifiedTransactionResponse(true, 'Payment successful', 'txn123', '2024-09-21 12:34:56', 10000, 'USD', '411111');
        $paymentAdapter->method('returnResponse')->willReturn($unifiedResponse);

        $this->paymentProcessorFactory->method('create')->willReturn($paymentProcessor);
        $this->paymentResponseAdapterFactory->method('create')->willReturn($paymentAdapter);

        $transactionData = [
            'amount' => "100.00",
            'currency' => 'USD',
            'card_number' => '4111111111111111',
            'card_exp_year' => "2024",
            'card_exp_month' => "12",
            'card_cvv' => '123'
        ];

        $result = $transactionService->processTransaction('shift4', $transactionData);

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('txn123', $result['data']['transactionId']);
    }

    /**
     * Test invalid provider case.
     */
    public function testProcessTransactionInvalidProvider(): void
    {
        $transactionService = new TransactionService(
            $this->validator,
            $this->logger,
            $this->paymentProcessorFactory,
            $this->paymentResponseAdapterFactory
        );

        $transactionData = [
            'amount' => "100.00",
            'currency' => 'USD',
            'card_number' => '4111111111111111',
            'card_exp_year' => "2024",
            'card_exp_month' => "12",
            'card_cvv' => '123'
        ];

        $result = $transactionService->processTransaction('invalid_provider', $transactionData);

        // Assert invalid provider error
        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString('Unsupported payment provider', $result['errors'][0]);
    }
}
