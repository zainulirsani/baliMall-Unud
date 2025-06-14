<?php

namespace App\Controller\Store;

use App\Controller\PublicController;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProductReview;
use App\Entity\Store;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductReviewRepository;
use App\Repository\StoreRepository;
use App\Service\BreadcrumbService;
use App\Utility\CustomPaginationTemplate;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Exception;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class StoreController extends PublicController
{
    public function storePage($store, ?Profiler $profiler)
    {
        if (null !== $profiler) {
            $profiler->disable();
        }

        $storeData = $this->getStoreData($store);
        /** @var User $owner */
        $owner = $storeData->getUser();

        $this->validateStoreOwner($owner, $store);

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        $productCount = $repository->getStoreProductCount($storeData);
        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        $page = $page < 1 ? 1 : $page;
        $keywords = $request->query->get('keywords', null);
        $category = (array) $request->query->get('category1', null);
        $subCategory = (array) $request->query->get('category2', null);
        $childCategory = (array) $request->query->get('category3', null);
        $price = (array) $request->query->get('price', null);
        $sort = $request->query->get('sort', null);
        $limit = $this->getParameter('item_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $pageHeader = $storeData->getName();
        $prices = $repository->getHighestAndLowestPrice($storeData->getId());
        $parameters = [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'category' => $category,
            'sub_category' => $subCategory,
            'child_category' => $childCategory,
            'price' => $price,
            'sort' => $sort,
            'store' => $store,
            'order_by' => 'p.id',
            'sort_by' => 'DESC',
            'user_login' => $this->getUser(),
        ];

        $cacheKey = 'user_lkpp_restricted_categories';
        /** @var SessionInterface $session */
        $session = $this->getSession();
        $lkppUser = $session->has($cacheKey);

        if ($lkppUser) {
            $restricted = [];
            $allowedCategories = [];
            $cache = $this->getCache();

            try {
                if ($cache->hasItem($cacheKey)) {
                    /** @var CacheItem $restricted */
                    $restricted = $cache->getItem($cacheKey);
                    $restricted = $restricted->get();
                }
            } catch (InvalidArgumentException $e) {
            }

            foreach ($restricted as $items) {
                foreach ($items as $item) {
                    $allowedCategories[] = $item;
                }
            }

            if (count($allowedCategories) > 0) {
                $parameters['lkpp_filter'] = $allowedCategories;
            }

            $restrictedMerchantClassification = $session->get('user_lkpp_restricted_merchant_classification');

            $parameters['lkpp_filter_store_classification'] = $restrictedMerchantClassification;
            $parameters['lkpp_filter_verified_store'] = 1;
        }

        if (!empty($keywords)) {
            $parameters['keywords'] = filter_var($keywords, FILTER_SANITIZE_STRING);
            $parameters['find_in_set'] = explode(' ', $keywords);
        }

        switch ($sort) {
            case 'cheapest':
                $parameters['order_by'] = 'p.price';
                $parameters['sort_by'] = 'ASC';
                break;
            case 'most_expensive':
                $parameters['order_by'] = 'p.price';
                $parameters['sort_by'] = 'DESC';
                break;
            case 'latest':
                $parameters['order_by'] = 'p.id';
                $parameters['sort_by'] = 'DESC';
                break;
            case 'oldest':
                $parameters['order_by'] = 'p.id';
                $parameters['sort_by'] = 'ASC';
                break;
            case 'a_to_z':
                $parameters['order_by'] = 'p.name';
                $parameters['sort_by'] = 'ASC';
                break;
            case 'z_to_a':
                $parameters['order_by'] = 'p.name';
                $parameters['sort_by'] = 'DESC';
                break;
        }

        try {
            $queryBuilder = $repository->getSearchResult($parameters);
             $countQueryBuilder = function ($qb) {
                $countQb = clone $qb;
                $countQb
                    ->resetQueryParts(['select', 'orderBy', 'groupBy', 'having']) // Clean up
                    ->setFirstResult(null)
                    ->setMaxResults(null)
                    ->select('COUNT(DISTINCT p.id) AS total_results');

                return $countQb;
            };

            $adapter = new DoctrineDbalAdapter($queryBuilder, $countQueryBuilder);
            $pagination = New Pagerfanta($adapter);
            $pagination
                ->setMaxPerPage($limit)
                ->setCurrentPage($page)
            ;

            $view = new DefaultView(new CustomPaginationTemplate());
            $options = ['proximity' => 3];
            $html = $view->render($pagination, $this->routeGenerator($parameters), $options);
            $queryBuilder = $repository->getSearchResult($parameters, true);
            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();
            $products = $conn->executeQuery($queryBuilder->getSQL())->fetchAll(FetchMode::ASSOCIATIVE);


            foreach ($products as &$product) {
                $total = $repository->getTotalProductReviewByProduct((int) $product['id']);
                $product['pr_total'] = (int) $total['count'];
                $product['avg_rating'] = (float) $total['avg_rating'];
            }
            unset($product);
        } catch (Exception $e) {
            $products = [];
            $pagination = $html = null;
        }

        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->getRepository(Order::class);

        $statistic = [
            'product' => $repository->getStoreProductCount($storeData),
            'review' => $repository->getTotalProductReviewForStore($storeData),
            'order' => $orderRepository->getTotalProductSoldForStore($storeData),
        ];

        BreadcrumbService::add(['label' => $pageHeader]);

        // dd($products, $pagination);

        return $this->view('@__main__/public/store/page.html.twig', [
            'page_header' => $pageHeader,
            'store_data' => $storeData,
            'products' => $products,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
            'statistic' => $statistic,
            'lowest_price' => min($prices),
            'highest_price' => max($prices),
            'product_count' => count($productCount)
        ]);
    }

    public function productPage($store, $product)
    {
        $storeData = $this->getStoreData($store);
        /** @var User $user */
        $user = $this->getUser();
        /** @var User $owner */
        $owner = $storeData->getUser();
        $isOwner = false;

        $this->validateStoreOwner($owner, $store);

        if ($user instanceof User && (int) $user->getId() === (int) $owner->getId()) {
            $isOwner = true;
        }

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        /** @var Product $productData */
        $productData = $repository->findOneBy([
            'store' => $storeData,
            'slug' => $product,
            'status' => 'publish',
        ]);

        if (!$productData instanceof Product) {
            throw new NotFoundHttpException(sprintf('Cannot find product "%s" by store "%s"', $product, $store));
        }

        $cache = $this->getCache();
        $cacheKey = 'user_lkpp_restricted_categories';
        /** @var SessionInterface $session */
        $session = $this->getSession();
        $lkppUser = $session->has($cacheKey);
        $restricted = [];

        try {
            if ($cache->hasItem($cacheKey)) {
                /** @var CacheItem $restricted */
                $restricted = $cache->getItem($cacheKey);
                $restricted = $restricted->get();
            }
        } catch (InvalidArgumentException $e) {
        }

        if ($lkppUser) {
            $allowed = [];

            foreach ($restricted as $key => $items) {
                $allowed[] = $key;

                foreach ($items as $item) {
                    $allowed[] = $item;
                }
            }

            if (!in_array((int) $productData->getCategory(), $allowed, false)) {
                $this->get('logger')->error('[LKPP USER] Accessing product with category outside the domain!', [
                    'attempt' => (int) $productData->getCategory(),
                    'allowed_categories' => $allowed,
                ]);

                return $this->view('@__main__/public/product/exception/lkpp.html.twig');
            }
        }

        $productFiles = $repository->getProductFiles($productData->getId());
        $relatedProducts = $repository->getRelatedProductsByStore($storeData->getId(), $productData->getId(), $user);
        // dd($relatedProducts);
        /** @var ProductReviewRepository $reviewRepository */
        $reviewRepository = $this->getRepository(ProductReview::class);

        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        $state = $request->query->get('state', null);
        $page = $page < 1 ? 1 : $page;
        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'product' => $productData,
            'status' => 'publish',
            'order_by' => 'pr.id',
            'sort_by' => 'DESC',
        ];

        

        try {
            $adapter = new DoctrineORMAdapter($reviewRepository->getPaginatedResult($parameters));
            // dd($adapter);
            $pagination = New Pagerfanta($adapter);
            $pagination
                ->setMaxPerPage($limit)
                ->setCurrentPage($page)
            ;

            $view = new DefaultView(new CustomPaginationTemplate());
            $options = ['proximity' => 3];

            $html = $view->render($pagination, $this->reviewRouteGenerator([
                'page' => $page,
                'store' => $store,
                'product' => $product,
            ]), $options);

            $productReviews = $adapter->getQuery()->getScalarResult();
        } catch (Exception $e) {
            $productReviews = [];
            $pagination = $html = null;
        }

        $total = $reviewRepository->getPaginatedResult($parameters, true)->getQuery()->getSingleResult();
        $totalReviews = (int) $total['count'];
        $router = $this->get('router');
        $pageHeader = $productData->getName();

        $getProductReviews = $reviewRepository->getPaginatedResult($parameters)->getQuery()->getScalarResult();
        $averageProductReviews = [];
        if (!empty($productReviews)) {          
            foreach ($productReviews as $pr) {
                $averageProductReviews['pr_rating'][] = $pr['pr_rating'];
            }
        } else {
            foreach ($getProductReviews as $pr) {
                $averageProductReviews['pr_rating'][] = $pr['pr_rating'];
            }
        }
        
        if (empty($averageProductReviews['pr_rating'])) {
            $avgRating = 0;
        } else {
            $avgRating = array_sum($averageProductReviews['pr_rating']) / count($averageProductReviews['pr_rating'], 2);
        }
        // dd($productData, $total, $productReviews,  round($avgRating, 1));

        if ($state === 'search') {
            BreadcrumbService::add(
                ['label' => $this->getTranslation('title.search_result'), 'id' => 'state-search'],
                ['label' => $storeData->getName(), 'href' => $router->generate('store_page', ['store' => $store])],
                ['label' => $pageHeader]
            );
        } else {
            BreadcrumbService::add(
                ['label' => $storeData->getName(), 'href' => $router->generate('store_page', ['store' => $store])],
                ['label' => $pageHeader]
            );
        }
        // dd($storeData, $productData, $productFiles, $productReviews, $relatedProducts, $totalReviews);

        return $this->view('@__main__/public/store/product.html.twig', [
            'page_header' => $pageHeader,
            'store_data' => $storeData,
            'product' => $productData,
            'product_files' => $productFiles,
            'product_reviews' => $productReviews ?? $getProductReviews ?? [],
            'avg_rating' => round($avgRating, 1),
            'product_category' => $this->getCategoryNameFromProduct($productData),
            'related_products' => $relatedProducts,
            'is_owner' => $isOwner,
            'html' => $html,
            'pagination' => $pagination,
            'total_reviews' => $totalReviews,
        ]);
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            $query = [
                'page' => $page,
                'store' => $parameters['store'],
                //'keywords' => '',
                'category1' => '',
                'category2' => '',
                'category3' => '',
                'price' => '',
                'sort' => '',
            ];

            /*if (isset($parameters['keywords'])) {
                $query['keywords'] = $parameters['keywords'];
            }*/

            if (isset($parameters['category'])) {
                $query['category1'] = $parameters['category'];
            }

            if (isset($parameters['sub_category'])) {
                $query['category2'] = $parameters['sub_category'];
            }

            if (isset($parameters['child_category'])) {
                $query['category3'] = $parameters['child_category'];
            }

            if (isset($parameters['price'])) {
                $query['price'] = $parameters['price'];
            }

            if (isset($parameters['sort'])) {
                $query['sort'] = $parameters['sort'];
            }

            return $this->get('router')->generate('store_page', $query);
        };
    }

    private function reviewRouteGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            $query = [
                'page' => $page,
                'store' => $parameters['store'],
                'product' => $parameters['product'],
            ];

            return $this->get('router')->generate('product_page', $query);
        };
    }

    private function getStoreData(string $store): Store
    {
        // dd($store);
        /** @var StoreRepository $repository */
        $repository = $this->getRepository(Store::class);
        /** @var Store $storeData */
        $storeData = $repository->findOneBy([
            'slug' => $store,
            'isActive' => true,
        ]);

        if (empty($storeData)) {
            throw new NotFoundHttpException(sprintf('Cannot find store "%s"', $store));
        }

        return $storeData;
    }

    private function validateStoreOwner(?User $owner, $store): void
    {
        if (!$owner instanceof User) {
            throw new NotFoundHttpException(sprintf('Store "%s" does not seem to have an owner', $store));
        }

        if (!$owner->getIsActive() || $owner->getIsDeleted()) {
            throw new NotFoundHttpException(sprintf('Store owner of "%s" is either disabled or deleted!', $store));
        }
    }
}
