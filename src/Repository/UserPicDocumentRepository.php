<?php

namespace App\Repository;

use App\Entity\UserPicDocument;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class UserPicDocumentRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = UserPicDocument::class;
        $this->alias = 'upd';

        parent::__construct($registry);
    }


    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder('upd')
            ->where('upd.id > :id')
            ->andWhere('upd.user = :user')
            ->setParameter('id', 0)
            ->setParameter('user', $parameters['user'])
        ;

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }
}
