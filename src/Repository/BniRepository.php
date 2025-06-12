<?php

namespace App\Repository;

use App\Entity\Bni;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use Doctrine\ORM\EntityManagerInterface;
/**
 * @method Bni|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bni|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bni[]    findAll()
 * @method Bni[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BniRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Bni::class;
        $this->alias = 'bn';
        parent::__construct($registry);
    }

    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder('bn')
            ->where('bn.id > :id')
            ->setParameter('id', 0)
        ;

        if (isset($parameters['noVa'])) {
            $this->builder
                ->andWhere('bn.user = :user')
                ->setParameter('user', $parameters['user'])
            ;
        }

        if (isset($parameters['search_rid']) && !empty($parameters['search_rid'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('bn.requestId', ':search_rid'),
                ))->setParameter('search_rid', '%'.$parameters['search_rid'].'%')
            ;
        }

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }
}
