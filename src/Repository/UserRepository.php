<?php

namespace App\Repository;

use App\Entity\Store;
use App\Entity\User;
use App\Entity\UserAddress;
use App\Utility\GoogleMailHandler;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserRepository extends BaseEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = User::class;
        $this->alias = 'u';

        parent::__construct($registry);
    }

    public function loadUserByUsername($username)
    {
        $isEmail = substr_count($username, '@') + 1;

        if ($isEmail > 1) {
            $username = GoogleMailHandler::validate($username);
        }

        $query = $this
            ->createQueryBuilder('u')
            ->where('u.email = :email')
            //->andWhere('u.isActive = :active')
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('email', $username)
            //->setParameter('active', 1)
            ->setParameter('deleted', false)
            ->getQuery()
        ;

        try {
            /** @var User $user */
            $user = $query->getSingleResult();

            if (!$user->getIsActive()) {
                $message = sprintf('User account "%s" is not yet activated.', $username);

                throw new DisabledException($message);
            }
        } catch (NonUniqueResultException | NoResultException $e) {
            $message = sprintf('Unable to find an active user object identified by "%s".', $username);

            throw new UsernameNotFoundException($message, 0, $e);
        }

        return $user;
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('u')
            ->select(['u'])
            ->leftJoin(UserAddress::class, 'ua', 'WITH', 'ua.user = u.id')
            ->where('u.isDeleted = :deleted')
            ->setParameter('deleted', false)
            ->distinct()
        ;

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        $query = clone $this->builder;
        $query->select('count(distinct u.id)');

        try {
            $results['total'] = $query->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        $this->setLimitAndOffset($parameters);

        $results['data'] = $this->builder->getQuery()->getScalarResult();
        return $this->getResults($results);
    }

    public function getDataForSelectOptions(array $parameters = [])
    {
        $this->builder = $this
            ->createQueryBuilder('u')
            ->select(['u.id as id', 'CONCAT(u.firstName, \' \', u.lastName) as text'])
            ->where('u.isDeleted = :deleted')
            ->setParameter('deleted', false)
        ;

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        return $this->builder->getQuery()->getScalarResult();
    }

    public function getDataForStoreSelection(array $parameters = [])
    {
        $this->builder = $this
            ->createQueryBuilder('u')
            ->select(['u.id as id', 'CONCAT(u.firstName, \' \', u.lastName) as text', 'u.email as email', 'u.phoneNumber as phoneNumber'])
            ->leftJoin(Store::class, 's', 'WITH', 's.user = u.id')
            ->where('u.isDeleted = :deleted')
            ->setParameter('deleted', false)
        ;

        $this->builder->andWhere($this->builder->expr()->isNull('s.user'));

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        return $this->builder->getQuery()->getScalarResult();
    }

    public function getAllPpUsers()
    {
        $query = $this
            ->createQueryBuilder('u')
            ->select(['u'])
            ->where('u.role = :role_user')
            ->andWhere('u.subRole IS NULL')
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('role_user', 'ROLE_USER_GOVERNMENT')
            ->setParameter('deleted', false)
            ->getQuery()
        ;

        $user = $query->getScalarResult();

        return $user;
    }

    public function getAllPpkUsers($data)
    {
        echo $data;
        $query = $this
            ->createQueryBuilder('u')
            ->select(['u'])
            ->where('u.subRole = :sub_role')
            ->andWhere('u.lkppLpseId = :idLpse')
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('sub_role', 'PPK')
            ->setParameter('deleted', false)
            ->setParameter('idLpse', $data)
            ->getQuery()
        ;

        $user = $query->getScalarResult();

        return $user;
    }

    public function getAllPpkUsersWithoutLpse()
    {
        $query = $this
            ->createQueryBuilder('u')
            ->select(['u'])
            ->where('u.subRole = :sub_role')
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('sub_role', 'PPK')
            ->setParameter('deleted', false)
            ->getQuery()
        ;

        $user = $query->getScalarResult();

        return $user;
    }

    public function getAllTreasurerUsers($data)
    {
        $query = $this
            ->createQueryBuilder('u')
            ->select(['u'])
            ->where('u.subRole = :sub_role')
            ->andWhere('u.lkppLpseId = :idLpse')
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('sub_role', 'TREASURER')
            ->setParameter('deleted', false)
            ->setParameter('idLpse', $data)
            ->getQuery()
        ;

        $user = $query->getScalarResult();

        return $user;
    }

    public function getAllTreasurerUsersWithoutLpse()
    {
        $query = $this
            ->createQueryBuilder('u')
            ->select(['u'])
            ->where('u.subRole = :sub_role')
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('sub_role', 'TREASURER')
            ->setParameter('deleted', false)       
            ->getQuery()
        ;

        $user = $query->getScalarResult();

        return $user;
    }

    public function getDataWithProfileById(int $userId)
    {
        $query = $this
            ->createQueryBuilder('u')
            ->select(['u'])
            ->where('u.id = :user_id')
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('user_id', $userId)
            ->setParameter('deleted', false)
            ->getQuery()
        ;

        $user = $query->getScalarResult();

        if (!$user) {
            $message = sprintf('Unable to find an active user object identified by id "%s".', $userId);

            throw new RuntimeException($message);
        }

        $user = current($user);
        $user['main_address'] = $this->getFirstUserAddress($userId);

        unset($user['u_password']);

        return $user;
    }

    public function checkUsername(string $username): string
    {
        $query = $this
            ->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.username LIKE :username')
            ->setParameter('username', '%'.$username.'%')
        ;

        try {
            $count = (int) $query->getQuery()->getSingleScalarResult();

            return $count > 0 ? $username.'_'.$count : $username;
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return $username;
    }

    public function checkExistingEmail(string $email, int $userId)
    {
        $query = $this
            ->createQueryBuilder('u')
            ->select('u.email')
            ->where('u.emailCanonical = :email')
            ->setParameter('email', $email)
        ;

        if ($userId > 0) {
            $query
                ->andWhere('u.id <> :user_id')
                ->setParameter('user_id', $userId)
            ;
        }

        return $query->getQuery()->getResult();
    }

    public function applyFilters(array $parameters = []): void
    {
        if (isset($parameters['exclude_role']) && !empty($parameters['exclude_role'])) {
            $this->builder
                ->andWhere('u.role <> :exclude_role')
                ->setParameter('exclude_role', $parameters['exclude_role'])
            ;
        }

        if (isset($parameters['roles']) && is_array($parameters['roles'])) {
            $this->builder
                ->andWhere($this->builder->expr()->in('u.role', ':roles'))
                ->setParameter('roles', $parameters['roles'])
            ;
        }

        if (isset($parameters['role']) && !empty($parameters['role'])) {
            $this->builder
                ->andWhere('u.role = :role')
                ->setParameter('role', $parameters['role'])
            ;
        }

        if (isset($parameters['status']) && !empty($parameters['status'])) {
            $this->builder
                ->andWhere('u.isActive = :status')
                ->setParameter('status', $parameters['status'] === 'active')
            ;
        }

        if (isset($parameters['deleted']) && !empty($parameters['deleted'])) {
            $this->builder
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', $parameters['deleted'] === 'yes')
            ;
        }

        if (isset($parameters['keywords']) && !empty($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    //$this->builder->expr()->like('u.username', ':keywords'),
                    $this->builder->expr()->like('u.email', ':keywords'),
                    $this->builder->expr()->like('u.firstName', ':keywords'),
                    $this->builder->expr()->like('u.lastName', ':keywords')
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        if (isset($parameters['search']) && !empty($parameters['search'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('u.firstName', ':search'),
                    $this->builder->expr()->like('u.lastName', ':search')
                ))
                ->setParameter('search', '%'.$parameters['search'].'%')
            ;
        }

        if (isset($parameters['date_start']) && !empty($parameters['date_start'])) {
            $this->builder
                ->andWhere('u.createdAt >= :date_start')
                ->setParameter('date_start', $parameters['date_start'])
            ;
        }

        if (isset($parameters['date_end']) && !empty($parameters['date_end'])) {
            $this->builder
                ->andWhere('u.createdAt <= :date_end')
                ->setParameter('date_end', $parameters['date_end'])
            ;
        }

        if (isset($parameters['admin_merchant_cabang']) && !empty($parameters['admin_merchant_cabang'])) {
            $this->builder
                ->andWhere('ua.provinceId = :provinceId')
                ->setParameter('provinceId', $parameters['admin_merchant_cabang'])
            ;
        }

        if (isset($parameters['id_lpse']) && !empty($parameters['id_lpse'])) {
            if($parameters['id_lpse'] == '-') {
                $this->builder
                    ->andWhere('u.lkppLpseId IS NULL')
                ;
            } else {
                $this->builder
                    ->andWhere('u.lkppLpseId = :id_lpse')
                    ->setParameter('id_lpse', $parameters['id_lpse'])
                ;
            }
        }
    }

    public function getFirstUserAddress(int $userId)
    {
        $address =  $this
            ->createQueryBuilder('u')
            ->select(['ua'])
            ->leftJoin(UserAddress::class, 'ua', 'WITH', 'ua.user = u.id')
            ->where('u.id = :user_id')
            ->setParameter('user_id', $userId)
            ->setMaxResults(1)
            ->orderBy('ua.id', 'ASC')
        ;

        try {
            return $address->getQuery()->getSingleResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return [];
    }
}
