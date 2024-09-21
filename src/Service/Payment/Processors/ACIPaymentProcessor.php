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
    public function processPayment(TransactionRequest $transactionRequest): array
    {
        try {
            $url = $this->paymentURL;
            $data = $this->prepareRequestData($transactionRequest);
            $curlRequest = $this->initiateCurlRequest($url, $data);
            $responseData = curl_exec($curlRequest);
            if (curl_errno($curlRequest)) {
                $this->logger->error('cURL error: ' . curl_error($curlRequest));
                return ['success' => false, 'message' => 'Connection error, please try again'];
            } else {
                $this->logger->info('cURL response: ' . $responseData);
                $decodedResponse = json_decode($responseData, true);
                if (isset($decodedResponse['result']['code']) && strpos($decodedResponse['result']['code'], '000.100') === 0) {
                    $this->logger->info('Payment was successful: ' . $decodedResponse['result']['description']);
                    return ['success' => true, 'message' => $decodedResponse['result']['description'], 'data' => $decodedResponse];
                } else {
                    $errorCode = $decodedResponse['result']['code'] ?? 'unknown';
                    $errorMessage = $decodedResponse['result']['description'] ?? 'Payment failed with unknown error';
                    $this->logger->error("Payment failed with code $errorCode: $errorMessage");
                    return ['success' => false, 'message' => $errorMessage];
                }
            }
            curl_close($curlRequest);
        } catch (Exception $e) {
            $this->logger->error('Error during ACI payment: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while processing payment through ACI'
            ];
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
        return $curlRequest;
    }
}
