<?php

namespace App\Repository;

use App\Entity\UserAddress;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class UserAddressRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = UserAddress::class;
        $this->alias = 'ua';

        parent::__construct($registry);
    }

    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder('ua')
            ->where('ua.id > :id')
            ->setParameter('id', 0)
        ;

        if (isset($parameters['user'])) {
            $this->builder
                ->andWhere('ua.user = :user')
                ->setParameter('user', $parameters['user'])
            ;
        }

        if (isset($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('ua.title', ':keywords'),
                    $this->builder->expr()->like('ua.address', ':keywords')
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }
}
