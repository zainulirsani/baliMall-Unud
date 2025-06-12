<?php

namespace App\Repository;

use _HumbugBoxa991b62ce91e\Nette\Utils\DateTime;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Store;
use App\Entity\User;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\RuntimeException;

class StoreRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Store::class;
        $this->alias = 's';

        parent::__construct($registry);
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('s')
            ->select(['s', 'u.id AS u_id', 'u.firstName AS u_firstName', 'u.lastName AS u_lastName'])
            ->leftJoin(User::class, 'u', 'WITH', 's.user = u.id')
            ->where('s.id <> :id')
            ->setParameter('id', 0)
        ;

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        $query = clone $this->builder;
        $query->select('count(s.id)');

        try {
            $results['total'] = $query->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        $this->setLimitAndOffset($parameters);

        $results['data'] = $this->builder->getQuery()->getScalarResult();

        return $this->getResults($results);
    }

    public function getDataForSelectOptions(array $parameters = []): array
    {
        $this->builder = $this
            ->createQueryBuilder('s')
            ->select(['s.id as id', 's.name as text'])
            ->where('s.isActive = :status')
            ->setParameter('status', true)
        ;

        if (isset($parameters['search']) && !empty($parameters['search'])) {
            $this->builder
                ->andWhere('s.name LIKE :search')
                ->setParameter('search', '%'.$parameters['search'].'%')
            ;
        }

        $this->setOrderBy($parameters);

        return $this->builder->getQuery()->getScalarResult();
    }

    public function getDataWithOwnerById(int $storeId)
    {
        $store = $this
            ->createQueryBuilder('s')
            ->select(['s', 'u.id AS u_id', 'u.firstName AS u_firstName', 'u.lastName AS u_lastName',
                'u.npwp AS u_npwp','u.NpwpName AS u_npwpName', 'u.npwpFile AS u_npwpFile', 'u.suratIjinFile AS u_suratIjin',
                'u.dokumenFile AS u_dokumenFile', 'u.nik AS u_nik', 'u.dob AS u_dob', 'u.gender AS u_gender',
                'u.photoProfile AS u_photoProfile', 'u.bannerProfile AS u_bannerProfile','u.ktpFile AS u_ktpFile',
                'u.email AS u_email', 'u.phoneNumber AS u_phoneNumber', 'u.user_signature as u_user_signature', 'u.user_stamp as u_user_stamp',
            ])
            ->leftJoin(User::class, 'u', 'WITH', 's.user = u.id')
            ->where('s.id = :store_id')
            ->setParameter('store_id', $storeId)
            ->getQuery()
            ->getScalarResult();

        if (!$store) {
            $message = sprintf('Unable to find an active store object identified by id "%s".', $storeId);

            throw new RuntimeException($message);
        }

        return current($store);
    }

    public function applyFilters(array $parameters = []): void
    {
        if (isset($parameters['status']) && !empty($parameters['status'])) {
            // $this->builder
            //     ->andWhere('s.status = :status')
            //     ->setParameter('status', $parameters['status'])
            // ;
            $this->builder
                ->andWhere('s.status IN(:status)')
                ->setParameter('status', array_values($parameters['status']))
            ;
        }

        if (isset($parameters['business_criteria']) && !empty($parameters['business_criteria'])) {
            $this->builder
                ->andWhere('s.businessCriteria = :business_criteria')
                ->setParameter('business_criteria', $parameters['business_criteria'])
            ;
        }

        if (isset($parameters['verified']) && !empty($parameters['verified'])) {
            $this->builder
                ->andWhere('s.isVerified = :verified')
                ->setParameter('verified', $parameters['verified'] === 'verified')
            ;
        }

        if (isset($parameters['keywords']) && !empty($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('s.name', ':keywords'),
                    $this->builder->expr()->like('u.firstName', ':keywords'),
                    $this->builder->expr()->like('u.lastName', ':keywords')
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        $dateFilteredBy = 's.createdAt';

        if (isset($parameters['is_updated_at']) && $parameters['is_updated_at'] === true) {
            $dateFilteredBy = 's.updatedAt';
        }

        if (isset($parameters['date_start']) && !empty($parameters['date_start'])) {
            $this->builder
                ->andWhere(sprintf('%s >= :date_start', $dateFilteredBy))
                ->setParameter('date_start', $parameters['date_start'])
            ;
        }

        if (isset($parameters['date_end']) && !empty($parameters['date_end'])) {
            $this->builder
                ->andWhere(sprintf('%s <= :date_end', $dateFilteredBy))
                ->setParameter('date_end', $parameters['date_end'])
            ;
        }

        if (isset($parameters['year']) && !empty($parameters['year'])) {
            $this->builder
                ->andWhere(sprintf('YEAR(%s) = :year', $dateFilteredBy))
                ->setParameter('year', abs($parameters['year']))
            ;
        }

        if (isset($parameters['admin_merchant_cabang']) && !empty($parameters['admin_merchant_cabang'])) {
            $this->builder
                ->andWhere('s.provinceId = :provinceId')
                ->setParameter('provinceId', $parameters['admin_merchant_cabang'])
            ;
        }
    }

    public function getDataForMerchantByStateChart(array $parameters = [])
    {
        $query = $this
            ->createQueryBuilder('s')
            ->select(['s.city', 'count(s.id) as total'])
            ->where('s.id <> :id')
            ->setParameter('id', 0)
        ;

        $query
            ->andWhere($query->expr()->isNotNull('s.user'))
        ;

        if (isset($parameters['year']) && !empty($parameters['year'])) {
            $query
                ->andWhere('YEAR(s.createdAt) = :year')
                ->setParameter('year', abs($parameters['year']))
            ;
        }

        if (isset($parameters['admin_merchant_province']) && !empty($parameters['admin_merchant_province'])) {
            $query
                ->andWhere('s.provinceId = :province')
                ->setParameter('province', abs($parameters['admin_merchant_province']))
            ;
        }

        return $query->groupBy('s.city')->getQuery()->getResult();
    }

    protected function dataExportBaseBuilder(): QueryBuilder
    {
        return $this
            ->createQueryBuilder('s')
            ->select(['s', 'u.firstName as u_firstName', 'u.lastName as u_lastName', 'u.nik as u_nik', 'u.npwp as u_npwp', 'u.phoneNumber as u_phoneNumber'])
            ->leftJoin(User::class, 'u', 'WITH', 'u.id = s.user')
            ->where('s.id <> :id')
            ->setParameter('id', 0)
        ;
    }

    public function getTotalRegisteredMerchantByDate($date=null): int {

        if (is_null($date)) {
            $date = new DateTime();
        }
        return $this
            ->createQueryBuilder('s')
            ->select('COUNT(s.id) as counter')
            ->where('DATE(s.createdAt) = :date')
            ->andWhere('s.registeredNumber is not null')
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getStoreByProductCategory(array $parameters)
    {
        $q= $this->createQueryBuilder('s')
            ->select(['s'])
            ->leftJoin(Product::class, 'p', 'WITH', 'p.store = s.id')
            ->where('p.category IN (:categoryId)')
            ->andWhere('p.status = :status')
            ->setParameter('categoryId', $parameters['category'])
            ->setParameter('status', 'publish');

        if (isset($parameters['name'])) {
            $q->andWhere($q->expr()->orX(
                $q->expr()->like('s.name', ':name')
            ))->setParameter('name', '%'.$parameters['name'].'%');
        }

        if (isset($parameters['page'], $parameters['per_page'])) {
            $q->setMaxResults($parameters['per_page'])
                ->setFirstResult($parameters['per_page'] * ($parameters['page'] - 1));
        }

        return $q->getQuery()->getResult();

    }

    public function getAllStores():array
    {
        $q = $this
            ->createQueryBuilder('s')
            ->select(['s.id as s_id', 's.name as s_name', 's.status as s_status'])
            ->where('s.id <> :id')
            ->setParameter('id', 0)
        ;

        $q->andWhere('s.user IS NOT NULL');

        return $q->getQuery()->getArrayResult();
    }
}
