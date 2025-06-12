<?php

namespace App\Repository;

use App\Entity\UserBpdBinding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserBpdBinding|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserBpdBinding|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserBpdBinding[]    findAll()
 * @method UserBpdBinding[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserBpdBindingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBpdBinding::class);
    }

    // /**
    //  * @return UserBpdBinding[] Returns an array of UserBpdBinding objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserBpdBinding
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
