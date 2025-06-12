<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Qris;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class QrisRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Qris::class;
        $this->alias = 'q';

        parent::__construct($registry);
    }

    public function getUnpaidOrders(User $user)
    {
        $query = $this
            ->createQueryBuilder('q')
            ->leftJoin(Order::class, 'o', 'WITH', 'q.invoice = o.qrisBillNumber')
            ->where('q.id <> :id')
            ->andWhere('q.qrStatus = :qr_status')
            ->andWhere('o.buyer = :buyer')
            ->setParameter('id', 0)
            ->setParameter('qr_status', 'Belum Terbayar')
            ->setParameter('buyer', $user)
            ->groupBy('q.invoice')
        ;

        $query->andWhere($query->expr()->isNotNull('o.qrisBillNumber'));

        return $query->getQuery()->getResult();
    }
}
