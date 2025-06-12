<?php

namespace App\Repository;

use App\Entity\ProductReview;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ProductReviewRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = ProductReview::class;
        $this->alias = 'pr';

        parent::__construct($registry);
    }

    public function getPaginatedResult(array $parameters = [], bool $count = false): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder('pr')
            ->select(['pr', 'u.firstName AS u_firstName', 'u.lastName AS u_lastName', 'u.photoProfile AS u_photoProfile'])
            ->leftJoin(User::class, 'u', 'WITH', 'u.id = pr.user')
            ->where('pr.id <> :id')
            ->andWhere('pr.product = :product')
            ->setParameter('id', 0)
            ->setParameter('product', $parameters['product'])
        ;     

        if (isset($parameters['status'])) {
            $this->builder
                ->andWhere('pr.status = :status')
                ->setParameter('status', $parameters['status'])
            ;
        }


        if ($count) {
            $this->builder->select('count(pr.id) AS count');
        } else {
            $this->setLimitAndOffset($parameters);
            $this->setOrderBy($parameters);
        }

        return $this->builder;
    }

    public function getProductReviewDetail(int $orderId, int $productId, int $buyerId)
    {
        return $this
            ->createQueryBuilder($this->alias)
            ->where('pr.order = :order_id')
            ->andWhere('pr.product = :product_id')
            ->andWhere('pr.user = :buyer_id')
            ->setParameter('order_id', $orderId)
            ->setParameter('product_id', $productId)
            ->setParameter('buyer_id', $buyerId)
            ->getQuery()
            ->getScalarResult()
        ;
    }
}
