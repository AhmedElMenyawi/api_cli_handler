<?php

namespace App\Command;

use Exception;
use Psr\Log\LoggerInterface;
use App\DTO\TransactionRequest;
use App\Service\TransactionService;
use Symfony\Component\Console\Command\Command;
use App\Service\Payment\PaymentProcessorFactory;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\Payment\PaymentResponseAdapterFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:process-transaction',
    description: 'Command to process a financial transaction by providing provider name and trx details (transaction & card)',
)]
class ProcessTransactionCommand extends Command
{
    private $transactionService;
    private $logger;
    private $validator;
    private $paymentProcessorFactory;
    private $paymentResponseAdapterFactory;

    public function __construct(TransactionService $transactionService, ValidatorInterface $validator, LoggerInterface $logger,PaymentProcessorFactory $paymentProcessorFactory, PaymentResponseAdapterFactory $paymentResponseAdapterFactory)
    {
        $this->transactionService = $transactionService;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->paymentProcessorFactory = $paymentProcessorFactory;
        $this->paymentResponseAdapterFactory = $paymentResponseAdapterFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('provider', InputArgument::REQUIRED, 'Payment provider (e.g., "aci", "shift4")')
            ->addOption('amount', null, InputOption::VALUE_REQUIRED, 'Transaction amount')
            ->addOption('currency', null, InputOption::VALUE_REQUIRED, 'Transaction currency')
            ->addOption('card_number', null, InputOption::VALUE_REQUIRED, 'Card number')
            ->addOption('card_exp_year', null, InputOption::VALUE_REQUIRED, 'Card expiration year')
            ->addOption('card_exp_month', null, InputOption::VALUE_REQUIRED, 'Card expiration month')
            ->addOption('card_cvv', null, InputOption::VALUE_REQUIRED, 'Card CVV');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $io = new SymfonyStyle($input, $output);
            $provider = $input->getArgument('provider');
            $transactionRequest = $this->mapDTO($input);
            $errors = $this->validator->validate($transactionRequest);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $io->error($error->getMessage());
                }
                return Command::FAILURE;
            }
            //use the transaction service here !
            $paymentProcessor = $this->paymentProcessorFactory->create($provider);
            $paymentProcessed = $paymentProcessor->processPayment($transactionRequest);

            $paymentAdapter = $this->paymentResponseAdapterFactory->create($provider);
            $unifiedResponse = $paymentAdapter->returnResponse($paymentProcessed);

            // Log the processed transaction
            $this->logger->info('Payment processed: ' . json_encode($unifiedResponse));
            $this->logger->info('Payment message: ' . $unifiedResponse->message);

            // Return response based on success or failure
            if ($unifiedResponse->success) {
                $io->success('Transaction processed successfully');
                $io->writeln('Transaction Data: ' . json_encode($unifiedResponse->data));
                return Command::SUCCESS;
            } else {
                $io->error('Transaction failed: ' . $unifiedResponse->message);
                return Command::FAILURE;
            }
        } catch (Exception $e) {
            $this->logger->error('Unexpected error: ' . $e->getMessage());
            $io->error('An unexpected error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
        return Command::FAILURE;
    }

    private function mapDTO(InputInterface $input): TransactionRequest
{
    $transactionRequest = new TransactionRequest();
    $transactionRequest->amount = $input->getOption('amount') ?? null;
    $transactionRequest->currency = $input->getOption('currency') ?? null;
    $transactionRequest->cardNumber = $input->getOption('card_number') ?? null;
    $transactionRequest->cardExpYear = $input->getOption('card_exp_year') ?? null;
    $transactionRequest->cardExpMonth = $input->getOption('card_exp_month') ?? null;
    $transactionRequest->cardCvv = $input->getOption('card_cvv') ?? null;

    return $transactionRequest;
}

}
