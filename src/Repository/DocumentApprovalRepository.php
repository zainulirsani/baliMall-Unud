<?php

namespace App\Repository;

use App\Entity\DocumentApproval;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentApproval>
 *
 * @method DocumentApproval|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentApproval|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentApproval[]    findAll()
 * @method DocumentApproval[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentApprovalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentApproval::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DocumentApproval $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(DocumentApproval $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function getByTypeDocument($order_id, $type_document , array $approved_by = [])
    {
        $documents = $this->createQueryBuilder('d')
            ->where('d.order_id = :order_id')
            ->setParameter('order_id' , $order_id)
            ->andWhere('d.type_document = :type_document')
            ->setParameter('type_document' , $type_document);

        if(count($approved_by) > 0) {
            $documents = $documents->andWhere('d.approved_by in (:ids)')->setParameter('ids' , $approved_by);
        }

        return $documents->getQuery()->getResult();
    }

    // /**
    //  * @return DocumentApproval[] Returns an array of DocumentApproval objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DocumentApproval
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
