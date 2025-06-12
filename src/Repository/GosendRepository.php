<?php

namespace App\Repository;

use App\Entity\Gosend;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Gosend|null find($id, $lockMode = null, $lockVersion = null)
 * @method Gosend|null findOneBy(array $criteria, array $orderBy = null)
 * @method Gosend[]    findAll()
 * @method Gosend[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GosendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gosend::class);
    }

    public function findByStoreOrderId($orderId)
    {
        return $this->createQueryBuilder('g')
            ->where('g.storeOrderId = :val')
            ->setParameter('val', $orderId)
            ->getQuery()
            ->getSingleResult(2)
            ;
    }

    // /**
    //  * @return Gosend[] Returns an array of Gosend objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Gosend
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
