<?php

namespace App\Repository;

use App\Entity\RefundBpd;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RefundBpd|null find($id, $lockMode = null, $lockVersion = null)
 * @method RefundBpd|null findOneBy(array $criteria, array $orderBy = null)
 * @method RefundBpd[]    findAll()
 * @method RefundBpd[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RefundBpdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefundBpd::class);
    }

    // /**
    //  * @return RefundBpd[] Returns an array of RefundBpd objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RefundBpd
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
