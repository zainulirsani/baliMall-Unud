<?php

namespace App\Command;

use App\Entity\User;
use App\Utility\GoogleMailHandler;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use Faker\Factory;
use Hashids\Hashids;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PopulateDummyUserCommand extends Command
{
    /** @var ObjectManager $manager */
    private $manager;

    public function __construct(Registry $registry)
    {
        parent::__construct();

        $this->manager = $registry->getManager();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:populate-dummy-user')
            ->setDescription('Populates dummy users.')
            ->setHelp('This command allows you to populate many dummy users.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (getenv('APP_ENV') !== 'dev') {
            $output->writeln('This command available only in dev environment!');

            return 0;
        }

        $total = 20000;
        $faker = Factory::create();
        $salt = 'App\Entity\UserDummy';
        $encoder = new Hashids($salt, 6, getenv('HASHIDS_ALPHABET'));
        $users = [];

        for ($i = 0; $i < $total; $i++) {
            $email = $faker->unique()->email;

            $user = new User();
            $user->setUsername($faker->unique()->userName);
            $user->setEmail($email);
            $user->setEmailCanonical(GoogleMailHandler::validate($email));
            $user->setPassword('$2y$10$.x/5iWqZFCtMt48iezRHiOV2Fp8SAGw9KXgWV7hCPKYiMg/nxEJYS'); // password
            $user->setRole('ROLE_USER');
            $user->setIsActive(false);
            $user->setIsDeleted(false);
            $user->setFirstName($faker->firstName);
            $user->setLastName($faker->lastName);
            $user->setPhoneNumber($faker->e164PhoneNumber);
            $user->setNewsletter(false);

            try {
                $user->setDob(new DateTime($faker->dateTimeThisCentury->format('Y-m-d')));
            } catch (Exception $e) {
            }

            $this->manager->persist($user);

            $users[] = $user;
        }

        $this->manager->flush();

        foreach ($users as $user) {
            $user->setDirSlug($encoder->encode($user->getId()));

            $this->manager->persist($user);
        }

        $this->manager->flush();

        $output->writeln(sprintf('Success to populate %d dummy users!', $total));

        return 1;
    }
}
