<?php

namespace App\Command;

use Exception;
use Psr\Log\LoggerInterface;
use App\Service\TransactionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:processTransaction',
    description: 'Command to process a financial transaction by providing provider name and trx details (transaction & card)',
)]
class ProcessTransactionCommand extends Command
{
    private $transactionService;
    private $logger;

    public function __construct(TransactionService $transactionService, LoggerInterface $logger)
    {
        $this->transactionService = $transactionService;
        $this->logger = $logger;
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
            $data = $this->buildDataFromInput($input);
            $response = $this->transactionService->processTransaction($provider, $data);
            if (isset($response['errors'])) {
                foreach ($response['errors'] as $error) {
                    $io->error('Transaction failed: ' . $error);
                }
                return Command::FAILURE;
            } else {
                $io->success("Transaction processed successfully\n" . json_encode($response['data'], JSON_PRETTY_PRINT));
                return Command::SUCCESS;
            }
        } catch (Exception $e) {
            $this->logger->error('Unexpected error: ' . $e->getMessage());
            $io->error('An unexpected error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
        return Command::FAILURE;
    }

    private function buildDataFromInput(InputInterface $input): array
    {
        $data = [
            'amount' => $input->getOption('amount'),
            'currency' => $input->getOption('currency'),
            'card_number' => $input->getOption('card_number'),
            'card_exp_year' => $input->getOption('card_exp_year'),
            'card_exp_month' => $input->getOption('card_exp_month'),
            'card_cvv' => $input->getOption('card_cvv')
        ];
        return $data;
    }
}
