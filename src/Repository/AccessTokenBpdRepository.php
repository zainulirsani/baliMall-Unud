<?php

namespace App\Repository;

use App\Entity\AccessTokenBpd;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AccessTokenBpd|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccessTokenBpd|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccessTokenBpd[]    findAll()
 * @method AccessTokenBpd[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccessTokenBpdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessTokenBpd::class);
    }

}
