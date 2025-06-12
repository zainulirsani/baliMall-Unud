<?php

namespace App\Command;

use App\Entity\Operator;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateUserOperatorOwnerCommand extends Command
{
    protected static $defaultName = 'app:generate-user-operator-owner';
    protected static $defaultDescription = 'Add a short description for your command';
    private $manager;

    public function __construct(Registry $registry, LoggerInterface $logger)
    {
        parent::__construct();

        $this->manager = $registry->getManager();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userRepository = $this->manager->getRepository(User::class);
        $operatorRepository = $this->manager->getRepository(Operator::class);

        $users = $userRepository->findBy(['role' => 'ROLE_USER_SELLER']);

        if (count($users) > 0) {
            foreach ($users as $user) {
                if ($operatorRepository->count(['owner' => $user, 'role' => 'ROLE_ADMIN_MERCHANT_OWNER']) === 0) {
                    $operator = new Operator();
                    $operator->setOwner($user);
                    $fullname = $user->getFirstName(). ' '.$user->getLastName();
                    $operator->setFullname($fullname);
                    $operator->setPhone($user->getPhoneNumber() ?? 0);
                    $operator->setRole('ROLE_ADMIN_MERCHANT_OWNER');
                    $operator->setAddress(' ');

                    $this->manager->persist($operator);
                    $this->manager->flush();
                }
            }
        }

        return 1;
    }
}
