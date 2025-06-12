<?php

namespace App\Repository;

use App\Entity\OrderChangeLog;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrderChangeLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderChangeLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderChangeLog[]    findAll()
 * @method OrderChangeLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderChangeLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderChangeLog::class);
    }

    public function findByOrderId($orderId)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.orderId = :val')
            ->setParameter('val', $orderId)
            ->orderBy('o.id', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function getOrderBugStatusRating()
    {
        return $this->createQueryBuilder('o')
            ->select(['o','ord.status as order_status'])
            ->leftJoin(Order::class, 'ord', 'WITH', 'o.orderId = ord.id')
            ->where('o.previousValues LIKE :previousValues')
            ->andWhere('o.changes LIKE :changes')
            ->setParameter('changes', '%statusRating%')
            ->setParameter('previousValues', '%paid%')
            ->getQuery()
            ->getArrayResult()
        ;
    }

}
