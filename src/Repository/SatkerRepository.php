<?php

namespace App\Repository;

use App\Entity\Satker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Satker|null find($id, $lockMode = null, $lockVersion = null)
 * @method Satker|null findOneBy(array $criteria, array $orderBy = null)
 * @method Satker[]    findAll()
 * @method Satker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SatkerRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Satker::class;
        $this->alias = 'satker';

        parent::__construct($registry);
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('satker')
            ->select('satker')
            ->where('satker.id <> :id')
            ->setParameter('id', 0)
        ;

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        $query = clone $this->builder;
        $query->select('count(satker.id)');

        $results['total'] = $query->getQuery()->getSingleScalarResult();
        $this->setLimitAndOffset($parameters);
        $results['data'] = $this->builder->getQuery()->getScalarResult();

        return $results;
    }

    public function applyFilters(array $parameters = []): void
    {
        if (isset($parameters['keywords']) && !empty($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('satker.satkerName', ':keywords'),
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        if (isset($parameters['id_lpse']) && !empty($parameters['id_lpse'])) {
            if($parameters['id_lpse'] == '-') {
                $this->builder
                    ->andWhere('satker.idLpse IS NULL')
                ;
            } else {
                $this->builder
                    ->andWhere('satker.idLpse = :id_lpse')
                    ->setParameter('id_lpse', $parameters['id_lpse'])
                ;
            }
        }

        if (isset($parameters['id_satker']) && !empty($parameters['id_satker'])) {
            $this->builder
                ->andWhere('satker.idSatker = :id_satker')
                ->setParameter('id_satker', $parameters['id_satker'])
            ;
        }

    }

    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        if ($parameters['id_lpse'] == null || $parameters['id_lpse'] == '' || $parameters['id_lpse'] == 0) {
            $this->builder = $this
                ->createQueryBuilder('satker')
                ->where('satker.id > :id')
                ->andWhere('satker.user = :user')
                // ->andWhere('satker.idLpse = :user_lpse')
                ->setParameter('id', 0)
                ->setParameter('user', $parameters['user'])
                // ->setParameter('user_lpse', $parameters['id_lpse'])
            ;
        } else {
            $this->builder = $this
                ->createQueryBuilder('satker')
                ->where('satker.id > :id')
                // ->andWhere('satker.user = :user')
                ->andWhere('satker.idLpse = :user_lpse')
                ->setParameter('id', 0)
                // ->setParameter('user', $parameters['user'])
                ->setParameter('user_lpse', $parameters['id_lpse'])
            ;
        }

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }

    // /**
    //  * @return Satker[] Returns an array of Satker objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Satker
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
