<?php

namespace App\Security\Voter;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrderNegotiationVoter extends Voter
{

    protected $order_repo;

    public function __construct(EntityManagerInterface $entity_manager)
    {
        $this->order_repo = $entity_manager->getRepository(Order::class);   
    }
    protected function supports($attribute, $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return is_int($attribute) && $subject == 'order_negotiation';
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        $negotiations = $this->order_repo->getOrderNegotiationProducts($attribute);

        $countNegotiations = count($negotiations) - 1;

        if(count($negotiations) > 0) {
           if($negotiations[$countNegotiations]['on_submittedAs'] == 'buyer' && $user->getRole() == 'ROLE_USER_SELLER') {
                return true;
           }else if($negotiations[$countNegotiations]['on_submittedAs'] == 'seller' && $user->getRole() == 'ROLE_USER_GOVERNMENT') {
                return true;
           }     
        }

        return false;
    }
}
