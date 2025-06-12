<?php

namespace App\Repository;

use App\Entity\StoreOwnerLog;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class StoreOwnerLogRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = StoreOwnerLog::class;
        $this->alias = 'sol';

        parent::__construct($registry);
    }

    public function getLogHistoriesByStore(int $storeId)
    {
        return $this
            ->createQueryBuilder('sol')
            ->select(['sol', 'co.firstName as co_firstName', 'co.lastName as co_lastName', 'po.firstName as po_firstName', 'po.lastName as po_lastName', 'adm.firstName as adm_firstName', 'adm.lastName as adm_lastName'])
            ->leftJoin(User::class, 'co', 'WITH', 'sol.currentOwner = co.id')
            ->leftJoin(User::class, 'po', 'WITH', 'sol.previousOwner = po.id')
            ->leftJoin(User::class, 'adm', 'WITH', 'sol.updatedBy = adm.id')
            ->where('sol.id <> :id')
            ->andWhere('sol.storeId = :store_id')
            ->setParameter('id', 0)
            ->setParameter('store_id', $storeId)
            ->setMaxResults(100)
            ->orderBy('sol.id', 'DESC')
            ->getQuery()
            ->getScalarResult()
        ;
    }
}
