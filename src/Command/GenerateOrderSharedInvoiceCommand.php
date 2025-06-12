<?php

namespace App\Command;

use App\EventListener\SetOrderSharedInvoiceEntityListener;
use Hashids\Hashids;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class GenerateOrderSharedInvoiceCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('app:generate-order-shared-invoice')
            ->setDescription('Generates order shared invoice.')
            ->setHelp('This command allows you to generate order shared invoice manually.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $askOrderIds = new Question('Please enter order id (separate with comma for multiple id): ');
        $askOrderIds->setValidator(function ($value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('The order id cannot be empty.');
            }

            return $value;
        });

        $orderIds = str_replace(' ', '', $helper->ask($input, $output, $askOrderIds));
        $ids = explode(',', $orderIds);
        // ['v1' => 16, 'v2' => 18]
        $encoder = new Hashids(SetOrderSharedInvoiceEntityListener::class, 18, getenv('HASHIDS_ALPHABET'));

        $output->writeln(sprintf('Shared invoice hash for order id [%s] is: %s', $orderIds, $encoder->encode($ids)));

        return 1;
    }
}
