<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Bni;
use App\Entity\BniDetail;
use App\Entity\OrderComplaint;
use App\Entity\OrderNegotiation;
use App\Entity\OrderPayment;
use App\Entity\OrderProduct;
use App\Entity\OrderShippedFile;
use App\Entity\Disbursement;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Qris;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\Doku;
use App\Entity\VirtualAccount;
use App\Entity\VoucherUsedLog;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Security\Core\Exception\RuntimeException;

class OrderRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Order::class;
        $this->alias = 'o';
        parent::__construct($registry);
    }

    public function getInvoiceListForBuyer(User $user, $sharedId = null)
    {
        $status = $user->getRole() === 'ROLE_USER_GOVERNMENT' ? 'pending_payment' : 'pending';
        $query = $this
            ->createQueryBuilder('o')
            ->select('o.id', 'o.invoice', 'o.total + o.shippingPrice AS total', 'o.sharedId', 'o.isB2gTransaction', 'o.negotiationStatus', 'o.treasurerPphNominal','o.treasurer_ppn_nominal')
            ->leftJoin(OrderPayment::class, 'op', 'WITH', 'o.id = op.order')
            ->leftJoin(User::class, 'u', 'WITH', 'o.buyer = u.id')
            ->where('o.id <> :id')
            ->andWhere('o.status = :status')
//            ->andWhere('u.lkppInstanceId = :instanceId')
            ->setParameter('id', 0)
            ->setParameter('status', $status)
//            ->setParameter('instanceId', $user->getLkppInstanceId())
        ;
        
        if ($user->getRole() === 'ROLE_USER_GOVERNMENT' && $user->getSubRole() === 'TREASURER') {
            if (!empty($sharedId)) {
                $query->andWhere('o.sharedId = :sharedId')
                      ->setParameter('sharedId', $sharedId);
            }
        } else {
            $query->andWhere('o.buyer = :buyer')
                  ->setParameter('buyer', $user);
        }


        //$query->andWhere($query->expr()->in('o.status', ['pending', 'pending_payment']));
        $query->andWhere($query->expr()->isNull('op.id'));
        $query->andWhere($query->expr()->isNotNull('o.sharedId'));

        return $query->getQuery()->getArrayResult();
    }

    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder('o')
            ->select(['o', 'ow.user_signature as ow_user_signature', 'ow.user_stamp as ow_user_stamp', 'ow.firstName as ow_firstName', 'ow.lastName as ow_lastName', 's.name as s_name', 's.isPKP as s_isPKP', 'u.firstName as u_firstName', 'u.lastName as u_lastName', 'u.role as u_role', 'u.subRole as u_subRole'])
            ->leftJoin(User::class, 'u', 'WITH', 'o.buyer = u.id')
            ->leftJoin(Store::class, 's', 'WITH', 'o.seller = s.id')
            ->leftJoin(User::class, 'ow', 'WITH', 'ow.id = s.user')
            ->where('o.id <> :id')
            ->setParameter('id', 0)
        ;

        if (isset($parameters['buyer'])) {

            $this->builder
                ->andWhere('o.buyer = :buyer')
                ->setParameter('buyer', $parameters['buyer']);


//            $lkppInstanceId = ($parameters['buyer'])->getLkppInstanceId() ?? 0;

//            $this->builder
//                ->andWhere('u.lkppInstanceId = :instanceId')
//                ->setParameter('instanceId', $lkppInstanceId)
//            ;

//            if (($parameters['buyer'])->getLkppRole() === 'PPK') {
//                $this->builder
//                    ->andWhere('o.status = :status')
//                    ->setParameter('status', 'pending_approve')
//                    ;
//            }

            if (isset($parameters['version']) && $parameters['version'] === 'v2') {
                $this->builder
                    ->andWhere($this->builder->expr()->isNotNull('o.sharedId'))
                    ->groupBy('o.sharedId')
                ;
            }
        } elseif (isset($parameters['seller'])) {
            $this->builder
                ->andWhere('o.seller = :seller')
                ->setParameter('seller', $parameters['seller'])
            ;
        }

        if (isset($parameters['status'])) {
            $this->builder
                ->andWhere('o.status = :status')
                ->setParameter('status', $parameters['status'])
            ;
        }

        if (isset($parameters['status_multiple'])) {
            $this->builder
                ->andWhere("o.status IN(:status_m)")
                ->setParameter('status_m', array_values($parameters['status_multiple']))
            ;
        }

        if (isset($parameters['key_invoice'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('o.invoice', ':key_invoice'),
                    $this->builder->expr()->like('o.sharedInvoice', ':key_invoice'),
                ))->setParameter('key_invoice', '%'.$parameters['key_invoice'].'%')
            ;
        }

        if (isset($parameters['ppk_user'])) {
            $this->builder
                ->andWhere('o.ppkId = :ppkId')
                // ->andWhere('o.buyer <> :ppkBuyer')
                ->setParameter('ppkId', $parameters['ppk_user'])
                // ->setParameter('ppkBuyer', $parameters['ppk_user_collec'])
            ;
        }

        if (isset($parameters['filter_status_ppk'])) {
            if ($parameters['filter_status_ppk'] == 'sudah') {
                $this->builder
                    ->andWhere('o.isApprovedPPK = :is_approve')
                    ->setParameter('is_approve', true);
            } else {
                $this->builder
                ->andWhere($this->builder->expr()->isNull('o.isApprovedPPK'));
            } 
        }

        if (isset($parameters['filter_status_treasurer'])) {
            if ($parameters['filter_status_treasurer'] == 'sudah') {
                $status_treasurer = ['payment_process','paid'];
            } else {
                $status_treasurer = ['tax_invoice','pending_payment'];
            } 

            $this->builder
                ->andWhere("o.status IN(:status_treasurer)")
                ->setParameter('status_treasurer', array_values($status_treasurer))
            ;
        }

        if (isset($parameters['treasurer_user'])) {
            $this->builder
                ->andWhere('o.treasurerId = :treasurerId')
                ->setParameter('treasurerId', $parameters['treasurer_user'])
            ;
        }

        if (isset($parameters['exclude_status'])) {
            $this->builder
                ->andWhere($this->builder->expr()->notIn('o.status', ':exclude_status'))
                ->setParameter('exclude_status', $parameters['exclude_status'])
            ;
        }

        if (isset($parameters['type_order'])) {
            $this->builder
                ->andWhere('o.type_order = :type_order')
                ->setParameter('type_order', $parameters['type_order'])
            ;
        }

        if (isset($parameters['keywords'])) {
            if (isset($parameters['buyer'])) {
                $this->builder
                    ->andWhere($this->builder->expr()->orX(
                        $this->builder->expr()->like('o.invoice', ':keywords'),
                        $this->builder->expr()->like('o.sharedInvoice', ':keywords'),
                        $this->builder->expr()->like('o.jobPackageName', ':keywords'),
                        $this->builder->expr()->like('s.name', ':keywords'),
                        $this->builder->expr()->like('o.invoice', ':keywords'),
                        $this->builder->expr()->like('o.sharedInvoice', ':keywords'),
                        $this->builder->expr()->like('o.dokuInvoiceNumber', ':keywords'),
                        $this->builder->expr()->like('o.status', ':keywords'),
                        $this->builder->expr()->like('o.taxType', ':keywords'),
                    ))->setParameter('keywords', '%'.$parameters['keywords'].'%')
                ;
            }else if (isset($parameters['seller'])) {
                $this->builder
                    ->andWhere($this->builder->expr()->orX(
                        $this->builder->expr()->like('o.invoice', ':keywords'),
                        $this->builder->expr()->like('o.sharedInvoice', ':keywords'),
                        $this->builder->expr()->like('o.jobPackageName', ':keywords')
                    ))->setParameter('keywords', '%'.$parameters['keywords'].'%')
                ;
            }
        }

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }

    public function getOrderDetail(int $orderId, array $parameters = [])
    {
        $query = $this
            ->createQueryBuilder('o')
            ->select([
                'o', 'od', 'op', 'oc', 's.slug as s_slug', 's.isPKP as s_pkp', 's.umkm_category as s_umkm_category',
                's.name as s_name', 's.provinceId as s_provinceId','s.address as s_address', 'ow.id as s_ow_id', 'u.id as u_id',
                'u.firstName as u_firstName', 'u.lastName as u_lastName', 'u.role as u_role', 'u.subRole as u_subRole', 'u.ppName as u_ppName',
                'u.ppkName as u_ppkName', 'u.nip as u_nip', 'u.lkppKLDI as u_lkppKLDI', 'u.lkppWorkUnit as u_lkppWorkUnit',
                'u.lkppRole as u_lkppRole', 'u.lkppWorkunitName as u_lkppWorkunitName', 'u.npwp as u_npwp', 'u.user_signature as u_user_signature', 'u.user_stamp as u_user_stamp',
                'ow.photoProfile as s_photo', 'ow.phoneNumber as s_phone_number', 'ow.firstName as s_firstName',
                'ow.lastName as s_lastName', 'ow.email AS ow_email', 'ow.npwp as ow_npwp', 'ow.user_signature as ow_user_signature', 'ow.user_stamp as ow_user_stamp', 'd','bn.requestId as bn_requestId',
                'ppk.satkerId as ppk_satkerId', 'ppk.vaBni as ppk_vaBni'
            ])
            ->leftJoin(OrderPayment::class, 'op', 'WITH', 'o.id = op.order')
            ->leftJoin(OrderComplaint::class, 'oc', 'WITH', 'o.id = oc.order')
            ->leftJoin(Doku::class, 'od', 'WITH', 'o.dokuInvoiceNumber = od.invoice_number')
            ->leftJoin(BniDetail::class, 'b', 'WITH', 'o.id = b.order_id')
            ->leftJoin(Bni::class, 'bn', 'WITH', 'bn.id = b.bni_trx_id')
            ->leftJoin(Disbursement::class, 'd', 'WITH', 'o.id = d.orderId')
            ->leftJoin(User::class, 'u', 'WITH', 'o.buyer = u.id')
            ->leftJoin(Store::class, 's', 'WITH', 'o.seller = s.id')
            ->leftJoin(User::class, 'ow', 'WITH', 'ow.id = s.user')
            ->leftJoin(User::class, 'ppk', 'WITH', 'ppk.id = o.ppkId')
            ->where('o.id = :id')
            ->setParameter('id', $orderId)
        ;

        if (isset($parameters['seller'])) {
            $query
                ->andWhere('o.seller = :seller')
                ->setParameter('seller', $parameters['seller'])
            ;
        }

        if (isset($parameters['buyer'])) {
            $query
                ->andWhere('o.buyer = :buyer')
                ->setParameter('buyer', $parameters['buyer'])
            ;

//            $query
//                ->andWhere('u.lkppInstanceId = :instanceId')
//                ->setParameter('instanceId', ($parameters['buyer'])->getLkppInstanceId());
        }

        $order = $query->getQuery()->getScalarResult();
        $order = $order ? current($order) : [];

        if (!$order) {
            $message = sprintf('Unable to find an active item object identified by id "%s".', $orderId);

            throw new RuntimeException($message);
        }

        $order['o_products'] = $this->getOrderProducts($order['o_id']);
        $order['o_complaint'] = $this->getOrderComplaint($order['o_id']);
        $order['o_negotiatedProducts'] = $this->getOrderNegotiationProducts($order['o_id']);
        $order['o_shippedFiles'] = $this->getOrderShippedFiles($order['o_id']);
        $order['o_qrisPayment'] = null;

        if (isset($order['o_sharedInvoice']) && !empty($order['o_sharedInvoice'])) {
            $order['o_qrisPayment'] = $this->getQRISPaymentDetail($order['o_sharedInvoice']);
        }

        return $order;
    }

    public function getOrderShippedFiles(int $orderId): array
    {
        $query = $this
            ->createQueryBuilder($this->alias)
            ->select(['os'])
            ->leftJoin(OrderShippedFile::class, 'os', 'WITH', 'os.order = o.id')
            ->where('o.id = :order_id')
            ->setParameter('order_id', $orderId)
        ;

        return $query->getQuery()->getScalarResult();
    }

    public function getOrderProducts(int $orderId): array
    {
        $query = $this
            ->createQueryBuilder($this->alias)
            ->select(['op', 's.name as s_name', 's.isPKP as s_pkp', 's.address as s_address','s.umkm_category as s_umkm_category', 's.slug as s_slug', 's.rekeningName AS rekening_name', 's.bankName AS bank_name', 's.nomorRekening AS nomor_rekening', 'ow.id as ow_id', 'p.id as p_id', 'p.name as p_name', 'p.slug as p_slug', 'p.price as p_price', 'p.unit as p_unit', 'p.category as p_category', 'p.sku as p_sku' ,'pc.name as pc_name' ,'o.isB2gTransaction as o_isB2gTransaction'])
            ->leftJoin(OrderProduct::class, 'op', 'WITH', 'op.order = o.id')
            ->leftJoin(Product::class, 'p', 'WITH', 'p.id = op.product')
            ->leftJoin(ProductCategory::class, 'pc', 'WITH', 'pc.id = p.category')
            ->leftJoin(Store::class, 's', 'WITH', 's.id = p.store')
            ->leftJoin(User::class, 'ow', 'WITH', 'ow.id = s.user')
            ->where('o.id = :order_id')
            ->setParameter('order_id', $orderId)
            ->groupBy('p.id')
        ;

        return $query->getQuery()->getScalarResult();
    }

    public function getOrderComplaint(int $orderId): array
    {
        $query = $this
            ->createQueryBuilder($this->alias)
            ->select(['oc'])
            ->leftJoin(OrderComplaint::class, 'oc', 'WITH', 'oc.order = o.id')
            ->where('o.id = :order_id')
            ->setParameter('order_id', $orderId)
        ;

        return $query->getQuery()->getScalarResult();
    }

    public function getSingleOrderProduct(int $orderId, int $productId)
    {
        $query = $this
            ->createQueryBuilder($this->alias)
            ->select(['op'])
            ->leftJoin(OrderProduct::class, 'op', 'WITH', 'op.order = o.id')
            ->leftJoin(Product::class, 'p', 'WITH', 'p.id = op.product')
            ->where('o.id = :order_id')
            ->andWhere('op.product = :product_id')
            ->setParameter('order_id', $orderId)
            ->setParameter('product_id', $productId)
        ;

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return false;
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('o')
            ->select(['o', 's.name as s_name', 'u.firstName as u_firstName', 'u.lastName as u_lastName', 'u.role as u_role','u.subRole as u_subRole', 's.status as s_stts','s.id as s_id'])
            ->leftJoin(User::class, 'u', 'WITH', 'o.buyer = u.id')
            ->leftJoin(Store::class, 's', 'WITH', 'o.seller = s.id')
            ->where('o.id <> :id')
            ->setParameter('id', 0)
        ;

        if (isset($parameters['version']) && !empty($parameters['version']) && $parameters['version'] === 'v2') {
            $this->builder->andWhere($this->builder->expr()->isNotNull('o.sharedId'));
        }


        if (isset($parameters['role'])) {
            if ($parameters['role'] == 'buyer') {
                $this->builder->andWhere('o.isB2gTransaction = :id_t')
                ->setParameter('id_t', 0);
            }else if ($parameters['role'] == 'government') {
                $this->builder->andWhere('o.isB2gTransaction = :id_t')
                ->setParameter('id_t', 1);
            }
        }

        if (isset($parameters['seller'])) {
            $this->builder
                ->andWhere('o.seller = :seller')
                ->setParameter('seller', $parameters['seller'])
            ;
        }

        if (isset($parameters['buyer'])) {
            $this->builder
                ->andWhere('o.buyer = :buyer')
                ->setParameter('buyer', $parameters['buyer'])
            ;
        }
        // dd($parameters);
        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

//        $query = clone $this->builder;
//        $query->select('count(o.id)');
//
//        try {
//            $results['total'] = $query->getQuery()->getSingleScalarResult();
//        } catch (NonUniqueResultException | NoResultException $e) {
//        }

        if (isset($parameters['version']) && $parameters['version'] === 'v2') {
            $this->builder->groupBy('o.sharedId');
        }

        $results['total'] = count($this->builder->getQuery()->getScalarResult());

        $this->setLimitAndOffset($parameters);

        $results['data'] = $this->builder->getQuery()->getScalarResult();
        return $this->getResults($results);
    }

    public function applyFilters(array $parameters = []): void
    {
        if (isset($parameters['status']) && !empty($parameters['status']) && $parameters['status'] != null) {
            if (is_array($parameters['status'])) {
                $this->builder
                    ->andWhere('o.status IN(:status)')
                    ->setParameter('status', array_values($parameters['status']))
                ;
            } else {
                $this->builder
                    ->andWhere('o.status = :status')
                    ->setParameter('status', $parameters['status'])
                ;
            }
        }

        if (isset($parameters['status_djp']) && !empty($parameters['status_djp']) && $parameters['status_djp'] != null) {
            
            if ($parameters['status_djp'] == 'djp_report_not_send') {
                $this->builder
                    ->andWhere('o.djpReportStatus is NULL')
                    ->andWhere('o.taxType = :tipe_pajak')
                    ->setParameter('tipe_pajak', 58)
                ;
            } else {
                $this->builder
                    ->andWhere('o.djpReportStatus = :status_djp')
                    ->andWhere('o.taxType = :tipe_pajak')
                    ->setParameter('status_djp', $parameters['status_djp'])
                    ->setParameter('tipe_pajak', 58)
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

        if (isset($parameters['keywords']) && !empty($parameters['keywords']) && $parameters['keywords'] != null) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('o.invoice', ':keywords'),
                    $this->builder->expr()->like('o.sharedInvoice', ':keywords'),
                    $this->builder->expr()->like('s.name', ':keywords'),
                    $this->builder->expr()->like('u.firstName', ':keywords'),
                    $this->builder->expr()->like('u.lastName', ':keywords'),
                    $this->builder->expr()->like('o.invoice', ':keywords'),
                    $this->builder->expr()->like('o.sharedInvoice', ':keywords'),
                    $this->builder->expr()->like('o.dokuInvoiceNumber', ':keywords'),
                    $this->builder->expr()->like('o.status', ':keywords'),
                    $this->builder->expr()->like('o.taxType', ':keywords'),
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        $dateFilteredBy = 'o.createdAt';
        
        if (isset($parameters['is_updated_at']) && $parameters['is_updated_at'] === true) {
            $dateFilteredBy = 'o.updatedAt';
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

        if (isset($parameters['roles']) && is_array($parameters['roles']) && $parameters['roles'] != null) {
            $this->builder
                ->andWhere($this->builder->expr()->in('u.role', ':roles'))
                ->setParameter('roles', $parameters['roles'])
            ;
        }

        if (isset($parameters['store']) && !empty($parameters['store']) && $parameters['store'] != null) {
            $this->builder
                ->andWhere('o.seller = :store')
                ->setParameter('store', abs($parameters['store']))
            ;
        }

        // if (isset($parameters['year']) && !empty($parameters['year']) && $parameters['year'] != null) {
        //     $this->builder
        //         ->andWhere('YEAR(s.createdAt) = :year')
        //         ->setParameter('year', abs($parameters['year']))
        //     ;
        // }

        // if (isset($parameters['status_last_changed']) && !empty($parameters['status_last_changed']) && $parameters['status_last_changed'] != null) {
        //     $this->builder
        //         ->andWhere('DATE(o.statusChangeTime) = :status_last_changed')
        //         ->setParameter('status_last_changed', $parameters['status_last_changed'])
        //     ;
        // }

        if (isset($parameters['admin_merchant_cabang']) && !empty($parameters['admin_merchant_cabang'])) {
            $this->builder
                ->andWhere('s.provinceId = :provinceId')
                ->setParameter('provinceId', $parameters['admin_merchant_cabang'])
            ;
        }
        
        if (isset($parameters['ppk_payment_method']) && !empty($parameters['ppk_payment_method'])) {
            $this->builder
                ->andWhere('o.ppk_payment_method = :ppk_payment_method')
                ->setParameter('ppk_payment_method', $parameters['ppk_payment_method'])
            ;
        }
    }

    public function getTotalProductSoldForStore(Store $store): array
    {
        $totalB2G = $totalRegular = 0;
        $baseQuery = $this
            ->createQueryBuilder('o')
            ->select(['count(o.id) AS total_order'])
            ->where('o.id <> :id')
            ->andWhere('o.seller = :seller')
            ->setParameter('id', 0)
            ->setParameter('seller', $store)
        ;

        $queryB2G = clone $baseQuery;
        $queryB2G->andWhere('o.status IN (\'paid\')');

        $queryRegular = clone $baseQuery;
        $queryRegular->andWhere('o.status IN (\'paid\', \'confirmed\', \'processed\', \'shipped\', \'received\')');

        try {
            $b2g = $queryB2G->getQuery()->getSingleResult();
            $regular = $queryRegular->getQuery()->getSingleResult();

            $totalB2G = $b2g['total_order'];
            $totalRegular = $regular['total_order'];
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return [
            'total_order' => $totalB2G + $totalRegular,
        ];
    }

    public function checkForUnpaidPendingOrder(array $parameters = [])
    {
        $query = $this
            ->createQueryBuilder('o')
            ->where('o.id <> :id')
            ->andWhere('o.status = :status')
            ->setParameter('id', 0)
            ->setParameter('status', 'pending')
        ;

        if (isset($parameters['today']) && !empty($parameters['today'])) {
            $query
                ->andWhere('o.createdAt <= :created')
                ->setParameter('created', $parameters['today'])
            ;
        }

        return $query->getQuery()->getResult();
    }

    public function getDataForTransactionPerMonthChart(string $type, array $parameters = [])
    {
        $select = [
            'IFNULL(SUM(MONTH(o.created_at) = 1), 0) as "Jan"',
            'IFNULL(SUM(MONTH(o.created_at) = 2), 0) as "Feb"',
            'IFNULL(SUM(MONTH(o.created_at) = 3), 0) as "Mar"',
            'IFNULL(SUM(MONTH(o.created_at) = 4), 0) as "Apr"',
            'IFNULL(SUM(MONTH(o.created_at) = 5), 0) as "May"',
            'IFNULL(SUM(MONTH(o.created_at) = 6), 0) as "Jun"',
            'IFNULL(SUM(MONTH(o.created_at) = 7), 0) as "Jul"',
            'IFNULL(SUM(MONTH(o.created_at) = 8), 0) as "Aug"',
            'IFNULL(SUM(MONTH(o.created_at) = 9), 0) as "Sep"',
            'IFNULL(SUM(MONTH(o.created_at) = 10), 0) as "Oct"',
            'IFNULL(SUM(MONTH(o.created_at) = 11), 0) as "Nov"',
            'IFNULL(SUM(MONTH(o.created_at) = 12), 0) as "Dec"',
        ];

        if ($type === 'nominal_transaction') {
            $select = [
                'SUM(o.total + o.shipping_price) as "Jan"',
                'SUM(MONTH(o.created_at) = 1) as "Jan-Count"',
                'SUM(o.total + o.shipping_price) as "Feb"',
                'SUM(MONTH(o.created_at) = 2) as "Feb-Count"',
                'SUM(o.total + o.shipping_price) as "Mar"',
                'SUM(MONTH(o.created_at) = 3) as "Mar-Count"',
                'SUM(o.total + o.shipping_price) as "Apr"',
                'SUM(MONTH(o.created_at) = 4) as "Apr-Count"',
                'SUM(o.total + o.shipping_price) as "May"',
                'SUM(MONTH(o.created_at) = 5) as "May-Count"',
                'SUM(o.total + o.shipping_price) as "Jun"',
                'SUM(MONTH(o.created_at) = 6) as "Jun-Count"',
                'SUM(o.total + o.shipping_price) as "Jul"',
                'SUM(MONTH(o.created_at) = 7) as "Jul-Count"',
                'SUM(o.total + o.shipping_price) as "Aug"',
                'SUM(MONTH(o.created_at) = 8) as "Aug-Count"',
                'SUM(o.total + o.shipping_price) as "Sep"',
                'SUM(MONTH(o.created_at) = 9) as "Sep-Count"',
                'SUM(o.total + o.shipping_price) as "Oct"',
                'SUM(MONTH(o.created_at) = 10) as "Oct-Count"',
                'SUM(o.total + o.shipping_price) as "Nov"',
                'SUM(MONTH(o.created_at) = 11) as "Nov-Count"',
                'SUM(o.total + o.shipping_price) as "Dec"',
                'SUM(MONTH(o.created_at) = 12) as "Dec-Count"',
                /*'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Jan"',
                'SUM(MONTH(o.created_at) = 1) as "Jan-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Feb"',
                'SUM(MONTH(o.created_at) = 2) as "Feb-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Mar"',
                'SUM(MONTH(o.created_at) = 3) as "Mar-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Apr"',
                'SUM(MONTH(o.created_at) = 4) as "Apr-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "May"',
                'SUM(MONTH(o.created_at) = 5) as "May-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Jun"',
                'SUM(MONTH(o.created_at) = 6) as "Jun-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Jul"',
                'SUM(MONTH(o.created_at) = 7) as "Jul-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Aug"',
                'SUM(MONTH(o.created_at) = 8) as "Aug-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Sep"',
                'SUM(MONTH(o.created_at) = 9) as "Sep-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Oct"',
                'SUM(MONTH(o.created_at) = 10) as "Oct-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Nov"',
                'SUM(MONTH(o.created_at) = 11) as "Nov-Count"',
                'cast((SUM(o.total + o.shipping_price) / 1000000) as UNSIGNED) as "Dec"',
                'SUM(MONTH(o.created_at) = 12) as "Dec-Count"',*/
            ];
        }

        $conn = $this->getEntityManager()->getConnection();
        $query = $conn
            ->createQueryBuilder()
            ->select($select)
            ->from('`order`', 'o')
            ->leftJoin('o', 'store', 's', 'o.store_id = s.id')
            ->where('o.id <> 0')
        ;

        if (isset($parameters['store']) && !empty($parameters['store'])) {
            $query->andWhere(sprintf('o.store_id = %s', $conn->quote($parameters['store'])));
        }

        if (isset($parameters['status']) && !empty($parameters['status'])) {
            $query->andWhere(sprintf('o.status = %s', $conn->quote($parameters['status'])));
        }

        if (isset($parameters['in_status']) && is_array($parameters['in_status'])) {
            $statuses = $parameters['in_status'];

            foreach ($statuses as &$status) {
                $status = $conn->quote($status);
            }

            unset($status);

            $query->andWhere(sprintf('o.status IN (%s)', implode(', ', $statuses)));
        }

        if (isset($parameters['type']) && !empty($parameters['type'])) {
            if ($parameters['type'] === 'regular') {
                $query->andWhere('o.is_b2g_transaction = 0');
            } elseif ($parameters['type'] === 'b2g') {
                $query->andWhere('o.is_b2g_transaction = 1');
            }
        }

        if (isset($parameters['year']) && !empty($parameters['year'])) {
            $query->andWhere(sprintf('YEAR(o.created_at) = %s', $conn->quote($parameters['year'])));
        } else {
            $query->andWhere('YEAR(o.created_at) = YEAR(CURDATE())');
        }

        if (isset($parameters['admin_merchant_province']) && !empty($parameters['admin_merchant_province'])) {
            $query->andWhere(sprintf('s.province_id = %s', $conn->quote($parameters['admin_merchant_province'])));
        }

        if ($type === 'nominal_transaction') {
            $query->groupBy('MONTH(o.created_at)');
        }

        //$query->groupBy('MONTH(o.created_at)');

        try {
            $fetch = ($type === 'nominal_transaction') ? 'fetchAll' : 'fetch';
            $result = $conn->executeQuery($query->getSQL())->{$fetch}();
        } catch (Exception $e) {
            $result = [
                'Jan' => 0,
                'Feb' => 0,
                'Mar' => 0,
                'Apr' => 0,
                'May' => 0,
                'Jun' => 0,
                'Jul' => 0,
                'Aug' => 0,
                'Sep' => 0,
                'Oct' => 0,
                'Nov' => 0,
                'Dec' => 0,
            ];
        }

        return $result;
    }

    public function getOrderRelatedBySharedId(string $sharedId, int $orderId)
    {
        return $this
            ->createQueryBuilder('o')
            ->select(['o', 'op', 'oc', 's.name as s_name', 's.address as s_address', 's.umkm_category as s_umkm_category', 'ow.id as s_ow_id', 'u.id as u_id', 'u.firstName as u_firstName', 'u.lastName as u_lastName', 'u.role as u_role','u.subRole as u_subRole', 'u.ppName as u_ppName', 'u.ppkName as u_ppkName'])
            ->leftJoin(OrderPayment::class, 'op', 'WITH', 'o.id = op.order')
            ->leftJoin(OrderComplaint::class, 'oc', 'WITH', 'o.id = oc.order')
            ->leftJoin(User::class, 'u', 'WITH', 'o.buyer = u.id')
            ->leftJoin(Store::class, 's', 'WITH', 'o.seller = s.id')
            ->leftJoin(User::class, 'ow', 'WITH', 'ow.id = s.user')
            ->where('o.sharedId = :shared_id')
            ->andWhere('o.id <> :id')
            ->setParameter('shared_id', $sharedId)
            ->setParameter('id', $orderId)
            ->getQuery()
            ->getScalarResult()
        ;
    }

    public function getOrderDetailBySharedId(string $sharedId, array $parameters = [])
    {
        $query = $this
            ->createQueryBuilder('o')
            ->select(['o', 'op', 'oc', 's.name as s_name', 's.address as s_address', 'ow.id as s_ow_id', 'u.id as u_id', 'u.firstName as u_firstName', 'u.lastName as u_lastName', 'u.role as u_role','u.subRole as u_subRole', 'u.ppName as u_ppName', 'u.ppkName as u_ppkName'])
            ->leftJoin(OrderPayment::class, 'op', 'WITH', 'o.id = op.order')
            ->leftJoin(OrderComplaint::class, 'oc', 'WITH', 'o.id = oc.order')
            ->leftJoin(User::class, 'u', 'WITH', 'o.buyer = u.id')
            ->leftJoin(Store::class, 's', 'WITH', 'o.seller = s.id')
            ->leftJoin(User::class, 'ow', 'WITH', 'ow.id = s.user')
            ->where('o.sharedId = :shared_id')
            ->setParameter('shared_id', $sharedId)
        ;

        if (isset($parameters['seller'])) {
            $query
                ->andWhere('o.seller = :seller')
                ->setParameter('seller', $parameters['seller'])
            ;
        }

        if (isset($parameters['buyer'])) {
            $query
                ->andWhere('o.buyer = :buyer')
                ->setParameter('buyer', $parameters['buyer'])
            ;
        }

        $orders = $query->getQuery()->getScalarResult();

        if (count($orders) < 1) {
            $message = sprintf('Unable to find a shared orders identified by id "%s".', $sharedId);

            throw new RuntimeException($message);
        }

        foreach ($orders as &$order) {
            $order['o_products'] = $this->getOrderProducts($order['o_id']);
        }

        unset($order);

        return $orders;
    }

    public function getOrdersBySharedId(string $sharedId = null, int $orderId = 0, bool $group = true)
    {
        $query = $this->createQueryBuilder('o');

        if ($orderId > 0) {
            $query
                ->where('o.id <> :id')
                ->setParameter('id', $orderId)
            ;
        } else {
            $query
                ->where('o.id > :id')
                ->setParameter('id', 0)
            ;
        }

        if (!empty($sharedId)) {
            $query
                ->andWhere('o.sharedId = :shared_id')
                ->setParameter('shared_id', $sharedId)
            ;
        }

        $query
            ->andWhere($query->expr()->isNotNull('o.sharedId'))
            ->andWhere($query->expr()->isNull('o.sharedInvoice'))
        ;

        if ($group) {
            $query->groupBy('o.sharedId');
        }

        return $query->getQuery()->getResult();
    }

    public function getOrderDetailBySharedInvoice(string $sharedInvoice, array $parameters = [])
    {
        if (!isset($parameters['buyer'])) {
            throw new RuntimeException('Shared orders only available for buyer.');
        }

        $orders = $this
            ->createQueryBuilder('o')
            ->select(['o', 'op', 'oc', 's.name as s_name', 's.address as s_address', 's.isPKP as s_pkp', 'ow.id as s_ow_id', 'u.id as u_id', 'u.firstName as u_firstName', 'u.lastName as u_lastName', 'u.role as u_role', 'u.subRole as u_subRole', 'u.ppName as u_ppName', 'u.ppkName as u_ppkName','d'])
            ->leftJoin(OrderPayment::class, 'op', 'WITH', 'o.id = op.order')
            ->leftJoin(OrderComplaint::class, 'oc', 'WITH', 'o.id = oc.order')
            ->leftJoin(Disbursement::class, 'd', 'WITH', 'o.id = d.orderId')
            ->leftJoin(User::class, 'u', 'WITH', 'o.buyer = u.id')
            ->leftJoin(Store::class, 's', 'WITH', 'o.seller = s.id')
            ->leftJoin(User::class, 'ow', 'WITH', 'ow.id = s.user')
            ->where('o.sharedInvoice = :shared_invoice')
            ->setParameter('shared_invoice', $sharedInvoice)
        ;

        if ($parameters['buyer'] !== 'n/a') {
            $orders
                ->andWhere('o.buyer = :buyer')
                ->setParameter('buyer', $parameters['buyer'])
            ;

//            $orders
//                ->andWhere('u.lkppInstanceId = :instanceId')
//                ->setParameter('instanceId', ($parameters['buyer'])->getLkppInstanceId());
        }

        $orders = $orders->getQuery()->getScalarResult();

        if (count($orders) < 1) {
            $message = sprintf('Unable to find a shared orders identified by id "%s".', $sharedInvoice);

            throw new RuntimeException($message);
        }

        foreach ($orders as &$order) {
            $order['o_products'] = $this->getOrderProducts($order['o_id']);
            $order['o_negotiatedProducts'] = $this->getOrderNegotiationProducts($order['o_id']);
        }

        unset($order);

        return $orders;
    }

    public function getDataForTransactionByCategoryPerMonthChart(array $parameters = []): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $query = $conn
            ->createQueryBuilder()
            ->select(['o.id as order_id, op.product_id, pc.id as category_id, SUM(o.total + o.shipping_price) as amount'])
            ->from('`order`', 'o')
            ->leftJoin('o', 'order_product', 'op', 'o.id = op.order_id')
            ->leftJoin('op', 'product', 'p', 'op.product_id = p.id')
            ->leftJoin('p', 'product_category', 'pc', 'p.category = pc.id')
            ->where('o.id <> 0')
            //->andWhere('p.status <> \'deleted\'')
        ;

        $query->andWhere($query->expr()->isNotNull('op.product_id'));

        if (isset($parameters['in_status']) && is_array($parameters['in_status'])) {
            $statuses = $parameters['in_status'];

            foreach ($statuses as &$status) {
                $status = $conn->quote($status);
            }

            unset($status);

            $query->andWhere(sprintf('o.status IN (%s)', implode(', ', $statuses)));
        }

        if (isset($parameters['categories']) && is_array($parameters['categories'])) {
            /*$categories = $parameters['categories'];
            $sql = '';

            foreach ($categories as $key => $category) {
                if (abs($category) > 0) {
                    $sql .= $key === 0 ? 'FIND_IN_SET("'.$category.'", p.category)' : ' OR FIND_IN_SET("'.$category.'", p.category)';
                }
            }

            if ($sql !== '') {
                $query->andWhere($sql);
            }*/

            $query->andWhere(sprintf('p.category IN (%s)', implode(', ', $parameters['categories'])));
        }

        if (isset($parameters['year']) && !empty($parameters['year'])) {
            $query->andWhere(sprintf('YEAR(o.created_at) = %s', $conn->quote($parameters['year'])));
        } else {
            $query->andWhere('YEAR(o.created_at) = YEAR(CURDATE())');
        }

        if (isset($parameters['admin_merchant_province']) && !empty($parameters['admin_merchant_province'])) {
            $query->andWhere(sprintf('s.province_id = %s', $conn->quote($parameters['admin_merchant_province'])));
        }

        $query->groupBy('pc.id');
        //$query->groupBy('op.order_id');

        try {
            $result = $conn->executeQuery($query->getSQL())->fetchAll();
        } catch (Exception $e) {
            $result = [];
        }

        return $result;
    }

    public function getOrderNegotiationProducts(int $orderId)
    {
        return $this
            ->createQueryBuilder($this->alias)
            ->select(['on', 'op.price as op_price', 'p.id as p_id', 'p.name as p_name'])
            ->leftJoin(OrderNegotiation::class, 'on', 'WITH', 'on.order = o.id')
            ->leftJoin(OrderProduct::class, 'op', 'WITH', 'op.product = on.productId')
            ->leftJoin(Product::class, 'p', 'WITH', 'p.id = on.productId')
            ->where('o.id = :order_id')
            ->setParameter('order_id', $orderId)
            ->groupBy('on.id')
            ->getQuery()
            ->getScalarResult()
        ;
    }

    public function countNegotiationSubmissions(int $orderId)
    {
        $query = $this
            ->createQueryBuilder('o')
            ->select(['count(on.id) as total'])
            ->distinct()
            ->leftJoin(OrderNegotiation::class, 'on', 'WITH', 'on.order = o.id')
            ->where('o.id = :order_id')
            ->setParameter('order_id', $orderId)
            ->groupBy('on.productId')
        ;

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return ['total' => 0];
    }

    public function getLatestNegotiationItem(int $orderId)
    {
        $limit = $this->countNegotiationSubmissions($orderId);

        return $this
            ->createQueryBuilder('o')
            ->select(['on'])
            ->leftJoin(OrderNegotiation::class, 'on', 'WITH', 'on.order = o.id')
            ->where('o.id = :order_id')
            ->andWhere('on.batch = :batch')
            ->setParameter('order_id', $orderId)
            ->setParameter('batch', $limit['total'])
            ->orderBy('on.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getQRISPaymentDetail(string $orderSharedInvoice)
    {
        $query = $this
            ->createQueryBuilder('o')
            ->select(['q'])
            ->leftJoin(Qris::class, 'q', 'WITH', 'q.invoice = o.sharedInvoice')
            ->where('q.invoice = :invoice')
            ->setParameter('invoice', $orderSharedInvoice)
        ;

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return null;
    }

    public function getVAPaymentDetail(string $orderSharedInvoice)
    {
        $query = $this
            ->createQueryBuilder('o')
            ->select(['va'])
            ->leftJoin(VirtualAccount::class, 'va', 'WITH', 'va.invoice = o.sharedInvoice')
            ->where('va.invoice = :invoice')
            ->setParameter('invoice', $orderSharedInvoice)
        ;

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return null;
    }

    public function getDataForMerchantTransactionChart(array $parameters = []): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $query = $this
            ->createQueryBuilder('o')
            ->select(['o','s.name as s_name','s.id as s_id'])
            // ->from('`order`', 'o')
            // ->leftJoin('o', 'store', 's', 'o.store_id = s.id')
            ->leftJoin(Store::class, 's', 'WITH', 'o.seller = s.id')
            ->where('o.id <> 0')
        ;

        $query->andWhere($query->expr()->isNotNull('o.seller'));

        if (isset($parameters['in_status']) && is_array($parameters['in_status'])) {
            $statuses = $parameters['in_status'];

            foreach ($statuses as &$status) {
                $status = $conn->quote($status);
            }

            unset($status);

            $query->andWhere(sprintf('o.status IN (%s)', implode(', ', $statuses)));
        }

        if (isset($parameters['year']) && !empty($parameters['year'])) {
            $query->andWhere(sprintf('YEAR(o.createdAt) = %s', $conn->quote($parameters['year'])));
        } else {
            $query->andWhere(sprintf('YEAR(o.createdAt) = %s', $conn->quote(date('Y'))));
            // $query->andWhere('YEAR(o.created_at) = YEAR(CURDATE())');
        }

        if (isset($parameters['admin_merchant_province']) && !empty($parameters['admin_merchant_province'])) {
            $query->andWhere(sprintf('s.province_id = %s', $conn->quote($parameters['admin_merchant_province'])));
        }

        // $query->groupBy('o.seller');

        try {
            // $result = $conn->executeQuery($query->getSQL())->fetchAll();
            $result = $query->getQuery()->getResult();
            $data_result = [];
            foreach ($result as $key => $value) {
                $data_result[$value['s_id']][] = $value;
            }
            $result = $data_result;
        } catch (Exception $e) {
            dd($e);
            $result = [];
        }

        return $result;
    }

    public function getApprovedNegotiationItem(int $orderId)
    {
        return $this
            ->createQueryBuilder('o')
            ->select(['on'])
            ->leftJoin(OrderNegotiation::class, 'on', 'WITH', 'on.order = o.id')
            ->where('o.id = :order_id')
            ->andWhere('on.isApproved = :is_approve')
            ->setParameter('order_id', $orderId)
            ->setParameter('is_approve', true)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getOrderPpkNameNotNull()
    {
        return $this
            ->createQueryBuilder('o')
            ->where('o.ppkId IS NULL')
            ->orWhere('o.treasurerId IS NULL')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getOrderProcessPaymentOrPaid()
    {
        return $this
            ->createQueryBuilder('o')
            ->where("o.status IN(:status)")
            ->andWhere('o.createdAt <:date_start')
            ->andWhere('o.taxType IS NULL')
            ->setParameter('date_start', '2023-02-07')
            ->setParameter('status', array_values(['payment_process','paid']))
            ->getQuery()
            ->getResult()
        ;
    }

    public function getDataToExport(array $parameters = []): array
    {
        $this->builder = $this
            ->createQueryBuilder('o')
            ->select(['o'])
            ->leftJoin(User::class, 'u', 'WITH', 'o.buyer = u.id')
            ->leftJoin(Store::class, 's', 'WITH', 'o.seller = s.id')
            ->where('o.id <> :id')
            ->setParameter('id', 0);

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        $query = clone $this->builder;
        $query->select('count(o.id)');

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

    public function getPartialOrders($master_id)
    {
        return $this
            ->createQueryBuilder($this->alias)
            ->select([
                'o', 
                'ow.user_signature as ow_user_signature', 
                'ow.user_stamp as ow_user_stamp', 
                'ow.firstName as ow_firstName', 
                'ow.lastName as ow_lastName', 
                's.name as s_name', 
                's.isPKP as s_isPKP', 
                'u.firstName as u_firstName', 
                'u.lastName as u_lastName', 
                'u.role as u_role', 
                'u.subRole as u_subRole',
                'sum(op.quantity) as total_qty'
            ])
            ->leftJoin(User::class, 'u', 'WITH', 'o.buyer = u.id')
            ->leftJoin(Store::class, 's', 'WITH', 'o.seller = s.id')
            ->leftJoin(User::class, 'ow', 'WITH', 'ow.id = s.user')
            ->leftJoin(OrderProduct::class, 'op', 'WITH', 'o.id = op.order')
            ->where('o.master_id = :master_id')
            ->groupBy('o.id')
            ->setParameter('master_id', $master_id)
            ->getQuery()
            ->getScalarResult()
        ;
    }
}
