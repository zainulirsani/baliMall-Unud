<?php

namespace App\Repository;

use App\Entity\RoleHasPermission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoleHasPermission>
 *
 * @method RoleHasPermission|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoleHasPermission|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoleHasPermission[]    findAll()
 * @method RoleHasPermission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoleHasPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoleHasPermission::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(RoleHasPermission $entity, bool $flush = true): void
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
    public function remove(RoleHasPermission $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return RoleHasPermission[] Returns an array of RoleHasPermission objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    public function findOnePermission( $role, $permission_id, $subrole = null): ?RoleHasPermission
    {
        $query = $this->createQueryBuilder('r')
            ->andWhere('r.role_slug = :role_slug')
            ->andWhere('r.permission_id = :permission_id')
            ->setParameter('role_slug', $role)
            ->setParameter('permission_id', $permission_id);

        if(!is_null($subrole)) {
            $query
                ->setParameter('subrole_slug', $subrole)
                ->andWhere('r.subrole_slug = :subrole_slug');
        }
            
        return $query->getQuery()->getOneOrNullResult();
    }

    public function removeAll()
    {
        $datas = $this->findAll();

        foreach ($datas as $data) {
            $this->remove($data);
        }
    }
}
