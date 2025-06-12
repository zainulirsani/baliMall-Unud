<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\Kldi;
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

class GenerateVaBniSatkerCommand extends Command
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
            ->setName('app:generate-va-bni-satker')
            ->setDescription('generate-va-bni-satker')
            ->setHelp('This command allows you to generate-va-bni-satker.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->manager->getRepository(Kldi::class);
        $kldi = $repository->findAll();
        foreach ($kldi as $key => $value) {
            if ($value->getDigitVa() == '') {
                $value->setDigitVa(str_pad($value->getId(), 8, "0", STR_PAD_LEFT));

                $this->manager->persist($value);
                $this->manager->flush();
            }
        }
        return 0;
    }
}
