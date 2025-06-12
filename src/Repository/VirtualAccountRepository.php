<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use App\Entity\VirtualAccount;
use Doctrine\Persistence\ManagerRegistry;

class VirtualAccountRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = VirtualAccount::class;
        $this->alias = 'va';

        parent::__construct($registry);
    }

    public function getUnpaidOrders(User $user)
    {
        return $this
            ->createQueryBuilder('va')
            ->leftJoin(Order::class, 'o', 'WITH', 'va.invoice = o.sharedInvoice')
            ->where('va.id <> :id')
            ->andWhere('va.paidStatus = :paid_status')
            ->andWhere('o.buyer = :buyer')
            ->setParameter('id', 0)
            ->setParameter('paid_status', '0')
            ->setParameter('buyer', $user)
            ->groupBy('o.sharedInvoice')
            ->getQuery()
            ->getResult()
        ;
    }
}
