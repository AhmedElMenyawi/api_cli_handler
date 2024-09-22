<?php

namespace App\Service\Payment\Processors;

use Exception;
use Psr\Log\LoggerInterface;
use App\DTO\TransactionRequest;
use App\Service\Payment\PaymentProcessorInterface;

class ACIPaymentProcessor implements PaymentProcessorInterface
{
    private $logger;
    private $entityId;
    private $bearerToken;
    private $paymentURL;

    public function __construct(LoggerInterface $logger, string $entityId, string $bearerToken, string $paymentURL)
    {
        $this->logger = $logger;
        $this->entityId = $entityId;
        $this->bearerToken = $bearerToken;
        $this->paymentURL = $paymentURL;
    }

    /**
     * Processes a payment request via cURL using the provided transaction data
     *
     * This function prepares the request data, initiates a cURL request to the payment URL, and handles the response
     * It manages both successful payment responses and error handling, including timeouts and connection issues
     * 
     * The response is parsed, and based on the result code, it returns either a success or failure message
     *
     * @param TransactionRequest $transactionRequest The request object containing transaction details to be processed
     * 
     * @return array Returns an array:
     *               - ['success' => true, 'message' => '...'] if the payment is successful
     *               - ['success' => false, 'message' => '...'] if the payment fails or encounters errors
     *               - Error messages are logged in case of timeouts, cURL errors, or unexpected exceptions
     *
     * @throws Exception If an unexpected error occurs during payment processing
     */

    public function processPayment(TransactionRequest $transactionRequest): array
    {
        try {
            $url = $this->paymentURL;
            $data = $this->prepareRequestData($transactionRequest);
            $curlRequest = $this->initiateCurlRequest($url, $data);
            $responseData = curl_exec($curlRequest);
            if (curl_errno($curlRequest)) {
                $curlError = curl_error($curlRequest);
                if (curl_errno($curlRequest) === CURLE_OPERATION_TIMEDOUT || curl_errno($curlRequest) === CURLE_COULDNT_CONNECT) {
                    $this->logger->error('Request timed out: ' . $curlError);
                    return ['success' => false, 'message' => 'Request timed out. Please try again later.'];
                }
                $this->logger->error('cURL error: ' . curl_error($curlRequest));
                return ['success' => false, 'message' => 'Connection error, please try again'];
            } else {
                $decodedResponse = json_decode($responseData, true);
                if (isset($decodedResponse['result']['code']) && strpos($decodedResponse['result']['code'], '000.100') === 0) {
                    return ['success' => true, 'message' => $decodedResponse['result']['description'], 'data' => $decodedResponse];
                } else {
                    $errorCode = $decodedResponse['result']['code'] ?? 'unknown';
                    $errorMessage = $decodedResponse['result']['description'] ?? 'Payment failed with unknown error';
                    $this->logger->error("Payment failed with code $errorCode: $errorMessage");
                    return ['success' => false, 'message' => $errorMessage];
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error during ACI payment: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while processing payment through ACI'
            ];
        } finally {
            if (isset($curlRequest)) {
                curl_close($curlRequest);
            }
        }
        return ['success' => false, 'message' => 'An error occurred, please try again'];
    }

    private function prepareRequestData(TransactionRequest $transactionRequest): string
    {
        $data = "entityId=" . $this->entityId .
            "&amount=" . $transactionRequest->getAmount() .
            "&currency=EUR" .
            "&paymentBrand=VISA" .
            "&paymentType=DB" .
            "&card.number=" . $transactionRequest->getCardNumber() .
            "&card.holder=Jane Jones" .
            "&card.expiryMonth=" . $transactionRequest->getCardExpMonth() .
            "&card.expiryYear=" . $transactionRequest->getCardExpYear() .
            "&card.cvv=" . $transactionRequest->getCardCvv();
        return  $data;
    }

    private function initiateCurlRequest($url, $data)
    {
        $curlRequest = curl_init();
        curl_setopt($curlRequest, CURLOPT_URL, $url);
        curl_setopt($curlRequest, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . $this->bearerToken
        ));
        curl_setopt($curlRequest, CURLOPT_POST, 1);
        curl_setopt($curlRequest, CURLOPT_POSTFIELDS, $data);
        $isProduction = getenv('APP_ENV') === 'prod';
        curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, $isProduction);
        curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlRequest, CURLOPT_TIMEOUT, 60);
        curl_setopt($curlRequest, CURLOPT_CONNECTTIMEOUT, 60);
        return $curlRequest;
    }
}
