<?php

namespace App\Command;

use App\Helper\StaticHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateAppSecretCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('app:generate-app-secret')
            ->setDescription('Generates app secret value.')
            ->setHelp('This command allows you to generate app secret to be set in env variable.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $appSecret = StaticHelper::secureRandomCode(16);

        $output->writeln(sprintf('Your app secret is: %s', $appSecret));

        return 1;
    }
}
