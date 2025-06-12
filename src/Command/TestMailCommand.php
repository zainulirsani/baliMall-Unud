<?php

namespace App\Command;

use App\Email\BaseMail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestMailCommand extends Command
{
    /** @var BaseMail $baseMail */
    private $baseMail;

    public function __construct(BaseMail $baseMail)
    {
        parent::__construct();

        $this->baseMail = $baseMail;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:test-mail')
            ->setDescription('Test mail sending functionality.')
            ->setHelp('This command allows you to send a test email.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mail = $this->baseMail;
        $mail->setMailSubject('Test Mail');
        $mail->setMailDebug();
        $mail->setToAdmin();
        $mail->send();

        $output->writeln('Mail sent!');

        return 1;
    }
}
