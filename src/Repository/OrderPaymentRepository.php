<?php

namespace App\Repository;

use App\Entity\OrderPayment;
use Doctrine\Persistence\ManagerRegistry;

class OrderPaymentRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = OrderPayment::class;
        $this->alias = 'op';

        parent::__construct($registry);
    }
}
