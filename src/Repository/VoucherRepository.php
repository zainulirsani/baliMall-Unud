<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\Voucher;
use App\Entity\VoucherUsedLog;
use Doctrine\Persistence\ManagerRegistry;

class VoucherRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Voucher::class;
        $this->alias = 'v';

        parent::__construct($registry);
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('v')
            ->select(['v', 'vul.id as vul_id', 'vul.orderSharedId as vul_orderSharedId', 'vul.createdAt as vul_createdAt', 'u.firstName as u_firstName', 'u.lastName as u_lastName'])
            ->leftJoin(VoucherUsedLog::class, 'vul', 'WITH', 'vul.voucherId = v.id')
            ->leftJoin(User::class, 'u', 'WITH', 'u.id = vul.userId')
            ->leftJoin(Order::class, 'o', 'WITH', 'o.id = vul.orderId')
            ->leftJoin(Store::class, 's', 'WITH', 's.id = o.seller')
            ->where('v.id <> :id')
            ->andWhere('v.status <> :status')
            ->setParameter('id', 0)
            ->setParameter('status', 'deleted')
        ;

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        // $query = clone $this->builder;
        // $query->select('count(v.id)');

        if (isset($parameters['is_used']) && $parameters['is_used'] === 'yes') {
            $this->builder->groupBy('vul.voucherId');
        } else {
            $this->builder->groupBy('v.code');
        }

        $results['total'] = count($this->builder->getQuery()->getScalarResult());
        $this->setLimitAndOffset($parameters);
        $results['data'] = $this->builder->getQuery()->getScalarResult();
        return $this->getResults($results);
    }

    public function getDatatoExport(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('v')
            ->select(['v', 'vul.id as vul_id', 'vul.orderSharedId as vul_orderSharedId', 'vul.createdAt as vul_createdAt', 'u.firstName as u_firstName', 'u.lastName as u_lastName', 'o.sharedInvoice as o_sharedInvoice', 'o.status as o_status'])
            ->leftJoin(VoucherUsedLog::class, 'vul', 'WITH', 'vul.voucherId = v.id')
            ->leftJoin(User::class, 'u', 'WITH', 'u.id = vul.userId')
            ->leftJoin(Order::class, 'o', 'WITH', 'o.id = vul.orderId')
            ->leftJoin(Store::class, 's', 'WITH', 's.id = o.seller')
            ->where('v.id <> :id')
            ->andWhere('v.status <> :status')
            ->setParameter('id', 0)
            ->setParameter('status', 'deleted')
        ;

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        // $query = clone $this->builder;
        // $query->select('count(v.id)');

        if (isset($parameters['is_used']) && $parameters['is_used'] === 'yes') {
            $this->builder->groupBy('vul.voucherId');
        } else {
            $this->builder->groupBy('v.code');
        }

        $results['total'] = count($this->builder->getQuery()->getScalarResult());
        $results['data'] = $this->builder->getQuery()->getScalarResult();

        return $this->getResults($results);
    }

    public function applyFilters(array $parameters = []): void
    {
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

        if (isset($parameters['keywords']) && !empty($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('v.code', ':keywords'),
                    $this->builder->expr()->like('v.name', ':keywords'),
                    $this->builder->expr()->like('v.description', ':keywords')
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
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

    public function checkForValidity(string $code)
    {
        $query = $this
            ->createQueryBuilder('v')
            ->select(['v', 'vul.id as vul_id'])
            ->leftJoin(VoucherUsedLog::class, 'vul', 'WITH', 'vul.voucherId = v.id')
            ->where('v.id <> :id')
            ->andWhere('v.code = :code')
            ->andWhere('v.status = :status')
            ->andWhere('v.baseType = :base_type')
            ->andWhere('v.startAt <= :start_at')
            ->andWhere('v.endAt >= :end_at')
            ->setParameter('id', 0)
            ->setParameter('code', $code)
            ->setParameter('status', 'publish')
            ->setParameter('base_type', Voucher::BASE_TYPE_COUPON)
            ->setParameter('start_at', date('Y-m-d H:i:s'))
            ->setParameter('end_at', date('Y-m-d H:i:s'))
        ;

        $query->andWhere($query->expr()->isNull('vul.id'));

        $data = $query->getQuery()->getScalarResult();

        return $data ? current($data) : [];
    }

    public function findUnusedVoucher(int $id)
    {
        $query = $this
            ->createQueryBuilder('v')
            ->leftJoin(VoucherUsedLog::class, 'vul', 'WITH', 'vul.voucherId = v.id')
            ->where('v.id = :id')
            ->setParameter('id', $id)
        ;

        $query->andWhere($query->expr()->isNull('vul.id'));

        $data = $query->getQuery()->getResult();

        return $data ? current($data) : null;
    }

    public function findUnusedVouchers()
    {
        $query = $this
            ->createQueryBuilder('v')
            ->leftJoin(VoucherUsedLog::class, 'vul', 'WITH', 'vul.voucherId = v.id')
            ->where('v.id <> :id')
            ->setParameter('id', 0)
        ;

        $query->andWhere($query->expr()->isNull('vul.id'));

        return $query->getQuery()->getResult();
    }
}
