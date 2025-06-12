<?php

namespace App\Repository;

use App\Entity\ProductFile;
use Doctrine\Persistence\ManagerRegistry;

class ProductFileRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = ProductFile::class;
        $this->alias = 'pf';

        parent::__construct($registry);
    }
}
