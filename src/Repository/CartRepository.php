<?php

namespace App\Repository;

use App\Entity\Cart;
use Doctrine\Persistence\ManagerRegistry;

class CartRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Cart::class;
        $this->alias = 'c';

        parent::__construct($registry);
    }
}
