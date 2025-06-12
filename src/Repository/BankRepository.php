<?php

namespace App\Repository;

use App\Entity\Bank;
use Doctrine\Persistence\ManagerRegistry;

class BankRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Bank::class;
        $this->alias = 'b';

        parent::__construct($registry);
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('b')
            ->select(['b'])
        ;

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        $results['total'] = count($this->builder->getQuery()->getScalarResult());
        $this->setLimitAndOffset($parameters);
        $results['data'] = $this->builder->getQuery()->getScalarResult();
        return $this->getResults($results);
    }

    public function applyFilters(array $parameters = []): void
    {
        if (isset($parameters['keywords']) && !empty($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    // $this->builder->expr()->like('b.code', ':keywords'),
                    $this->builder->expr()->like('b.name', ':keywords'),
                    // $this->builder->expr()->like('b.description', ':keywords')
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }
        if (isset($parameters['status']) && !empty($parameters['status'])) {
            $this->builder
                ->andWhere('b.is_Active = :status')
                ->setParameter('status', $parameters['status'] === 'active')
            ;
        }
        if (isset($parameters['is_used'])) {
            if ($parameters['is_used'] === 'yes') {
                if (isset($parameters['is_used_start_at']) && !empty($parameters['is_used_start_at'])) {
                    $this->builder
                        ->andWhere('vul.createdAt >= :is_used_start_at')
                        ->setParameter('is_used_start_at', $parameters['is_used_start_at'])
                    ;
                }

                if (isset($parameters['is_used_end_at']) && !empty($parameters['is_used_end_at'])) {
                    $this->builder
                        ->andWhere('vul.createdAt <= :is_used_end_at')
                        ->setParameter('is_used_end_at', $parameters['is_used_end_at'])
                    ;
                }

                $this->builder
                    ->andWhere($this->builder->expr()->isNotNull('vul.id'))
                    ->groupBy('v.code')
                ;
            } elseif ($parameters['is_used'] === 'no') {
                $this->builder->andWhere($this->builder->expr()->isNull('vul.id'));
            }
        }

        if (isset($parameters['type']) && !empty($parameters['type'])) {
            $this->builder
                ->andWhere('v.type = :type')
                ->setParameter('type', $parameters['type'])
            ;
        }

        if (isset($parameters['base_type']) && !empty($parameters['base_type'])) {
            $this->builder
                ->andWhere('v.baseType = :base_type')
                ->setParameter('base_type', $parameters['base_type'])
            ;
        }

        if (isset($parameters['status']) && !empty($parameters['status'])) {
            $this->builder
                ->andWhere('v.status = :status')
                ->setParameter('status', $parameters['status'])
            ;
        }

        if (isset($parameters['start_at']) && !empty($parameters['start_at'])) {
            $this->builder
                ->andWhere('v.startAt >= :start_at')
                ->setParameter('start_at', $parameters['start_at'])
            ;
        }

        if (isset($parameters['end_at']) && !empty($parameters['end_at'])) {
            $this->builder
                ->andWhere('v.endAt <= :end_at')
                ->setParameter('end_at', $parameters['end_at'])
            ;
        }

        if (isset($parameters['date_start']) && !empty($parameters['date_start'])) {
            $this->builder
                ->andWhere('v.startAt >= :date_start')
                ->setParameter('date_start', sprintf('%s 00:00:00', $parameters['date_start']))
            ;
        }

        if (isset($parameters['date_end']) && !empty($parameters['date_end'])) {
            $this->builder
                ->andWhere('v.endAt <= :date_end')
                ->setParameter('date_end', sprintf('%s 23:59:59', $parameters['date_end']))
            ;
        }

        if (isset($parameters['admin_merchant_cabang']) && !empty($parameters['admin_merchant_cabang'])) {
            $this->builder
                ->andWhere('s.provinceId = :provinceId')
                ->setParameter('provinceId', $parameters['admin_merchant_cabang'])
            ;
        }
    }
}
