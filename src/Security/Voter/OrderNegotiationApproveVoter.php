<?php

namespace App\Security\Voter;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class OrderNegotiationApproveVoter extends Voter
{
    protected $order_repo;
    private $buyerOrigin = '4b5771';
    private $sellerOrigin = '48fcb8';

    public function __construct(EntityManagerInterface $entity_manager)
    {
        $this->order_repo = $entity_manager->getRepository(Order::class);   
    }
    protected function supports($attribute, $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute , [$this->sellerOrigin, $this->buyerOrigin]) && $subject == 'order_negotiation_approve';
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if($this->sellerOrigin == $attribute && $user->getRole() == 'ROLE_USER_SELLER') {
            return true;
        }else if($this->buyerOrigin == $attribute && $user->getRole() == 'ROLE_USER_GOVERNMENT') {
            return true;
        }  

        return false;
    }
}
