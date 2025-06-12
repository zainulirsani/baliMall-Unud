<?php

namespace App\Security\Voter;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrderPermissionVoter extends Voter
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
        return is_int($attribute) && $subject == 'order_permission';
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        $order = $this->order_repo->getOrderDetail($attribute, []);

        if(!is_null($order)) {
            if($user->getId() == $order['s_ow_id']) {
                return true;
            }else if ($user->getId() == $order['u_id']) {
                return true;
            }else if ($user->getId() == $order['o_ppkId']) { 
                return true;
            }else if ($user->getId() == $order['o_treasurerId']) {
                return true;
            }
        }

        return false;
    }
}
