<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserSubscriber implements EventSubscriberInterface
{
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        if (null !== $session->get('_security_main')) {
            /** @var UsernamePasswordToken $token */
            $token = unserialize($session->get('_security_main'), ['allowed_classes' => true]);

            if ($user = $token->getUser()) {
                $roles = $user->getRoles();
                $role = $roles[0] ?? 'ROLE_INVALID';

                if ($role === 'ROLE_USER_GOVERNMENT') {
                    /** @var UserRepository $repository */
                    $repository = $this->manager->getRepository(User::class);
                    $temp = $repository->findOneBy([
                        'username' => $user->getUsername(),
                        'role' => 'ROLE_USER_GOVERNMENT',
                    ]);

                    if ($temp && $temp->getLkppLoginStatus() === 'logged_out') {
                        $temp->nullifyLkppUserToken();

                        $this->manager->persist($temp);
                        $this->manager->flush();

                        header('Location: /logout');
                        exit;
                    }
                }

                if ($role === 'ROLE_USER') {
                    $session->remove('_security_main');
                    $session->set('b2c_disabled', true);

                    header('Location: /login');
                    exit;
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }
}
