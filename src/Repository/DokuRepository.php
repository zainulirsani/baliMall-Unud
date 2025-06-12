<?php

namespace App\Repository;

use App\Entity\Doku;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Doku|null find($id, $lockMode = null, $lockVersion = null)
 * @method Doku|null findOneBy(array $criteria, array $orderBy = null)
 * @method Doku[]    findAll()
 * @method Doku[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DokuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Doku::class);
    }

    public function getFailedPayment(): array
    {
        $query = $this
            ->createQueryBuilder('d')
            ->where('d.status = :statusFailed')
            ->orWhere('d.status = :statusExpired')
            ->orWhere('d.status = :statusPending')
            ->setParameter('statusFailed', 'FAILED')
            ->setParameter('statusExpired', 'EXPIRED')
            ->setParameter('statusPending', 'PENDING')
        ;

        return $query->getQuery()->getResult();
    }

    public function getPendingPayment(): array
    {
        $query = $this
            ->createQueryBuilder('d')
            ->select(['d'])
            ->leftJoin(Order::class, 'o', 'WITH', 'o.dokuInvoiceNumber = d.invoice_number')
            ->where('d.status = :dokuStatus')
            // ->andWhere('o.status = :orderStatus')
            ->setParameter('dokuStatus', 'PENDING')
            // ->setParameter('orderStatus', 'pending')
        ;

        return $query->getQuery()->getResult();
    }
}
