<?php

namespace App\EventListener;

use App\Entity\Cart;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class UserLogoutListener implements LogoutSuccessHandlerInterface
{
    private $manager;
    private $cart;

    public function __construct(EntityManagerInterface $manager, array $configuration)
    {
        $this->manager = $manager;
        $this->cart = new CartService($configuration);
    }

    public function onLogoutSuccess(Request $request)
    {
        $cartId = $this->cart->getCartId();
        $session = $request->getSession()->all();

        if (isset($session[$cartId]) && !empty($session[$cartId])) {
            $firewall = unserialize($session['_security_main'], ['allowed_classes' => true]);
            /** @var User $temp */
            $temp = $firewall->getUser();
            /** @var User $user */
            $user = $this->manager->getRepository(User::class)->find($temp->getId());
            $roles = ['ROLE_USER', 'ROLE_USER_BUYER', 'ROLE_USER_GOVERNMENT', 'ROLE_USER_BUSINESS'];

            if (in_array($user->getRole(), $roles, false)) {
                /** @var CartRepository $repository */
                $repository = $this->manager->getRepository(Cart::class);
                $cart = $repository->findOneBy(['user' => $user]);

                if (!$cart instanceof Cart) {
                    $cart = new Cart();
                    $cart->setUser($user);
                }

                $cart->setReference(date('YmdHis'));
                $cart->setContent($session[$cartId]);

                if ($user->getRole() === 'ROLE_USER_GOVERNMENT' && !empty($user->getLkppToken())) {
                    $user->nullifyLkppUserToken();
                    $this->manager->persist($user);
                }

                $this->manager->persist($cart);
                $this->manager->flush();
            }
        }

        return new RedirectResponse('/');
    }
}
