<?php

namespace App\Service;

use Exception;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use App\DTO\TransactionRequest;
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

    /**
     * Processes a transaction request for a specified provider.
     *
     * This function handles the business logic for processing a transaction request by:
     * - Mapping the request data to a DTO
     * - Validating the transaction request
     * - Processing the payment using the appropriate payment processor
     * - Adapting the payment response to a unified format
     * 
     * If validation errors occur or if the payment processing fails, the function returns an array with error messages
     * Otherwise, it returns a successful response with transaction data
     *
     * @param string $provider The transaction provider (e.g., Shift4, ACI)
     * @param array $data The transaction data in array format
     * 
     * @return array Returns an array:
     *               - ['data' => ..., 'message' => 'Transaction processed successfully']: If the transaction is successful
     *               - ['errors' => [...]]: If there are validation errors or processing failures
     *
     * @throws Exception If an unexpected error occurs during transaction processing
     */

    public function processTransaction(string $provider, array $data): array
    {
        try {
            $this->logger->info('Processing provider: ' . $provider);
            $transactionRequest = $this->mapDataToObject($provider, $data); //name change
            $errors = $this->validator->validate($transactionRequest);

            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return ['errors' => $messages];
            }
            $paymentProcessor = $this->paymentProcessorFactory->create($provider);
            if ($paymentProcessor === null) {
                throw new InvalidArgumentException("Unsupported payment provider : $provider");
            }
            $paymentProcessed =  $paymentProcessor->processPayment($transactionRequest);
            $paymentAdapter = $this->paymentResponseAdapterFactory->create($provider);
            $unifiedResponse =  $paymentAdapter->returnResponse($paymentProcessed);
            if ($unifiedResponse->success) {
                return ['data' => $unifiedResponse->data, 'message' => 'Transaction processed successfully'];
            } else {
                return ['errors' => [$unifiedResponse->message]];
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());
            return ['errors' => [$e->getMessage()]];
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return ['errors' => ["An unexpected error occurred."]];
        }
        return ['errors' => ["An unexpected error occurred."]];
    }

    private function mapDataToObject(string $provider, array $data): TransactionRequest
    {
        $transactionRequest = new TransactionRequest();
        $transactionRequest->provider = $provider ?? null;
        $transactionRequest->amount = $data['amount'] ?? null;
        $transactionRequest->currency = $data['currency'] ?? null;
        $transactionRequest->cardNumber = $data['card_number'] ?? null;
        $transactionRequest->cardExpYear = $data['card_exp_year'] ?? null;
        $transactionRequest->cardExpMonth = $data['card_exp_month'] ?? null;
        $transactionRequest->cardCvv = $data['card_cvv'] ?? null;
        return $transactionRequest;
    }
}
