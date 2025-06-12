<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductFile;
use App\Entity\ProductReview;
use App\Entity\Store;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\RuntimeException;

class ProductRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Product::class;
        $this->alias = 'p';

        parent::__construct($registry);
    }

    public function getDataForTable(array $parameters = []): array
    {
        $results = [
            'total' => 0,
            'data' => [],
        ];

        $this->builder = $this
            ->createQueryBuilder('p')
            ->select(['p', 's.id AS s_id', 's.slug AS s_slug', 's.name AS s_name', 'pc.name AS pc_name'])
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin(ProductCategory::class, 'pc', 'WITH', 'p.category = pc.id')
            ->where('p.id <> :id')
            ->andWhere('p.status <> :deleted')
            ->setParameter('id', 0)
            ->setParameter('deleted', 'deleted')
        ;

        $this->applyFilters($parameters);
        $this->setOrderBy($parameters);

        $query = clone $this->builder;
        $query->select('count(p.id)');

        try {
            $results['total'] = $query->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        $this->setLimitAndOffset($parameters);

        $results['data'] = $this->builder->getQuery()->getScalarResult();

        return $this->getResults($results);
    }

    public function getFrontDataWithDetailsById(int $productId, int $storeId, string $itemType = 'all')
    {
        $query = $this
            ->createQueryBuilder('p')
            ->select(['p', 's.id AS s_id', 's.name AS s_name'])
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->where('p.id = :product_id')
            ->andWhere('s.id = :store_id')
            ->andWhere('p.status <> :deleted')
            ->setParameter('product_id', $productId)
            ->setParameter('store_id', $storeId)
            ->setParameter('deleted', 'deleted')
            ->getQuery()
        ;

        $product = $query->getScalarResult();
        $product = $product ? current($product) : [];

        if (!$product) {
            $message = sprintf('Unable to find an active item object identified by id "%s".', $productId);

            throw new RuntimeException($message);
        }

        $product['p_images'] = $this->getProductFiles($product['p_id'], $itemType);

        return $product;
    }

    public function getDataWithDetailsById(int $productId, string $itemType = 'all')
    {
        $query = $this
            ->createQueryBuilder('p')
            ->select(['p', 's.id AS s_id', 's.name AS s_name', 's.provinceId as s_provinceId'])
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->where('p.id = :product_id')
            ->andWhere('p.status <> :deleted')
            ->setParameter('product_id', $productId)
            ->setParameter('deleted', 'deleted')
            ->getQuery()
        ;

        $product = $query->getScalarResult();
        $product = $product ? current($product) : [];

        if (!$product) {
            $message = sprintf('Unable to find an active item object identified by id "%s".', $productId);

            throw new RuntimeException($message);
        }

        $product['p_images'] = $this->getProductFiles($product['p_id'], $itemType);
        

        return $product;
    }

    public function getProductFiles(int $productId, string $fileType = 'all'): array
    {
        $query = $this
            ->createQueryBuilder('p')
            ->select(['f'])
            ->leftJoin(ProductFile::class, 'f', 'WITH', 'p.id = f.product')
            ->where('f.product = :product_id')
            ->andWhere('f.fileStatus = :publish')
            ->andWhere('f.filePath IS NOT NULL')
            ->andWhere('f.fileName IS NOT NULL')
            ->setParameter('product_id', $productId)
            ->setParameter('publish', 'publish')
        ;

        if ($fileType !== 'all') {
            $query
                ->andWhere('f.fileType = :file_type')
                ->setParameter('file_type', $fileType)
            ;
        }

        return $query->getQuery()->getArrayResult();
    }

    public function applyFilters(array $parameters = []): void
    {
        $dateFilteredBy = 'p.createdAt';

        if (isset($parameters['keywords']) && !empty($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('p.name', ':keywords'),
                    $this->builder->expr()->like('s.name', ':keywords')
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        if (isset($parameters['is_updated_at']) && $parameters['is_updated_at'] === true) {
            $dateFilteredBy = 'p.updatedAt';
        }

        if (isset($parameters['status']) && !empty($parameters['status'])) {
            // $this->builder
            //     ->andWhere('p.status = :status')
            //     ->setParameter('status', $parameters['status'])
            // ;
            $this->builder
                ->andWhere('p.status IN(:status)')
                ->setParameter('status', array_values($parameters['status']))
            ;
        }

        if (isset($parameters['store']) && !empty($parameters['store'])) {
            $this->builder
                ->andWhere('s.id = :store')
                ->setParameter('store', abs($parameters['store']))
            ;
        }

        // if (isset($parameters['price_min']) && abs($parameters['price_min']) > 0) {
        //     $this->builder
        //         ->andWhere($this->builder->expr()->orX(
        //             $this->builder->expr()->gte('p.price', ':price_min'),
        //             $this->builder->expr()->gte('p.basePrice', ':price_min')
        //         ))
        //         ->setParameter('price_min', abs($parameters['price_min']))
        //     ;
        // }

        // if (isset($parameters['price_max']) && abs($parameters['price_max']) > 0) {
        //     $this->builder
        //         ->andWhere($this->builder->expr()->orX(
        //             $this->builder->expr()->lte('p.price', ':price_max'),
        //             $this->builder->expr()->lte('p.basePrice', ':price_max')
        //         ))
        //         ->setParameter('price_max', abs($parameters['price_max']))
        //     ;
        // }

        if (isset($parameters['category']) && !empty($parameters['category'])) {
            $this->builder
                ->andWhere('p.category = :category')
                ->setParameter('category', abs($parameters['category']))
            ;
        }

    //     if (isset($parameters['pdn_or_non_product']) && !empty($parameters['pdn_or_non_product'])) {
    //         $this->builder
    //             ->andWhere('p.is_pdn = :jenis')
    //             ->setParameter('jenis', $parameters['pdn_or_non_product'])
    //         ;
    //     }

    //     if (isset($parameters['lkpp_filter']) && is_array($parameters['lkpp_filter'])) {
    //         $this->builder
    //             ->andWhere($this->builder->expr()->in('p.category', $parameters['lkpp_filter']))
    //         ;
    //     }

    //     if (isset($parameters['date_start']) && !empty($parameters['date_start'])) {
    //         $this->builder
    //             ->andWhere(sprintf('%s >= :date_start', $dateFilteredBy))
    //             ->setParameter('date_start', $parameters['date_start'])
    //         ;
    //     }

    //     if (isset($parameters['date_end']) && !empty($parameters['date_end'])) {
    //         $this->builder
    //             ->andWhere(sprintf('%s <= :date_end', $dateFilteredBy))
    //             ->setParameter('date_end', $parameters['date_end'])
    //         ;
    //     }

    //     if (isset($parameters['year']) && !empty($parameters['year'])) {
    //         $this->builder
    //             ->andWhere(sprintf('YEAR(%s) = :year', $dateFilteredBy))
    //             ->setParameter('year', abs($parameters['year']))
    //         ;
    //     }

    //     if (isset($parameters['admin_merchant_cabang']) && !empty($parameters['admin_merchant_cabang'])) {
    //         $this->builder
    //             ->andWhere('s.provinceId = :provinceId')
    //             ->setParameter('provinceId', $parameters['admin_merchant_cabang'])
    //         ;
    //     }


        if (isset($parameters['id_product_tayang']) && !empty($parameters['id_product_tayang'])) {
            $this->builder
                ->andWhere('p.idProductTayang = :idProductTayang')
                ->setParameter('idProductTayang', $parameters['id_product_tayang'])
            ;
        }
        


    }

    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder('p')
            ->where('p.id <> :id')
            ->andWhere('p.store = :store')
            ->andWhere('p.status <> :deleted')
            ->setParameter('id', 0)
            ->setParameter('store', $parameters['store'])
            ->setParameter('deleted', 'deleted')
        ;

        if (isset($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('p.name', ':keywords'),
                    $this->builder->expr()->like('p.description', ':keywords'),
                    $this->builder->expr()->like('p.keywords', ':keywords')
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }

    public function getSearchResult(array $parameters = [], bool $paginate = false): DBALQueryBuilder
    {
        $minPrice = isset($parameters['price']['min']) ? abs($parameters['price']['min']) : 0;
        $maxPrice = isset($parameters['price']['max']) ? abs($parameters['price']['max']) : 0;
        $fields = [
            'p.id AS id',
            'p.name AS name',
            'p.slug AS slug',
            'p.sku AS sku',
            'p.description AS description',
            'p.quantity AS quantity',
            'p.price AS price',
            'p.view_count AS view_count',
            'COUNT(pr.id) AS rating_count',
            'COALESCE(AVG(pr.rating), 0) AS avg_rating',
            'COALESCE(SUM(pr.rating), 0) AS total_rating',
            'p.is_pdn AS is_pdn',
            's.id AS s_id',
            's.name AS s_name',
            's.slug AS s_slug',
            's.city AS s_city',
            's.is_verified AS s_is_verified',
            's.is_pkp AS s_is_pkp',
            's.umkm_category AS s_umkm_category',
        ];

        if ($parameters['user_login'] != null && in_array($parameters['user_login']->getLkppLpseId(), [10000])) {
            $query = $this
            ->getEntityManager()
            ->getConnection()
            ->createQueryBuilder()
            ->select($fields)
            ->from('product', 'p')
            ->leftJoin('p', 'store', 's', 'p.store_id = s.id')
            ->leftJoin('p', 'product_review', 'pr', 'p.id = pr.product_id')
            ->where('p.id <> 0')
            ->andWhere('s.is_active = 1')
            ->andWhere('p.status = \'publish\'')
            ->andWhere("p.id_product_tayang LIKE :lpse")
            ->setParameter('lpse', '%'.$parameters['user_login']->getLkppLpseId().'%')
            ->groupBy('p.id');
        ;
        } else {
            $query = $this
            ->getEntityManager()
            ->getConnection()
            ->createQueryBuilder()
            ->select($fields)
            ->from('product', 'p')
            ->leftJoin('p', 'store', 's', 'p.store_id = s.id')
            ->leftJoin('p', 'product_review', 'pr', 'p.id = pr.product_id')
            ->where('p.id <> 0')
            ->andWhere('s.is_active = 1')
            ->andWhere('p.status = \'publish\'')
            ->groupBy('p.id');
        ;
        }

        $query
            ->andWhere($query->expr()->isNotNull('p.store_id'))
            ->andWhere($query->expr()->isNotNull('s.user_id'))
        ;

        if (isset($parameters['keywords'])) {
            /*$query
                ->andWhere($query->expr()->orX(
                    $query->expr()->like('p.name', '"%'.$parameters['keywords'].'%"'),
                    $query->expr()->like('p.description', '"%'.$parameters['keywords'].'%"'),
                    $query->expr()->like('s.name', '"%'.$parameters['keywords'].'%"')
                ))
            ;*/

            $rawQuery = 'p.name LIKE "%'.$parameters['keywords'].'%"';
            //$rawQuery .= ' OR p.description LIKE "%'.$parameters['keywords'].'%"';
            $rawQuery .= ' OR p.sku LIKE "%'.$parameters['keywords'].'%"';
            $rawQuery .= ' OR s.name LIKE "%'.$parameters['keywords'].'%"';

            if (isset($parameters['find_in_set']) && is_array($parameters['find_in_set'])) {
                if (count($parameters['find_in_set']) > 1) {
                    $rawQuery .= ' OR FIND_IN_SET("'.$parameters['keywords'].'", p.keywords)';
                }

                foreach ($parameters['find_in_set'] as $set) {
                    if (!empty($set)) {
                        $rawQuery .= ' OR FIND_IN_SET("'.$set.'", p.keywords)';
                    }
                }
            }

            $query->andWhere($rawQuery);
        }

        /*if (isset($parameters['find_in_set']) && is_array($parameters['find_in_set'])) {
            $findInSets = '';
            $counter = 0;

            if (count($parameters['find_in_set']) > 1) {
                $findInSets = 'FIND_IN_SET("'.$parameters['keywords'].'", p.keywords)';
                $counter = 1;
            }

            foreach ($parameters['find_in_set'] as $set) {
                if (!empty($set)) {
                    $findInSets .= $counter === 0 ? 'FIND_IN_SET("'.$set.'", p.keywords)' : ' OR FIND_IN_SET("'.$set.'", p.keywords)';
                    $counter++;
                }
            }

            if ($findInSets !== '') {
                $query->andWhere($findInSets);
            }
        }*/

        if (isset($parameters['province_id']) && !empty($parameters['province_id'])) {

            if (isset($parameters['with_province_id']) && !empty($parameters['with_province_id'])) {
                $provinceIds = [$parameters['with_province_id'], $parameters['province_id']];

                $query
                    ->andWhere($query->expr()->or($query->expr()->in('s.province_id' , $provinceIds)))
                ;

            }else {
                $query
                    ->andWhere($query->expr()->or($query->expr()->eq('s.province_id' , $parameters['province_id'])))
                ;
            }
        }

        if (isset($parameters['region']) && !empty($parameters['region'])) {
            $region = strtolower($parameters['region']);

            //$query->andWhere(sprintf('s.city = "%s"', $region));
            $query
                ->andWhere($query->expr()->orX(
                    $query->expr()->like('s.city', '"%'.$region.'%"')
                ))
            ;
        }

        /*if (isset($parameters['category']) && is_array($parameters['category'])) {
            $sql = '';
            $categories = array_values($parameters['category']);

            foreach ($categories as $index => $category) {
                if (abs($category) > 0) {
                    $sql .= $index === 0 ? 'FIND_IN_SET("'.$category.'", p.category)' : ' OR FIND_IN_SET("'.$category.'", p.category)';
                }
            }

            if ($sql !== '') {
                if (isset($parameters['sub_category']) && is_array($parameters['sub_category'])) {
                    $subCategories = array_values($parameters['sub_category']);

                    foreach ($subCategories as $subCategory) {
                        if (abs($subCategory) > 0) {
                            $sql .= ' OR FIND_IN_SET("'.$subCategory.'", p.category)';
                        }
                    }
                }

                if (isset($parameters['child_category']) && is_array($parameters['child_category'])) {
                    $childCategories = array_values($parameters['child_category']);

                    foreach ($childCategories as $childCategory) {
                        if (abs($childCategory) > 0) {
                            $sql .= ' OR FIND_IN_SET("'.$childCategory.'", p.category)';
                        }
                    }
                }

                $query->andWhere($sql);
            }
        }*/

        $sql = '';

        if (isset($parameters['category']) && is_array($parameters['category']) && count($parameters['category'])) {
            $categories = array_values($parameters['category']);

            foreach ($categories as $category) {
                if (abs($category) > 0) {
                    $sql .= $sql === '' ? 'FIND_IN_SET("'.$category.'", p.category)' : ' OR FIND_IN_SET("'.$category.'", p.category)';
                }
            }
        }

        if (isset($parameters['sub_category']) && is_array($parameters['sub_category']) && count($parameters['sub_category'])) {
            $subCategories = array_values($parameters['sub_category']);

            foreach ($subCategories as $subCategory) {
                if (abs($subCategory) > 0) {
                    $sql .= $sql === '' ? 'FIND_IN_SET("'.$subCategory.'", p.category)' : ' OR FIND_IN_SET("'.$subCategory.'", p.category)';
                }
            }
        }

        if (isset($parameters['child_category']) && is_array($parameters['child_category']) && count($parameters['child_category'])) {
            $childCategories = array_values($parameters['child_category']);

            foreach ($childCategories as $childCategory) {
                if (abs($childCategory) > 0) {
                    $sql .= $sql === '' ? 'FIND_IN_SET("'.$childCategory.'", p.category)' : ' OR FIND_IN_SET("'.$childCategory.'", p.category)';
                }
            }
        }

        if ($sql !== '') {
            $query->andWhere($sql);
        }

        if (isset($parameters['lkpp_filter']) && is_array($parameters['lkpp_filter'])) {
            $query->andWhere($query->expr()->in('p.category', $parameters['lkpp_filter']));
        }

        if (
            isset($parameters['lkpp_filter_store_classification']) &&
            is_array($parameters['lkpp_filter_store_classification']) &&
            count($parameters['lkpp_filter_store_classification']) > 0
        ) {
            $query->andWhere($query->expr()->in('s.business_criteria', $parameters['lkpp_filter_store_classification']));
        }

        if (isset($parameters['lkpp_filter_verified_store'])) {
            $query->andWhere($query->expr()->eq('s.is_verified', $parameters['lkpp_filter_verified_store']));
        }

        if (isset($parameters['store'])) {
            $query->andWhere(sprintf('s.slug = "%s"', $parameters['store']));
        }

        if (isset($parameters['min_quantity'])) {
            $query->andWhere($query->expr()->gte('p.quantity', (int) $parameters['min_quantity']));
        }

        if ($minPrice > 0) {
            $query->andWhere($query->expr()->gte('p.price', $minPrice));
        }

        if ($maxPrice > 0) {
            $query->andWhere($query->expr()->lte('p.price', $maxPrice));
        }

        if ($paginate && isset($parameters['limit'], $parameters['offset'])) {
            $query
                ->setMaxResults($parameters['limit'])
                ->setFirstResult($parameters['offset'])
            ;
        }

        if (isset($parameters['order_by'], $parameters['sort_by'])) {
            $query
                ->orderBy('s.is_verified', 'DESC')
                ->addOrderBy($parameters['order_by'], $parameters['sort_by'])
            ;
        }
        // dump($parameters['sort'] == 'terlaris');exit;
        if(isset($parameters['sort']) && $parameters['sort'] == 'terlaris'){
            $query
                ->leftjoin('p', 'order_product', 'op', 'p.id = op.product_id')
                ->orderBy('sum(op.quantity)', 'DESC')
                ->groupBy('p.id')
            ;
        }

        if (isset($parameters['sort']) && in_array($parameters['sort'], ['most_bought', 'terlaris'])) {
            $query
                ->leftjoin('p', 'order_product', 'op', 'p.id = op.product_id')
                ->orderBy('sum(op.quantity)', 'DESC')
                ->groupBy('p.id')
            ;
        }
        // echo $query->getSQL();
        return $query;
    }

    public function getRelatedProductsByStore(int $storeId, int $productId, $user): array
    {
       if($user != null && in_array($user->getLkppLpseId(), [10000])){
            $products = $this
            ->createQueryBuilder('p')
            ->select(['p'])
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin('p.files', 'pf')
            ->where('s.id = :store_id')
            ->andWhere('p.id <> :product_id')
            ->andWhere('p.status = :status')
            ->andWhere('pf.filePath IS NOT NULL')
            ->andWhere('pf.fileName IS NOT NULL')
            ->andWhere("p.idProductTayang LIKE :lpse")
            ->setParameter('store_id', $storeId)
            ->setParameter('product_id', $productId)
            ->setParameter('lpse', '%'.$user->getLkppLpseId().'%')
            ->setParameter('status', 'publish')
            ->setMaxResults(4)
            ->orderBy('rand()')
            ->getQuery()
            ->getScalarResult()
            ;
       } else {
         $products = $this
            ->createQueryBuilder('p')
            ->select(['p'])
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin('p.files', 'pf')
            ->where('s.id = :store_id')
            ->andWhere('p.id <> :product_id')
            ->andWhere('p.status = :status')
            ->andWhere('pf.filePath IS NOT NULL')
            ->andWhere('pf.fileName IS NOT NULL')
            ->setParameter('store_id', $storeId)
            ->setParameter('product_id', $productId)
            ->setParameter('status', 'publish')
            ->setMaxResults(4)
            ->orderBy('rand()')
            ->getQuery()
            ->getScalarResult()
        ;
       }
        // dd($products);
        if ($products) {
            foreach ($products as &$product) {
                $total = $this->getTotalProductReviewByProduct((int) $product['p_id']);
                $product['pr_total'] = (int) $total['count'];
                $product['avg_rating'] = (float) $total['avg_rating'];
            }
        }

        return $products;
    }


    // public function getSearchResult(array $parameters = [], bool $paginate = false): DBALQueryBuilder
    // {
    //     $minPrice = isset($parameters['price']['min']) ? abs($parameters['price']['min']) : 0;
    //     $maxPrice = isset($parameters['price']['max']) ? abs($parameters['price']['max']) : 0;

    //     $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
    //         ->select([
    //             'p.id AS id', 'p.name AS name', 'p.slug AS slug', 'p.sku AS sku',
    //             'p.description AS description', 'p.quantity AS quantity',
    //             'p.price AS price', 'p.view_count AS view_count',
    //             'p.rating_count AS rating_count', 'p.is_pdn AS is_pdn',
    //             's.id AS s_id', 's.name AS s_name', 's.slug AS s_slug',
    //             's.city AS s_city', 's.is_verified AS s_is_verified',
    //             's.is_pkp AS s_is_pkp', 's.umkm_category AS s_umkm_category',
    //         ])
    //         ->from('product', 'p')
    //         ->leftJoin('p', 'store', 's', 'p.store_id = s.id')
    //         ->where('p.id <> 0')
    //         ->andWhere('s.is_active = 1')
    //         ->andWhere('p.status = :status')
    //         ->setParameter('status', 'publish')
    //         ->andWhere('p.store_id IS NOT NULL')
    //         ->andWhere('s.user_id IS NOT NULL');

    //     if (!empty($parameters['keywords'])) {
    //         $query->andWhere(
    //             $query->expr()->orX(
    //                 $query->expr()->like('p.name', ':keywords'),
    //                 $query->expr()->like('p.sku', ':keywords'),
    //                 $query->expr()->like('s.name', ':keywords')
    //             )
    //         );
    //         $query->setParameter('keywords', '%'.$parameters['keywords'].'%');
    //     }

    //     if (!empty($parameters['province_id'])) {
    //         $query->andWhere('s.province_id = :province_id')
    //             ->setParameter('province_id', $parameters['province_id']);
    //     }

    //     if (!empty($parameters['region'])) {
    //         $query->andWhere('s.city LIKE :region')
    //             ->setParameter('region', '%'.strtolower($parameters['region']).'%');
    //     }

    //     if (!empty($parameters['category']) && is_array($parameters['category'])) {
    //         $query->andWhere('FIND_IN_SET(:category, p.category)')
    //             ->setParameter('category', implode(',', $parameters['category']));
    //     }

    //     if (!empty($parameters['store'])) {
    //         $query->andWhere('s.slug = :store')
    //             ->setParameter('store', $parameters['store']);
    //     }

    //     if (!empty($parameters['min_quantity'])) {
    //         $query->andWhere('p.quantity >= :min_quantity')
    //             ->setParameter('min_quantity', (int) $parameters['min_quantity']);
    //     }

    //     if ($minPrice > 0) {
    //         $query->andWhere('p.price >= :min_price')
    //             ->setParameter('min_price', $minPrice);
    //     }

    //     if ($maxPrice > 0) {
    //         $query->andWhere('p.price <= :max_price')
    //             ->setParameter('max_price', $maxPrice);
    //     }

    //     if ($paginate && isset($parameters['limit'], $parameters['offset'])) {
    //         $query->setMaxResults($parameters['limit'])
    //             ->setFirstResult($parameters['offset']);
    //     }

    //     if (!empty($parameters['order_by']) && !empty($parameters['sort_by'])) {
    //         $query->orderBy('s.is_verified', 'DESC')
    //             ->addOrderBy($parameters['order_by'], $parameters['sort_by']);
    //     }

    //     if (!empty($parameters['sort']) && in_array($parameters['sort'], ['most_bought', 'terlaris'])) {
    //         $query->leftJoin('p', 'order_product', 'op', 'p.id = op.product_id')
    //             ->orderBy('sum(op.quantity)', 'DESC')
    //             ->groupBy('p.id');
    //     }
    //     dump($parameters, $query);
    //     return $query;
    // }


    // public function getRelatedProductsByStore(int $storeId, int $productId): array
    // {
    //     $products = $this
    //         ->createQueryBuilder('p')
    //         ->select(['p'])
    //         ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
    //         ->where('s.id = :store_id')
    //         ->andWhere('p.id <> :product_id')
    //         ->andWhere('p.status = :status')
    //         ->setParameter('store_id', $storeId)
    //         ->setParameter('product_id', $productId)
    //         ->setParameter('status', 'publish')
    //         ->setMaxResults(4)
    //         ->orderBy('rand()')
    //         ->getQuery()
    //         ->getScalarResult()
    //     ;

    //     if ($products) {
    //         foreach ($products as &$product) {
    //             $total = $this->getTotalProductReviewByProduct((int) $product['p_id']);
    //             $product['pr_total'] = (int) $total['count'];
    //         }

    //         unset($product);
    //     }

    //     return $products;
    // }

    public function getShowcaseProducts(int $maxResult = 4, array $parameters = [], $user)
    {
        if ($user != null && in_array($user->getLkppLpseId(), [10000])) {
            $query = $this
                ->createQueryBuilder('p')
                ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
                ->leftJoin('p.files', 'pf')
                ->where('p.status = :status')
                ->andWhere('p.featured = :featured')
                ->andWhere('p.store IS NOT NULL')
                ->andWhere('s.isActive = 1')
                ->andWhere('s.user IS NOT NULL')
                ->andWhere('pf.filePath IS NOT NULL')
                ->andWhere('p.idProductTayang LIKE :lpse')
                ->setParameter('lpse', '%'.$user->getLkppLpseId().'%')
                ->setParameter('status', 'publish')
                ->setParameter('featured', true)
            ;
        } else {
            $query = $this
                ->createQueryBuilder('p')
                ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
                ->leftJoin('p.files', 'pf')
                ->where('p.status = :status')
                ->andWhere('p.featured = :featured')
                ->andWhere('pf.filePath IS NOT NULL')
                ->andWhere('p.store IS NOT NULL')
                ->andWhere('s.isActive = 1')
                ->andWhere('s.user IS NOT NULL')
                ->setParameter('status', 'publish')
                ->setParameter('featured', true)
            ;
        }
        // $query = $this
        //     ->createQueryBuilder('p')
        //     ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
        //     ->where('p.status = :status')
        //     ->andWhere('p.featured = :featured')
        //     ->andWhere('p.store IS NOT NULL')
        //     ->andWhere('s.isActive = 1')
        //     ->andWhere('s.user IS NOT NULL')
        //     ->andWhere('s.isVerified = :verified')
        //     ->setParameter('status', 'publish')
        //     ->setParameter('featured', true)
        //     ->setParameter('verified', true)
        // ;

        if (isset($parameters['allowed'])) {
            $query->andWhere($query->expr()->in('p.category', $parameters['allowed']));
        }

        return $query
            ->setMaxResults($maxResult)
            ->orderBy('rand()')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getRecomendationProducts(int $maxResult = 4)
    {
        // Masih pakai Random select record dari database
        return $this
            ->createQueryBuilder('p')
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->where('p.status = :status')
            ->andWhere('p.store IS NOT NULL')
            ->andWhere('s.user IS NOT NULL')
            ->setParameter('status', 'publish')
            ->setMaxResults($maxResult)
            ->orderBy('rand()')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalProductReviewByProduct(int $productId)
    {
        $product = $this->find($productId);
        $query = $this
            ->createQueryBuilder('p')
            ->select(['count(pr.id) AS count', 'COALESCE(AVG(pr.rating), 0) as avg_rating'])
            ->leftJoin(ProductReview::class, 'pr', 'WITH', 'pr.product = p.id')
            ->where('pr.id <> :id')
            ->andWhere('pr.product = :product')
            ->andWhere('pr.status = :status')
            ->setParameter('id', 0)
            ->setParameter('product', $product)
            ->setParameter('status', 'publish')
        ;
        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return [
            'count' => 0,
            'avg_rating' => 0,
        ];
    }

    public function getTotalProductReviewForStore(Store $store)
    {
        $query = $this
            ->createQueryBuilder('p')
            ->select(['count(pr.id) AS total_review', 'sum(pr.rating) AS total_rating'])
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin(ProductReview::class, 'pr', 'WITH', 'pr.product = p.id')
            ->where('pr.id <> :id')
            ->andWhere('s.id = :store')
            ->andWhere('pr.status = :status')
            ->setParameter('id', 0)
            ->setParameter('store', $store)
            ->setParameter('status', 'publish')
        ;

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return [
            'total_review' => 0,
            'total_rating' => 0,
        ];
    }

    public function getHighestAndLowestPrice(int $storeId = 0, float $lowestPrice = 1000.0, float $highestPrice = 1000000.0): array
    {
        $prices = [$lowestPrice, $highestPrice];
        $query = $this
            ->createQueryBuilder('p')
            ->select(['MIN(p.price) as lowest', 'MAX(p.price) as highest'])
            ->where('p.id <> :id')
            ->andWhere('p.status <> :deleted')
            ->setParameter('id', 0)
            ->setParameter('deleted', 'deleted')
        ;

        if ($storeId > 0) {
            $query
                ->andWhere('p.store = :store_id')
                ->setParameter('store_id', $storeId)
            ;
        }

        try {
            $results = $query->getQuery()->getSingleResult();
            $lowest = !empty($results['lowest']) ? (float) $results['lowest'] : $prices[0];
            $highest = !empty($results['highest']) ? (float) $results['highest'] : $prices[1];
            $prices = [$lowest, $highest];
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return $prices;
    }

    public function getStoreProductCount(Store $store)
    {
        $query = $this
            ->createQueryBuilder('p')
            ->select(['count(p.id) as product_total'])
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->where('s.id = :store_id')
            ->andWhere('p.status = :status')
            ->setParameter('store_id', $store)
            ->setParameter('status', 'publish')
        ;

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return [
            'product_total' => 0,
        ];
    }

    public function getDataForProductByCategoryChart(array $parameters = [])
    {
        $query = $this
            ->createQueryBuilder('p')
            ->select(['pc.id as category_id', 'pc.name as category', 'count(p.id) as total'])
            ->leftJoin(ProductCategory::class, 'pc', 'WITH', 'pc.id = p.category')
            //->leftJoin(ProductCategory::class, 'pc', 'WITH', 'FIND_IN_SET(pc.id, p.category) > 0')
            ->leftJoin(Store::class, 's', 'WITH', 's.id = p.store')
            ->where('p.id <> :id')
            ->andWhere('p.status <> :deleted')
            ->setParameter('id', 0)
            ->setParameter('deleted', 'deleted')
        ;

        $query
            ->andWhere($query->expr()->isNotNull('p.category'))
        ;

        if (isset($parameters['year']) && !empty($parameters['year'])) {
            $query
                ->andWhere('YEAR(p.createdAt) = :year')
                ->setParameter('year', abs($parameters['year']))
            ;
        }

        if (isset($parameters['admin_merchant_province']) && !empty($parameters['admin_merchant_province'])) {
            $query
                ->andWhere('s.provinceId = :province')
                ->setParameter('province', abs($parameters['admin_merchant_province']))
            ;
        }

        return $query->groupBy('pc.id')->getQuery()->getResult();
    }

    public function getProductsCreatedByStore(int $storeId, array $parameters = [])
    {
        $query = $this
            ->createQueryBuilder('p')
            ->select(['count(p.id) as total_new_products'])
            ->where('p.id <> :id')
            ->andWhere('p.status <> :deleted')
            ->andWhere('p.store = :store')
            ->setParameter('id', 0)
            ->setParameter('deleted', 'deleted')
            ->setParameter('store', $storeId)
        ;

        if (isset($parameters['start_date'])) {
            $query
                ->andWhere('p.createdAt >= :start_date')
                ->setParameter('start_date', $parameters['start_date'])
            ;
        }

        if (isset($parameters['end_date'])) {
            $query
                ->andWhere('p.createdAt <= :end_date')
                ->setParameter('end_date', $parameters['end_date'])
            ;
        }

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return [
            'total_new_products' => 0,
        ];
    }

    protected function dataExportBaseBuilder(): QueryBuilder
    {
        return $this
            ->createQueryBuilder('p')
            ->select(['p', 'pc.name as pc_name', 's.name as s_name'])
            ->leftJoin(ProductCategory::class, 'pc', 'WITH', 'pc.id = p.category')
            ->leftJoin(Store::class, 's', 'WITH', 's.id = p.store')
            ->where('p.id <> :id')
            ->andWhere('p.status <> :deleted')
            ->setParameter('id', 0)
            ->setParameter('deleted', 'deleted')
        ;
    }

    public function getRecommendationProducts(int $maxResult = 4, $user)
    {

        if ($user != null && in_array($user->getLkppLpseId(), [10000])) {
            // dd($user);
            return $this
            ->createQueryBuilder('p')
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin('p.files', 'f')
            ->where('p.status = :status')
            ->andWhere('s.isActive = 1')
            ->andWhere('p.store IS NOT NULL')
            ->andWhere('s.user IS NOT NULL')
            ->andWhere('f.filePath IS NOT NULL')
            ->andWhere("p.idProductTayang LIKE :idlpse")
            ->setParameter('status', 'publish')
            ->setParameter('idlpse',   '%'.$user->getLkppLpseId().'%')
            ->setMaxResults($maxResult)
            ->orderBy('rand()')
            ->getQuery()
            ->getResult()
        ;
        } else {
            return $this
            ->createQueryBuilder('p')
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin('p.files', 'f')
            ->where('p.status = :status')
            ->andWhere('s.isActive = 1')
            ->andWhere('p.store IS NOT NULL')
            ->andWhere('s.user IS NOT NULL')
            ->andWhere('f.filePath IS NOT NULL')
            ->setParameter('status', 'publish')
            ->setMaxResults($maxResult)
            ->orderBy('rand()')
            ->getQuery()
            ->getResult()
        ;
        }
        // $query = $this
        // ->createQueryBuilder('p')
        // ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
        // ->where('p.status = :status')
        // ->andWhere('s.isActive =: storeActive')
        // ->setParameter('storeActive', true)
        // ->setParameter('status', 'publish');

        // $query
        // ->andWhere($query->expr()->isNotNull('p.store'));
        // $query
        // ->setMaxResults($maxResult)
        // ->orderBy('rand()');
        // return $query->getQuery()->getResult();
    }

    public function getShowcaseProductsByCategory(int $maxResult = 3, int $categoryId = 0): array
    {
        $fields = [
            'p.id AS id',
            'p.name AS name',
            'p.slug AS slug',
            'p.price AS price',
            'p.sku AS sku',
            's.id AS s_id',
            's.name AS s_name',
            's.slug AS s_slug',
            'pf.filePath AS image',
        ];

        return $this
            ->createQueryBuilder('p')
            ->select($fields)
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin(ProductCategory::class, 'pc', 'WITH', 'p.category = pc.id')
            ->leftJoin(ProductFile::class, 'pf', 'WITH', 'p.id = pf.product')
            ->where('p.status = :status')
            ->andWhere('p.store IS NOT NULL')
            ->andWhere('s.user IS NOT NULL')
            ->andWhere('s.isActive = 1')
            ->andWhere('p.category = :category_id')
            ->setParameter('status', 'publish')
            ->setParameter('category_id', $categoryId)
            ->orderBy('p.viewCount')
            ->orderBy('pf.createdAt', 'DESC')
            ->groupBy('p.id')
            ->setMaxResults($maxResult)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getShowcaseProductsByCategoryV2(int $maxResult = 3, array $categoryIDs = [], $user): array
    {
        $fields = [
            'p.id AS id',
            'p.name AS name',
            'p.slug AS slug',
            'p.price AS price',
            'p.sku AS sku',
            's.id AS s_id',
            's.name AS s_name',
            's.slug AS s_slug',
            'pf.filePath AS image',
            'pc.id as pc_id'
        ];

        if ($user != null && in_array($user->getLkppLpseId(), [10000])) {
            $result = $this
                ->createQueryBuilder('p')
                ->select($fields)
                ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
                ->leftJoin(ProductCategory::class, 'pc', 'WITH', 'p.category = pc.id')
                ->leftJoin(ProductFile::class, 'pf', 'WITH', 'p.id = pf.product')
                ->where('p.status = :status')
                ->andWhere('p.store IS NOT NULL')
                ->andWhere('s.user IS NOT NULL')
                ->andWhere('s.isActive = 1')
                ->andWhere('pf.filePath IS NOT NULL')
                ->andWhere("p.idProductTayang LIKE :idlpse")
                ->andWhere('p.category IN (:category_id)')
                ->setParameter('status', 'publish')
                ->setParameter('idlpse', "%".$user->getLkppLpseId()."%")
                ->setParameter('category_id', $categoryIDs)
                ->orderBy('p.viewCount')
                ->orderBy('pf.createdAt', 'DESC')
                ->groupBy('p.id')
                ->setMaxResults($maxResult)
                ->getQuery()
                ->getResult()
            ;
        } else {
            $result = $this
                ->createQueryBuilder('p')
                ->select($fields)
                ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
                ->leftJoin(ProductCategory::class, 'pc', 'WITH', 'p.category = pc.id')
                ->leftJoin(ProductFile::class, 'pf', 'WITH', 'p.id = pf.product')
                ->where('p.status = :status')
                ->andWhere('pf.filePath IS NOT NULL')
                ->andWhere('p.store IS NOT NULL')
                ->andWhere('s.user IS NOT NULL')
                ->andWhere('s.isActive = 1')
                ->andWhere('p.category IN (:category_id)')
                ->setParameter('status', 'publish')
                ->setParameter('category_id', $categoryIDs)
                ->orderBy('p.viewCount')
                ->orderBy('pf.createdAt', 'DESC')
                ->groupBy('p.id')
                ->setMaxResults($maxResult)
                ->getQuery()
                ->getResult()
            ;
        }
        $resultByCategory = [];
        foreach($result as $product){
            if(!isset($resultByCategory[$product['pc_id']])){
                $resultByCategory[$product['pc_id']] = [];
            } 

            array_push($resultByCategory[$product['pc_id']], $product);
        }
        return array_values($result);       
    }

    public function getDataForComparison(array $parameters = [])
    {
        $query = $this
            ->createQueryBuilder('p')
            ->select(['p.id as id', 'p.name as name'])
            ->where('p.status = :status')
            ->setParameter('status', 'publish')
        ;

        if (isset($parameters['term']) && !empty($parameters['term'])) {
            $query
                ->andWhere($query->expr()->like('p.name', ':term'))
                ->setParameter('term', '%'.$parameters['term'].'%')
            ;
        }

        return $query->getQuery()->getScalarResult();
    }

    public function findProductsToCompare(array $productIds)
    {
        $query = $this
            ->createQueryBuilder('p')
            ->select(['p', 'pc.name as pc_name', 's.id as s_id', 's.name as s_name', 's.slug as s_slug', 's.city as s_city', 's.isVerified as s_isVerified', 's.isPKP as s_isPKP','s.umkm_category AS s_umkm_category',])
            ->leftJoin(ProductCategory::class, 'pc', 'WITH', 'p.category = pc.id')
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->where('p.id <> :id')
            ->setParameter('id', 0)
        ;

        $query->andWhere($query->expr()->in('p.id', $productIds));

        return $query->getQuery()->getScalarResult();
    }

    public function getProductsCategoryByStore(int $storeId){
        $fields = [
            'p.id AS id',
            'p.name AS name',
            'p.slug AS slug',
            'p.price AS price',
            'p.sku AS sku',
            'pc.name as category_name'
        ];

        return $this
            ->createQueryBuilder('p')
            ->select($fields)
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin(ProductCategory::class, 'pc', 'WITH', 'p.category = pc.id')
            ->where('p.status = :status')
            ->andWhere('p.store IS NOT NULL')
            ->andWhere('p.store = :store_id')
            ->setParameter('store_id', $storeId)
            ->setParameter('status', 'publish')
            ->groupBy('pc.id')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getProductData(array $parameters)
    {
        $q = $this->createQueryBuilder('p')
            ->select(['p'])
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->where('p.status = :status')
            ->setParameter('status', 'publish')
            ;

        if (isset($parameters['category'])) {
            $q->andWhere('p.category IN (:category)')
                ->setParameter('category', $parameters['category']);
        }

        if (isset($parameters['name'])) {
            $q->andWhere($q->expr()->orX(
                $q->expr()->like('p.name', ':name')
            ))->setParameter('name', '%'.$parameters['name'].'%');
        }

        if (isset($parameters['store'])) {
            $q->andWhere('p.store = :store')->setParameter('store', (int) $parameters['store']);
        }

        if (isset($parameters['page'], $parameters['per_page'])) {
            $q->setMaxResults($parameters['per_page'])
                ->setFirstResult($parameters['per_page'] * ($parameters['page'] - 1));
        }

        return $q->getQuery()->getResult();
    }

    public function getProductByStoreId(int $storeId)
    {
        $fields = [
            'p.id AS id',
            'pc.id as pc_id'
        ];

        return $this
            ->createQueryBuilder('p')
            ->select($fields)
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin(ProductCategory::class, 'pc', 'WITH', 'p.category = pc.id')
            ->where('p.store IS NOT NULL')
            ->andWhere('p.store = :store_id')
            ->setParameter('store_id', $storeId)
            ->groupBy('pc.id')
            ->getQuery()
            ->getResult()
            ;
    }

    public function getProductByProvince(int $provinceId, $user): array
    {
        if($user != null && in_array($user->getLkppLpseId(), [10000])){ 
            return $this
            ->createQueryBuilder('p')
            ->select(['p'])
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin('p.files', 'f')
            ->where('p.store IS NOT NULL')
            ->andWhere('p.status = :status')
            ->andWhere('f.filePath IS NOT NULL')
            ->andWhere('s.provinceId = :provinceId')
            ->andWhere('s.isActive = 1')
            ->andWhere('p.idProductTayang LIKE :idlpse')
            ->setParameter('idlpse', '%'.$user->getLkppLpseId().'%')
            ->setParameter('status', 'publish')
            ->setParameter('provinceId', $provinceId)
            ->orderBy('rand()')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
        } else {
            return $this
            ->createQueryBuilder('p')
            ->select(['p'])
            ->leftJoin(Store::class, 's', 'WITH', 'p.store = s.id')
            ->leftJoin('p.files', 'f')
            ->where('p.store IS NOT NULL')
            ->andWhere('p.status = :status')
            ->andWhere('f.filePath IS NOT NULL')
            ->andWhere('s.provinceId = :provinceId')
            ->andWhere('s.isActive = 1')
            ->setParameter('status', 'publish')
            ->setParameter('provinceId', $provinceId)
            ->orderBy('rand()')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
        }
    }

}
