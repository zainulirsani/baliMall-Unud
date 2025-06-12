<?php

namespace App\Repository;

use App\Entity\UserPpkTreasurer;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class UserPpkTreasurerRepository extends BaseEntityRepository
{
    
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = UserPpkTreasurer::class;
        $this->alias = 'upt';

        parent::__construct($registry);
    }


    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder('upt')
            ->where('upt.id > :id')
            ->andWhere('upt.user = :user')
            ->andWhere('upt.type = :type')
            ->setParameter('id', 0)
            ->setParameter('type', $parameters['type'])
            ->setParameter('user', $parameters['user'])
        ;

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }
}
