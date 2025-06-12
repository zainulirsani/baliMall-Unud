<?php

namespace App\Repository;

use App\Entity\Kldi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Kldi|null find($id, $lockMode = null, $lockVersion = null)
 * @method Kldi|null findOneBy(array $criteria, array $orderBy = null)
 * @method Kldi[]    findAll()
 * @method Kldi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KldiRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Kldi::class;
        $this->alias = 'k';

        parent::__construct($registry);
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('k')
            ->select('k')
            ->where('k.id <> :id')
            ->setParameter('id', 0)
        ;
        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        $query = clone $this->builder;
        $query->select('count(k.id)');

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
                    $this->builder->expr()->like('k.kldi_name', ':keywords'),
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        if (isset($parameters['id_lpse']) && !empty($parameters['id_lpse'])) {
            if($parameters['id_lpse'] == '-') {
                $this->builder
                    ->andWhere('k.id_lpse IS NULL')
                ;
            } else {
                $this->builder
                    ->andWhere('k.id_lpse = :id_lpse')
                    ->setParameter('id_lpse', $parameters['id_lpse'])
                ;
            }
        }

    }

    




    

    // /**
    //  * @return Kldi[] Returns an array of Kldi objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('k.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Kldi
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
