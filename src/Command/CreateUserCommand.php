<?php

namespace App\Command;

use App\Utility\UserManipulator;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateUserCommand extends Command
{
    private $userManipulator;

    public function __construct(UserManipulator $userManipulator)
    {
        parent::__construct();

        $this->userManipulator = $userManipulator;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:create-user')
            ->addOption('role', null, InputOption::VALUE_OPTIONAL, 'The role of the user.')
            ->setDescription('Creates a new user.')
            ->setHelp('This command allows you to create a new user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $role = $input->getOption('role');

        $askName = new Question('Please enter the name of the user: ');
        $askName->setValidator(function ($value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('The name cannot be empty.');
            }

            return $value;
        });

        $askUsername = new Question('Please enter the username of the user: ');
        $askUsername->setValidator(function ($value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('The username cannot be empty.');
            }

            return $value;
        });

        $askEmail = new Question('Please enter the email of the user: ');
        $askEmail->setValidator(function ($value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('The email cannot be empty.');
            }

            return $value;
        });

        $askPassword = new Question('Please enter the password of the user: ');
        $askPassword->setHidden(true);
        $askPassword->setValidator(function ($value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('The password cannot be empty.');
            }

            return $value;
        });

        $name = $helper->ask($input, $output, $askName);
        $username = $helper->ask($input, $output, $askUsername);
        $email = $helper->ask($input, $output, $askEmail);
        $password = $helper->ask($input, $output, $askPassword);

        if (empty($role)) {
            $askRole = new Question('Please enter the role of the user (ROLE_USER/ROLE_USER_BUYER/ROLE_USER_SELLER): ');
            $askRole->setValidator(function ($value) {
                $value = strtoupper($value);

                if (in_array($value, ['ROLE_USER_BUYER', 'ROLE_USER_SELLER'])) {
                    return $value;
                }

                return 'ROLE_USER';
            });

            $role = $helper->ask($input, $output, $askRole);
        } else {
            $role = in_array(strtoupper($role), ['ROLE_USER_BUYER', 'ROLE_USER_SELLER']) ? strtoupper($role) : 'ROLE_USER';
        }

        $data = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ];

        $user = $this->userManipulator->create($data);
        $message = $user ? 'User created successfully!' : 'Failed to create new user';

        $output->writeln($message);

        return 1;
    }
}
