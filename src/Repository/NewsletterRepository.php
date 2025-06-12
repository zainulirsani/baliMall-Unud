<?php

namespace App\Repository;

use App\Entity\Newsletter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class NewsletterRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Newsletter::class;
        $this->alias = 'n';

        parent::__construct($registry);
    }

    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this->createQueryBuilder('n');

        if (isset($parameters['keywords'])) {
            $this->builder
                ->where('n.email LIKE :keywords')
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }
}
