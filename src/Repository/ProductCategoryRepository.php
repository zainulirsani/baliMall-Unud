<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductCategory;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class ProductCategoryRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = ProductCategory::class;
        $this->alias = 'pc';

        parent::__construct($registry);
    }

    public function getDataForSelectOptions(array $parameters = []): array
    {
        $this->builder = $this
            ->createQueryBuilder('pc')
            ->select(['pc.id as id', 'pc.name as text'])
            ->where('pc.status = :status')
            ->setParameter('status', true)
        ;

        if (isset($parameters['search']) && !empty($parameters['search'])) {
            $this->builder
                ->andWhere('pc.name LIKE :search')
                ->setParameter('search', '%'.$parameters['search'].'%')
            ;
        }

        if (isset($parameters['parent_id'])) {
            $this->builder
                ->andWhere('pc.parentId = :parent_id')
                ->setParameter('parent_id', abs($parameters['parent_id']))
            ;
        }

        $this->setOrderBy($parameters);

        return $this->builder->getQuery()->getScalarResult();
    }

    public function applyFilters(array $parameters = []): void
    {
        if (isset($parameters['status']) && !empty($parameters['status'])) {
            $this->builder
                ->andWhere('pc.status = :status')
                ->setParameter('status', $parameters['status'] === 'active')
            ;
        }

        if (isset($parameters['keywords']) && !empty($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('pc.name', ':keywords')
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        if (isset($parameters['parent_category']) && abs($parameters['parent_category']) > 0) {
            $this->builder
                ->andWhere('pc.parentId = :parent_id')
                ->setParameter('parent_id', abs($parameters['parent_category']))
            ;
        }

        if (isset($parameters['date_start']) && !empty($parameters['date_start'])) {
            $this->builder
                ->andWhere('pc.createdAt >= :date_start')
                ->setParameter('date_start', $parameters['date_start'])
            ;
        }

        if (isset($parameters['date_end']) && !empty($parameters['date_end'])) {
            $this->builder
                ->andWhere('pc.createdAt <= :date_end')
                ->setParameter('date_end', $parameters['date_end'])
            ;
        }
    }

    public function getFeaturedData(array $parameters = []): array
    {
        $this->builder = $this
            ->createQueryBuilder('pc')
            ->select([
                'pc.id as id',
                'pc.name as text',
                'pc.desktopImage as icon',
                'pc.className as class',
                'pc.status as status',
                'lvl1.id as lvl1_id',
                'lvl1.parentId as lvl1_parentId',
                'lvl1.name as lvl1_text',
                'lvl1.desktopImage as lvl1_icon',
                'lvl1.className as lvl1_class',
                'lvl1.status as lvl1_status',
                'lvl2.id as lvl2_id',
                'lvl2.parentId as lvl2_parentId',
                'lvl2.name as lvl2_text',
                'lvl2.desktopImage as lvl2_icon',
                'lvl2.className as lvl2_class',
                'lvl2.status as lvl2_status'
            ])
            ->leftJoin(ProductCategory::class, 'lvl1', 'WITH', 'lvl1.parentId = pc.id')
            ->leftJoin(ProductCategory::class, 'lvl2', 'WITH', 'lvl2.parentId = lvl1.id')
            ->where('pc.status = :status')
            ->setParameter('status', true)
        ;

        if (isset($parameters['featured'])) {
            $this->builder
                ->andWhere('pc.featured LIKE :featured')
                ->setParameter('featured', $parameters['featured'] === 'yes')
            ;
        }

        if (isset($parameters['fetch_parent']) && $parameters['fetch_parent'] === 'yes') {
            $this->builder
                ->andWhere('pc.parentId = :parent_id')
                ->setParameter('parent_id', 0)
            ;
        }

        if (isset($parameters['limit']) && abs($parameters['limit']) > 0) {
            $this->builder->setMaxResults($parameters['limit']);
        }

        $this->setOrderBy($parameters);

        return $this->builder->getQuery()->getScalarResult();
    }

    public function getCategoryFromProduct(array $categoryIds, bool $multi = false)
    {
        $query = $this
            ->createQueryBuilder('pc')
            ->where('pc.status = :status')
            ->setParameter('status', true)
        ;

        if ($multi) {
            $query->andWhere($query->expr()->in('pc.id', $categoryIds));

            return $query->getQuery()->getResult();
        }

        $query
            ->andWhere('pc.id = :id')
            ->setParameter('id', $categoryIds[0])
        ;

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return false;
    }

    public function getCategoryIdByManySlugs(array $categorySlugs): array
    {
        $query = $this
            ->createQueryBuilder('pc')
            ->select('pc.id')
            ->where('pc.status = :status')
            ->setParameter('status', true)
        ;

        $query->andWhere($query->expr()->in('pc.slug', $categorySlugs));

        $ids = $query->getQuery()->getArrayResult();
        $result = [];

        foreach ($ids as $id) {
            $result[] = $id['id'];
        }

        return $result;
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('pc')
            ->select(['pc', 'pcp.name as pcp_name'])
            ->leftJoin(ProductCategory::class, 'pcp', 'WITH', 'pc.parentId = pcp.id')
            ->where('pc.id <> :id')
            ->setParameter('id', 0)
        ;

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        $query = clone $this->builder;
        $query->select('count(pc.id)');

        try {
            $results['total'] = $query->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        $this->setLimitAndOffset($parameters);

        $results['data'] = $this->builder->getQuery()->getScalarResult();

        return $this->getResults($results);
    }

    public function getCategoryDataWithParents(): array
    {
        $data = [];
        $categories = $this
            ->createQueryBuilder('pc')
            ->select(['pc.id as id', 'pc.name as text', 'pc.parentId as parent_id', 'pc.name as parent_text'])
            ->where('pc.status = :status')
            ->andWhere('pc.parentId = :parent_id')
            ->setParameter('status', true)
            ->setParameter('parent_id', 0)
            ->orderBy('pc.name', 'ASC')
            ->getQuery()
            ->getScalarResult()
        ;

        foreach ($categories as $category) {
            if ($subData = $this->getChildrenCategoryData((int) $category['id'])) {
                $data = $subData;
            }
        }

        $data = array_merge($categories, $data);
        $tempData = array_column($data, 'parent_text');
        array_multisort($tempData, SORT_ASC, $data);

        return $data;
    }

    public function getChildrenCategoryData(int $categoryId)
    {
        return $this
            ->createQueryBuilder('pc')
            ->select(['pc.id as id', 'pc.name as text', 'pcp.id as parent_id', 'pcp.name as parent_text', 'pc.fee as pc_fee'])
            ->leftJoin(ProductCategory::class, 'pcp', 'WITH', 'pc.parentId = pcp.id')
            ->where('pc.status = :status')
            ->andWhere('pc.parentId = :parent_id')
            ->setParameter('status', true)
            ->setParameter('parent_id', $categoryId)
            ->orderBy('pc.name', 'ASC')
            ->addOrderBy('pcp.name', 'ASC')
            ->getQuery()
            ->getScalarResult()
        ;
    }

    public function getCategoryParents(): array
    {
        return $this
            ->createQueryBuilder('pc')
            ->select(['pc.id as id', 'pc.name as text', 'pc.desktopImage', 'pc.className', 'pc.mobileImage', 'pc.parentId as parent_id', 'pc.name as parent_text', 'pc.fee as pc_fee'])
            ->where('pc.status = :status')
            ->andWhere('pc.parentId = :parent_id')
            ->setParameter('status', true)
            ->setParameter('parent_id', 0)
            ->orderBy('pc.name', 'ASC')
            ->getQuery()
            ->getScalarResult()
        ;
    }

    public function getCategoryFromProductId(int $productId)
    {
        $query = $this
            ->createQueryBuilder('pc')
            ->leftJoin(Product::class, 'p', 'WITH', 'pc.id = p.category')
            ->where('pc.status = :status')
            ->andWhere('p.id = :id')
            ->setParameter('status', true)
            ->setParameter('id', $productId)
        ;

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return false;
    }

    public function getCategoryFeeById(int $categoryId)
    {
        $query = $this
            ->createQueryBuilder('pc')
            ->select('fee')
            ->where('pc.id = : :id')
            ->setParameter('id', $categoryId)
            ;

        return $query->getQuery()->getResult();
    }

    public function getDataForApi(array $parameters = []): array
    {
        $this->builder = $this
            ->createQueryBuilder('pc')
            ->select(['pc.id as id', 'pc.name as text'])
        ;

        if (isset($parameters['search']) && !empty($parameters['search'])) {

            foreach ($parameters['search'] as $key => $value) {
                if ($key == 0) {
                    $this->builder
                        ->where('pc.name LIKE :search'.$key)
                        ->setParameter('search'.$key, '%'.$value.'%')
                    ;
                } else {
                    $this->builder
                        ->orWhere('pc.name LIKE :search'.$key)
                        ->setParameter('search'.$key, '%'.$value.'%')
                    ;
                }
            }

            $this->builder
                ->andWhere('pc.status = :status')
                ->setParameter('status', true)
                ->andWhere('pc.parentId <> :parent_id')
                ->setParameter('parent_id', 0);
        } else {

            $this->builder
                ->where('pc.status = :status')
                ->setParameter('status', true)
                ->andWhere('pc.parentId <> :parent_id')
                ->setParameter('parent_id', 0);
        }

        return $this->builder->getQuery()->getScalarResult();
    }
}
