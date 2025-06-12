<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\Bni;
use App\Repository\BniRepository;
use App\Repository\OrderRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use DateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckExpiredBniCommand extends Command
{
    /** @var ObjectManager $manager */
    private $manager;
    private $logger;

    public function __construct(Registry $registry, LoggerInterface $logger)
    {
        parent::__construct();

        $this->manager = $registry->getManager();
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:check-expired-bni')
            ->setDescription('Check expired VA BNI')
            ->setHelp('This command allows you to check expired va bni.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var OrderRepository $repository */
        $repository = $this->manager->getRepository(Bni::class);
        $bni = $repository->findBy(['status' => 'pending']);
        $now = new DateTime(date('c', time()), new DateTimeZone('Asia/Makassar'));
        foreach ($bni as $key => $value) {
            if ($value->getExpiredTime() < $now) {
                $value->setStatus('expired');
                $value->setUpdatedAt();
                $this->manager->persist($value);
                $this->manager->flush();
                $this->logger->error(sprintf('VA BNI callback expired: %s', json_encode($value->getRequestId())));
            }
        }
        return 0;
    }
}
