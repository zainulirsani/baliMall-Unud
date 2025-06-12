<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Voucher;
use App\Entity\VoucherUsedLog;
use Doctrine\Persistence\ManagerRegistry;

class VoucherUsedLogRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = VoucherUsedLog::class;
        $this->alias = 'vul';

        parent::__construct($registry);
    }

    public function getVouchersForOrderBySharedId(string $sharedId, bool $group = true, int $orderId = 0)
    {
        $select = ['vul', 'v', 'SUM(vul.voucherAmount) as vul_totalVoucher'];

        if ($orderId > 0) {
            $select[] = 'SUM(vul.orderAmount) as vul_totalOrder';
        } else {
            $select[] = 'vul.orderAmount as vul_totalOrder';
        }

        $query = $this
            ->createQueryBuilder('vul')
            ->select($select)
            ->leftJoin(Voucher::class, 'v', 'WITH', 'v.id = vul.voucherId')
            ->where('vul.orderSharedId = :shared_id')
            ->setParameter('shared_id', $sharedId)
        ;

        if ($orderId > 0) {
            $query
                ->andWhere('vul.orderId = :order_id')
                ->setParameter('order_id', $orderId)
            ;
        }

        if ($group) {
            $query->groupBy('vul.orderSharedId');
        }

        return $query->getQuery()->getScalarResult();
    }

    public function getVouchersForOrderBySharedIdGroupByVoucherId(string $sharedId, bool $group = true, int $orderId = 0)
    {
        $select = ['vul', 'v'];

        if ($orderId > 0) {
            $select[] = 'SUM(vul.orderAmount) as vul_totalOrder';
        } else {
            $select[] = 'vul.orderAmount as vul_totalOrder';
        }

        $query = $this
            ->createQueryBuilder('vul')
            ->select($select)
            ->leftJoin(Voucher::class, 'v', 'WITH', 'v.id = vul.voucherId')
            ->where('vul.orderSharedId = :shared_id')
            ->setParameter('shared_id', $sharedId)
        ;

        if ($orderId > 0) {
            $query
                ->andWhere('vul.orderId = :order_id')
                ->setParameter('order_id', $orderId)
            ;
        }

        if ($group == true) {
            $query->groupBy('vul.voucherId');
        }

        return $query->getQuery()->getScalarResult();
    }

    public function getTotalAmountVouchersBySharedId(string $sharedId)
    {
        $select = ['SUM(vul.voucherAmount) as vul_totalVoucher'];
        $amount = 0;

        $results = $this
            ->createQueryBuilder('vul')
            ->select($select)
            ->leftJoin(Voucher::class, 'v', 'WITH', 'v.id = vul.voucherId')
            ->where('vul.orderSharedId = :shared_id')
            ->setParameter('shared_id', $sharedId)
            ->getQuery()
            ->getScalarResult()
        ;

        foreach ($results as $result) {
            $amount = $result['vul_totalVoucher'];
        }

        return $amount;
    }

    public function getVouchersListBySharedId(string $sharedId)
    {
        $select = ['vul', 'v'];

        $query = $this
            ->createQueryBuilder('vul')
            ->select($select)
            ->leftJoin(Voucher::class, 'v', 'WITH', 'v.id = vul.voucherId')
            ->where('vul.orderSharedId = :shared_id')
            ->setParameter('shared_id', $sharedId)
        ;

        return $query->getQuery()->getScalarResult();
    }

    public function getVouchersForPaymentConfirmationBySharedId(string $sharedId, bool $group = true, int $orderId = 0)
    {
        $select = ['vul', 'v', 'vul.voucherAmount as vul_totalVoucher'];

        if ($group) {
            $select[] = 'SUM(vul.orderAmount) as vul_totalOrder';
        } else {
            $select[] = 'vul.orderAmount as vul_totalOrder';
        }

        $query = $this
            ->createQueryBuilder('vul')
            ->select($select)
            ->leftJoin(Voucher::class, 'v', 'WITH', 'v.id = vul.voucherId')
            ->where('vul.orderSharedId = :shared_id')
            ->setParameter('shared_id', $sharedId)
        ;

        if ($orderId > 0) {
            $query
                ->andWhere('vul.orderId = :order_id')
                ->setParameter('order_id', $orderId)
            ;
        }

        if ($group) {
            $query->groupBy('vul.orderSharedId');
        }

        return $query->getQuery()->getScalarResult();
    }

    public function getOrderInvoiceListByVoucherId(int $voucherId): array
    {
        $result = [];
        $invoices =  $this
            ->createQueryBuilder('vul')
            ->select(['o.invoice as o_invoice'])
            ->leftJoin(Voucher::class, 'v', 'WITH', 'v.id = vul.voucherId')
            ->leftJoin(Order::class, 'o', 'WITH', 'o.id = vul.orderId')
            ->where('v.id = :voucher_id')
            ->setParameter('voucher_id', $voucherId)
            ->getQuery()
            ->getScalarResult()
        ;

        foreach ($invoices as $invoice) {
            $result[] = $invoice['o_invoice'];
        }

        return $result;
    }

    public function getTotalOrder(string $sharedId, $groupBy=null): array
    {
        if (empty($groupBy)) {
            $groupBy = 'vul.voucherId';
        }

        $res = $this
            ->createQueryBuilder('vul')
            ->select(['vul.orderAmount', 'vul.voucherAmount', 'vul.voucherId', 'vul.orderId' , 'o.total as o_total', 'o.id as o_id', 'op'])
            ->leftJoin(Order::class, 'o', 'WITH', 'o.id = vul.orderId')
            ->leftJoin(OrderProduct::class, 'op', 'WITH', 'o.id = op.order')
            ->where('vul.orderSharedId = :shared_id')
            ->addGroupBy($groupBy)
            ->setParameter('shared_id', $sharedId)
            ->getQuery()
            ->getScalarResult();

        return $res;
    }
}
