<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use InvalidArgumentException;
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
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
