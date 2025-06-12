<?php

namespace App\Repository;

use App\Entity\Banner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Banner|null find($id, $lockMode = null, $lockVersion = null)
 * @method Banner|null findOneBy(array $criteria, array $orderBy = null)
 * @method Banner[]    findAll()
 * @method Banner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BannerRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Banner::class;
        $this->alias = 'b';

        parent::__construct($registry);
    }

    public function findById(int $id) :array
    {
        $query = $this->builder = $this
            ->createQueryBuilder('b')
            ->select('b')
            ->where('b.id = :id')
            ->andWhere('b.status != :deleted')
            ->setParameter('id', $id)
            ->setParameter('deleted', 'deleted')
            ->getQuery();
        ;

        return $query->getScalarResult();
    }

    public function findByPosition(string $position) :array
    {
        $query = $this->builder = $this
            ->createQueryBuilder('b')
            ->select('b')
            ->where('b.position = :position')
            ->andWhere('b.status = :active')
            ->setParameter('position', $position)
            ->setParameter('active', 'active')
            ->setMaxResults(1)
            ->getQuery();
        ;

        return $query->getArrayResult();
    }

    public function applyFilters(array $parameters = []): void
    {
        if (isset($parameters['keywords']) && !empty($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('b.name', ':keywords'),
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('b')
            ->select('b')
            ->where('b.id <> :id')
            ->andWhere('b.status != :deleted')
            ->setParameter('id', 0)
            ->setParameter('deleted', 'deleted')
        ;
        $this->applyFilters($parameters);

        $query = clone $this->builder;
        $query->select('count(b.id)');

        $results['total'] = $query->getQuery()->getSingleScalarResult();
        $this->setLimitAndOffset($parameters);
        $results['data'] = $this->builder->getQuery()->getScalarResult();

        return $results;
    }

    public function checkIfPositionActive(string  $position, int $id = 0): bool
    {
        $query = $this->builder = $this
            ->createQueryBuilder('b')
            ->select('b')
            ->where('b.position = :position')
            ->andWhere('b.status = :status')
            ->andWhere('b.id <> :id')
            ->setParameter('position', $position)
            ->setParameter('status', 'active')
            ->setParameter('id', $id)
        ;

        $result = $query->getQuery()->getArrayResult();

        return !empty($result);
    }

}
