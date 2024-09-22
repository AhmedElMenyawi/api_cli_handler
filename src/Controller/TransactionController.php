<?php

namespace App\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use App\Service\TransactionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TransactionController extends AbstractController
{
    private $transactionService;
    private $logger;

    public function __construct(TransactionService $transactionService, LoggerInterface $logger)
    {
        $this->transactionService = $transactionService;
        $this->logger = $logger;
    }

    /**
     * Handles the processing of a transaction request for a specified provider
     *
     * This function accepts a POST request to process a transaction based on the provider passed as a parameter
     * It expects the request body to contain the transaction data in JSON format. The function uses a transaction
     * service to process the transaction, and based on the response, it returns a JSON response indicating success
     * or errors
     *
     * @param string $provider The transaction provider (e.g., Shift4, ACI)
     * @param Request $request The incoming HTTP request containing transaction data in JSON format
     * 
     * @return JsonResponse Returns a JSON response:
     *                      - 200: Transaction processed successfully with a message and data
     *                      - 400: Bad request with error details if there are validation issues or errors during processing
     *
     * @throws Exception If the transaction service encounters an exception
     */
    #[Route('/app/processTransaction/{provider}', name: 'app_transaction_processing', methods: ['POST'])]
    public function processTransaction(string $provider, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $response = $this->transactionService->processTransaction($provider, $data);
            if (isset($response['errors'])) {
                return new JsonResponse(['errors' => $response['errors']], 400);
            } else {
                return new JsonResponse(['data' => $response['data'], 'message' => 'Transaction processed successfully'], 200);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
