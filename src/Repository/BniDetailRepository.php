<?php

namespace App\Repository;

use App\Entity\BniDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BniDetail|null find($id, $lockMode = null, $lockVersion = null)
 * @method BniDetail|null findOneBy(array $criteria, array $orderBy = null)
 * @method BniDetail[]    findAll()
 * @method BniDetail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BniDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BniDetail::class);
    }

    // /**
    //  * @return BniDetail[] Returns an array of BniDetail objects
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
    public function findOneBySomeField($value): ?BniDetail
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
