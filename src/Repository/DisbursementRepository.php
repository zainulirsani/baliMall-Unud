<?php

namespace App\Repository;

use App\Entity\Disbursement;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Store;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Disbursement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Disbursement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Disbursement[]    findAll()
 * @method Disbursement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DisbursementRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Disbursement::class;
        $this->alias = 'd';

        parent::__construct($registry);
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder($this->alias)
            ->select([
                'd',
                'o.invoice AS o_invoice', 'o.sharedInvoice as o_sharedInvoice', 'o.status AS o_status', 'o.ppk_payment_method as o_ppk_payment_method', 'o.taxType as o_taxType',
                's.name AS s_name',
            ])
            ->leftJoin(Order::class, 'o', 'WITH', 'o.id = d.orderId')
            ->leftJoin(Store::class, 's', 'WITH', 's.id = o.seller')
            ->leftJoin(User::class, 'u', 'WITH', 'u.id = o.buyer')
            ->where('d.id <> :id')
            ->setParameter('id', 0)
        ;

        $this->applyFilters($parameters);

        $query = clone $this->builder;

        $query->select('count(d.id)');

        $results['total'] = $query->getQuery()->getSingleScalarResult();

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        $results['data'] = $this->builder->getQuery()->getScalarResult();

        return $results;
    }

    public function getDataByStoreId(int $id, array $parameters = [])
    {
        $this->builder = $this
            ->createQueryBuilder($this->alias)
            ->select([
                'd.id AS id',
                'd.productFee AS product_fee',
                'd.ppn AS ppn',
                'd.pph AS pph',
                'd.bankFee AS bank_fee',
                'd.managementFee AS management_fee',
                'd.otherFee AS other_fee',
                'd.persentase_ppn AS persentase_ppn',
                'd.persentase_pph AS persentase_pph',
                'd.persentase_bank AS persentase_bank_fee',
                'd.persentase_management AS persentase_management_fee',
                'd.persentase_other AS persentase_other_fee',
                'd.payment_proof AS payment_proof',
                'd.status AS status',
                'd.totalProductPrice AS total_product_price',
                'd.logs as logs',
                'd.rekening_name AS rekening_name',
                'd.bank_name AS bank_name',
                'd.nomor_rekening AS nomor_rekening',
                'd.total AS total',

                'o.id AS order_id',
                'o.invoice AS order_invoice',
                'o.name AS buyer',
                'o.status AS order_status',
                'o.shippingPrice AS shipping_price',

                's.name AS store_name',
                's.address AS store_address',

                'u.firstName AS user_firstName',
                'u.lastName AS user_lastName',
                'u.phoneNumber AS user_phoneNumber',
            ])
            ->leftJoin(Order::class, 'o', 'WITH', 'o.id = d.orderId')
            ->leftJoin(Store::class, 's', 'WITH', 's.id = o.seller')
            ->leftJoin(User::class, 'u', 'WITH', 'u.id = s.user')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ;

            $this->applyFilters($parameters);

        return $this->builder->getQuery()->getScalarResult();
    }

    public function getDataById(int $id)
    {
        $this->builder = $this
            ->createQueryBuilder($this->alias)
            ->select([
                'd.id AS id',
                'd.productFee AS product_fee',
                'd.ppn AS ppn',
                'd.pph AS pph',
                'd.bankFee AS bank_fee',
                'd.managementFee AS management_fee',
                'd.otherFee AS other_fee',
                'd.persentase_ppn AS persentase_ppn',
                'd.persentase_pph AS persentase_pph',
                'd.persentase_bank AS persentase_bank_fee',
                'd.persentase_management AS persentase_management_fee',
                'd.persentase_other AS persentase_other_fee',
                'd.payment_proof AS payment_proof',
                'd.status AS status',
                'd.totalProductPrice AS total_product_price',
                'd.logs as logs',
                'd.rekening_name AS rekening_name',
                'd.bank_name AS bank_name',
                'd.nomor_rekening AS nomor_rekening',
                'd.order_shipping_price AS order_shipping_price',
                'd.total AS total',

                'o.id AS order_id',
                'o.invoice AS order_invoice',
                'o.name AS buyer',
                'o.status AS order_status',
                'o.shippingPrice AS shipping_price',
                'o.treasurer_pph AS treasurer_pph',
                'o.treasurer_ppn AS treasurer_ppn',
                'o.djpReportStatus AS order_djpReportStatus',

                's.name AS store_name',
                's.address AS store_address',
                's.isPKP AS store_pkp',

                'u.firstName AS user_firstName',
                'u.lastName AS user_lastName',
                'u.phoneNumber AS user_phoneNumber',
            ])
            ->leftJoin(Order::class, 'o', 'WITH', 'o.id = d.orderId')
            ->leftJoin(Store::class, 's', 'WITH', 's.id = o.seller')
            ->leftJoin(User::class, 'u', 'WITH', 'u.id = s.user')
            ->where('d.id = :id')
            ->setParameter('id', $id)
            ;

        return $this->builder->getQuery()->getSingleResult();
    }

    public function applyFilters(array $parameters = []): void
    {
        if (isset($parameters['roles']) && is_array($parameters['roles'])) {
            $this->builder
                ->andWhere($this->builder->expr()->in('u.role', ':roles'))
                ->setParameter('roles', $parameters['roles'])
            ;
        }

        if (isset($parameters['status']) && !empty($parameters['status']) && $parameters['status'] != null) {
            if (is_array($parameters['status'])) {
                $this->builder
                    ->andWhere('d.status IN(:status)')
                    ->setParameter('status', array_values($parameters['status']))
                ;
            } else {
                $this->builder
                    ->andWhere('d.status = :status')
                    ->setParameter('status', $parameters['status'])
                ;
            }
        }
        
        if (isset($parameters['tax_type']) && !empty($parameters['tax_type']) && $parameters['tax_type'] != null) {
            if ($parameters['tax_type'] == 'belum_pilih') {
                $this->builder
                    ->andWhere('o.taxType is NULL')
                ;
            } else {
                $this->builder
                    ->andWhere('o.taxType = :tax_type')
                    ->setParameter('tax_type', $parameters['tax_type'])
                ;
            }
        }

        if (isset($parameters['status_order']) && !empty($parameters['status_order']) && $parameters['status_order'] != null) {
            if (is_array($parameters['status'])) {
                $this->builder
                    ->andWhere('o.status IN(:status_order)')
                    ->setParameter('status_order', array_values($parameters['status_order']))
                ;
            } else {
                $this->builder
                    ->andWhere('o.status = :status_order')
                    ->setParameter('status_order', $parameters['status_order'])
                ;
            }
        }

        if (isset($parameters['keywords']) && !empty($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('s.name', ':keywords'),
                    $this->builder->expr()->like('o.sharedInvoice', ':keywords'),
                    $this->builder->expr()->like('o.invoice', ':keywords'),
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        if (isset($parameters['store']) && !empty($parameters['store'])) {
            $this->builder
                ->andWhere('s.id = :store')
                ->setParameter('store', abs($parameters['store']))
            ;
        }

        $dateFilteredBy = 'd.createdAt';
        
        if (isset($parameters['is_updated_at']) && $parameters['is_updated_at'] === true) {
            $dateFilteredBy = 'd.updatedAt';
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
        // dd($parameters);
        // if (isset($parameters['status_last_changed']) && !empty($parameters['status_last_changed']) && $parameters['status_last_changed'] != null) {
        //     $this->builder
        //         ->andWhere('DATE(d.statusChangeTime) = :status_last_changed')
        //         ->setParameter('status_last_changed', $parameters['status_last_changed'])
        //     ;
        // }

        if (isset($parameters['admin_merchant_cabang']) && !empty($parameters['admin_merchant_cabang'])) {
            $this->builder
                ->andWhere('s.provinceId = :provinceId')
                ->setParameter('provinceId', $parameters['admin_merchant_cabang'])
            ;
        }
    }

    public function getDataToExport(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder($this->alias)
            ->select([
                'd',
                'o.sharedInvoice as o_sharedInvoice', 'o.status AS o_status', 'o.invoice AS o_invoice',
                's.name AS s_name',
            ])
            ->leftJoin(Order::class, 'o', 'WITH', 'o.id = d.orderId')
            ->leftJoin(Store::class, 's', 'WITH', 's.id = o.seller')
            ->leftJoin(User::class, 'u', 'WITH', 'u.id = o.buyer')
            ->where('d.id <> :id')
            ->setParameter('id', 0)
        ;

        $this->applyFilters($parameters);

        $query = clone $this->builder;
        $query->select('count(d.id)');

        $results['total'] = $query->getQuery()->getSingleScalarResult();

        $results['data'] = $this->builder->getQuery()->getScalarResult();

        return $results;

    }
}
