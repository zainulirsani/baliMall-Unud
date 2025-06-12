<?php

namespace App\Utility;

use App\Entity\User;
use App\EventListener\UserEntityListener;
use App\Helper\StaticHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserManipulator
{
    private $entityManager;
    private $passwordEncoder;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        LoggerInterface $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->logger = $logger;
    }

    public function create($data)
    {
        $name = StaticHelper::splitFullName($data['name']);

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setEmailCanonical(GoogleMailHandler::validate($data['email']));
        $user->setRole($data['role']);
        $user->setIsActive(false);
        $user->setFirstName($name['first_name']);
        $user->setLastName($name['last_name']);

        $password = $this->passwordEncoder->encodePassword($user, $data['password']);
        $user->setPassword($password);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $listener = new UserEntityListener();
            $listener->handle(new GenericEvent($user, ['em' => $this->entityManager]));
        } catch(Exception $e) {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error($e->getMessage());

            $user = false;
        }

        return $user;
    }
}
