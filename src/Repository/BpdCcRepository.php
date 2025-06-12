<?php

namespace App\Repository;

use App\Entity\BpdCc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BpdCc|null find($id, $lockMode = null, $lockVersion = null)
 * @method BpdCc|null findOneBy(array $criteria, array $orderBy = null)
 * @method BpdCc[]    findAll()
 * @method BpdCc[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BpdCcRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BpdCc::class);
    }

    // /**
    //  * @return BpdCc[] Returns an array of BpdCc objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BpdCc
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
