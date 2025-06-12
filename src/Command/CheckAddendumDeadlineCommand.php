<?php

namespace App\Command;

use App\Entity\Order;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

class CheckAddendumDeadlineCommand extends Command
{
    protected static $defaultName = 'app:check-addendum-deadline';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('check addendum deadline 7 days before and send email to the user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTime();
        $sevenDaysLater = (clone $now)->modify('+27 days');

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('o')
           ->from(Order::class, 'o')
           ->where('DATE_ADD(o.createdAt, o.executionTime, \'DAY\') BETWEEN :now AND :sevenDaysLater')
           ->setParameter('now', $now)
           ->setParameter('sevenDaysLater', $sevenDaysLater)
           ->orderBy('o.createdAt', 'ASC');

        $tasks = $qb->getQuery()->getResult();
        $io->text("Now: " . $now->format('Y-m-d') . " | 27 Days Later: " . $sevenDaysLater->format('Y-m-d'));

        if (!$tasks) {
            $io->success('Tidak ada task yang deadline-nya kurang dari 27 hari.');
            return 0;
        }

        $io->title('Task dengan Deadline Kurang dari 27 Hari');
        foreach ($tasks as $task) {
            $io->text("Task ID: {$task->getId()} | Title: {$task->getInvoice()} | Deadline: " . $task->getCreatedAt()->modify('+27 days')->format('Y-m-d'));
        }

        return 0;
    }
}
