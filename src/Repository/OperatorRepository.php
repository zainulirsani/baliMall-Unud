<?php

namespace App\Repository;

use App\Entity\Operator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Operator|null find($id, $lockMode = null, $lockVersion = null)
 * @method Operator|null findOneBy(array $criteria, array $orderBy = null)
 * @method Operator[]    findAll()
 * @method Operator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OperatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operator::class);
    }

    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder('o')
            ->select(['o'])
            ->where('o.owner = :user')
            ->setParameter('user', $parameters['user'])
        ;

//        $this->setLimitAndOffset($parameters);
//        $this->setOrderBy($parameters);

        return $this->builder;
    }

}
