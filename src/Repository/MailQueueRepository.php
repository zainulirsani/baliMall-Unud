<?php

namespace App\Repository;

use App\Entity\MailQueue;
use Doctrine\Persistence\ManagerRegistry;

class MailQueueRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = MailQueue::class;
        $this->alias = 'mq';

        parent::__construct($registry);
    }
}
