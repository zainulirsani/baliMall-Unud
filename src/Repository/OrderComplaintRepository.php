<?php

namespace App\Repository;

use App\Entity\OrderComplaint;
use Doctrine\Persistence\ManagerRegistry;

class OrderComplaintRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = OrderComplaint::class;
        $this->alias = 'oc';

        parent::__construct($registry);
    }
}
