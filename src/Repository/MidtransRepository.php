<?php

namespace App\Repository;

use App\Entity\Midtrans;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Midtrans|null find($id, $lockMode = null, $lockVersion = null)
 * @method Midtrans|null findOneBy(array $criteria, array $orderBy = null)
 * @method Midtrans[]    findAll()
 * @method Midtrans[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MidtransRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Midtrans::class);
    }

    public function getPendingPayment(): array
    {
        return $this->createQueryBuilder('m')
            ->select(['m'])
            ->leftJoin(Order::class, 'o', 'WITH', 'o.midtransId = m.id')
            ->andWhere('m.status = :mStatus')
            ->andWhere('o.status = :oStatus')
            ->setParameter('mStatus', 'pending')
            ->setParameter('oStatus', 'pending')
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }
}
