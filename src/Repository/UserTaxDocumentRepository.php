<?php

namespace App\Repository;

use App\Entity\UserTaxDocument;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class UserTaxDocumentRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = UserTaxDocument::class;
        $this->alias = 'utd';

        parent::__construct($registry);
    }

    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder('utd')
            ->where('utd.id > :id')
            ->andWhere('utd.user = :user')
            ->setParameter('id', 0)
            ->setParameter('user', $parameters['user'])
        ;

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }
}
