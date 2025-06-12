<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\RuntimeException;

class BaseEntityRepository extends ServiceEntityRepository
{
    /** @var string $entity */
    protected $entity;

    /** @var string $alias */
    protected $alias = 't';

    /** @var QueryBuilder $builder */
    protected $builder;

    /** @var bool $debug */
    protected $debug = false;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, $this->entity);
    }

    public function countResult(string $primary = 'id'): int
    {
        $query = $this
            ->createQueryBuilder($this->alias)
            ->select(sprintf('count(%s.%s)', $this->alias, $primary))
            ->getQuery()
        ;

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException $e) {
            //
        }

        return 0;
    }

    public function checkSlug(string $slug, int $entityId = 0): int
    {
        $query = $this
            ->createQueryBuilder($this->alias)
            ->select(sprintf('count(%s.id)', $this->alias))
            ->where(sprintf('%s.slug = :slug', $this->alias))
            ->setParameter('slug', $slug)
        ;

        if ($entityId > 0) {
            $query
                ->andWhere(sprintf('%s.id <> :entity_id', $this->alias))
                ->setParameter('entity_id', $entityId)
            ;
        }

        try {
            return (int) $query->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return 0;
    }

    public function setLimitAndOffset(array $parameters = []): void
    {
        if (isset($parameters['limit'], $parameters['offset'])) {
            $this->builder
                ->setMaxResults($parameters['limit'])
                ->setFirstResult($parameters['offset'])
            ;
        }
    }

    public function setOrderBy(array $parameters = []): void
    {
        if (isset($parameters['is_updated_at']) && $parameters['is_updated_at'] == true) {
            $this->builder->orderBy($parameters['update_at_by'], 'DESC');
        } else if (isset($parameters['order_by'], $parameters['sort_by'])) {
            $this->builder->orderBy($parameters['order_by'], $parameters['sort_by']);
        }
        
        if (isset($parameters['status_last_changed']) && !empty($parameters['status_last_changed']) && $parameters['status_last_changed'] != null) {
            $this->builder->orderBy('o.statusChangeTime', $parameters['status_last_changed']);
        }

        if (isset($parameters['d_status_last_changed']) && !empty($parameters['d_status_last_changed']) && $parameters['d_status_last_changed'] != null) {
            $this->builder->orderBy('d.statusChangeTime', $parameters['d_status_last_changed']);
        }
    }

    public function enableDebug(): void
    {
        $this->debug = true;
    }

    public function getResults(array $results): array
    {
        if ($this->debug) {
            $query = $this->builder->getQuery();

            $results['sql'] = $query->getSQL();
            $results['parameters'] = $query->getParameters();
        }

        return $results;
    }

    public function getDataById(int $id)
    {
        $data = $this
            ->createQueryBuilder($this->alias)
            ->select([$this->alias])
            ->where(sprintf('%s.id = :id', $this->alias))
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();

        if (!$data) {
            $message = sprintf('Unable to find an active entity object identified by id "%s".', $id);

            throw new RuntimeException($message);
        }

        return current($data);
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder($this->alias)
            ->select([$this->alias])
            ->where(sprintf('%s.id <> :id', $this->alias))
            ->setParameter('id', 0)
        ;

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        $query = clone $this->builder;
        $query->select(sprintf('count(%s.id)', $this->alias));

        try {
            $results['total'] = $query->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        $this->setLimitAndOffset($parameters);

        $results['data'] = $this->builder->getQuery()->getScalarResult();

        return $this->getResults($results);
    }

    public function applyFilters(array $parameters = []): void
    {
        //
    }

    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder($this->alias)
            ->where(sprintf('%s.id > :id', $this->alias))
            ->setParameter('id', 0)
        ;

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }

    public function getDataToExport(array $parameters = []): array
    {
        $this->builder = $this->dataExportBaseBuilder();

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        $query = clone $this->builder;
        $query->select(sprintf('count(%s.id)', $this->alias));

        try {
            $total = $query->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException $e) {
            $total = 0;
        }

        return [
            'total' => (int) $total,
            'data' => $this->builder->getQuery()->getScalarResult(),
        ];
    }

    protected function dataExportBaseBuilder(): QueryBuilder
    {
        return $this
            ->createQueryBuilder($this->alias)
            ->select([$this->alias])
            ->where(sprintf('%s.id <> :id', $this->alias))
            ->setParameter('id', 0)
        ;
    }
}
