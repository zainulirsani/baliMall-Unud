<?php

namespace App\Controller\Product;

use App\Controller\PublicController;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Store;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Service\BreadcrumbService;
use App\Service\RajaOngkirService;
use App\Utility\CustomPaginationTemplate;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Exception;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ProductController extends PublicController
{
    public function index(?Profiler $profiler)
    {
        if (null !== $profiler) {
            $profiler->disable();
        }

        /** @var User $user */
        $user = $this->getUser();



        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        $page = $page < 1 ? 1 : $page;
        $keywords = $request->query->get('keywords', null);
        $category = (array) $request->query->get('category1', null);
        $subCategory = (array) $request->query->get('category2', null);
        $childCategory = (array) $request->query->get('category3', null);
        $price = (array) $request->query->get('price', null);
        $sort = $request->query->get('sort', null);
        $provinceId = abs($request->get('province_id', null));
        $withProvinceId = abs($request->get('with_province_id', null));
        $region = $request->query->get('region', null);
        $limit = $this->getParameter('item_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $pageHeader = $this->getTranslation('title.search_result');
        $prices = $repository->getHighestAndLowestPrice();
        $rajaOngkir = $this->get(RajaOngkirService::class);
        /** @var SessionInterface $session */
        $session = $this->getSession();
        $lkppUser = $session->has('user_lkpp_restricted_categories');

        if (!empty($category)) {
            /** @var ProductCategoryRepository $categoryRepository */
            $categoryRepository = $this->getRepository(ProductCategory::class);
            $categories = array_values($category);
            $tempSub = [];
            $tempChild = [];

            foreach ($categories as $value) {
                $children1 = $categoryRepository->getChildrenCategoryData((int) $value);
                $i = 1;

                foreach ($children1 as $ch1) {
                    $subId = $ch1['id'];
                    $tempSub[$i] = $subId;
                    $children2 = $categoryRepository->getChildrenCategoryData($subId);
                    $j = 1;
                    $i++;

                    foreach ($children2 as $ch2) {
                        $childId = $ch2['id'];
                        $tempChild[$j] = $childId;
                        $j++;
                    }
                }
            }

            $subCategory = $tempSub;
            $childCategory = $tempChild;
        }

        if ($lkppUser) {
            $logger = $this->get('logger');

            if (empty($category)) {
                $logger->error('LKPP user tried to access category outside the domain!');

                return $this->createNotFoundException();
            }

            $restricted = $session->get('user_lkpp_restricted_categories');

            foreach ($category as $key => $item) {
                if ((!array_key_exists($key, $restricted))
                    || (array_key_exists($key, $restricted) && !in_array($item, $restricted[$key], false))) {
                    $logger->error('LKPP user tried to access category outside the domain!');

                    return $this->createNotFoundException();
                }
            }
        }

        $parameters = [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'category' => $category,
            'sub_category' => $subCategory,
            'child_category' => $childCategory,
            'price' => $price,
            'sort' => $sort,
            'region' => $region,
            'order_by' => 'p.id',
            'sort_by' => 'DESC',
            'min_quantity' => 1,
            'province_id' => $provinceId,
            'user_login' => $user? $user : null
        
        ];

        if (!empty($withProvinceId)) {
            $parameters['with_province_id'] = $withProvinceId;
        }

        if ($lkppUser) {
            $restrictedMerchantClassification = $session->get('user_lkpp_restricted_merchant_classification');

            $parameters['lkpp_filter_store_classification'] = $restrictedMerchantClassification;
            $parameters['lkpp_filter_verified_store'] = 1;
        }

        if (!empty($keywords)) {
            $label = $this->getTranslator()->trans('title.search_result');
            $pageHeader = sprintf('%s "%s"', $label, $keywords);
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
            // $queryBuilder = $repository->getSearchResult($parameters, true);
            // /** @var Connection $conn */
            // $conn = $this->getDoctrine()->getConnection();
            // $products = $conn->executeQuery($queryBuilder->getSQL())->fetchAll(FetchMode::ASSOCIATIVE);
            $queryBuilder = $repository->getSearchResult($parameters, true);
            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();
            $stmt = $conn->prepare($queryBuilder->getSQL());
            // dd($this->getUser());
            
            foreach ($queryBuilder->getParameters() as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            $products = $stmt->fetchAll(FetchMode::ASSOCIATIVE);

            // $products = $conn->executeQuery($queryBuilder->getSQL())->fetchAll(FetchMode::ASSOCIATIVE);

            // dd($products, $pagination, $parameters, $queryBuilder, $countQueryBuilder, $pagination->getNbPages());

            foreach ($products as &$product) {
                $total = $repository->getTotalProductReviewByProduct((int) $product['id']);
                $product['pr_total'] = (int) $total['count'];
                $product['avg_rating'] = (float) $total['avg_rating'];
            }
            unset($product);
        } catch (Exception $e) {
            $products = [];
            $pagination = $html = null;
            // dd($e, $parameters, $queryBuilder);
        }

        BreadcrumbService::add(['label' => $this->getTranslation('title.search_result')]);

        // dd([$products, $pagination, $html, $parameters]);

        return $this->view('@__main__/public/product/index.html.twig', [
            'page_header' => $pageHeader,
            'products' => $products,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
            'lowest_price' => min($prices),
            'highest_price' => max($prices),
            'province_id' => $provinceId,
            'province_data' => $rajaOngkir->getProvince(),
            'city_data' => $this->manipulateCitiesData(),
        ]);
    }

    public function viewCount()
    {
        $this->isAjaxRequest('POST');

        $response = ['status' => false];
        $request = $this->getRequest();
        $slug = $request->request->get('main', null);
        $dirSlug = $request->request->get('sub', null);
        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        /** @var Product $product */
        $product = $repository->findOneBy([
            'slug' => $slug,
            'dirSlug' => $dirSlug,
            'status' => 'publish',
        ]);

        if ($product instanceof Product) {
            /** @var Store $store */
            $store = $product->getStore();

            if (!empty($store) && $store->getIsActive()) {
                $product->incrementViewCount();

                $em = $this->getEntityManager();
                $em->persist($product);
                $em->flush();

                $response['status'] = true;
            }
        }

        return $this->view('', $response, 'json');
    }

    public function compare(): Response
    {
        $request = $this->getRequest();
        $productId1 = $request->query->get('product1', '');
        $productId2 = $request->query->get('product2', '');
        $productId3 = $request->query->get('product3', '');
        $product1 = $product2 = $product3 = null;
        $productIds = [];

        if (!empty($productId1)) {
            $productIds[] = abs($productId1);
        } elseif (!empty($productId2)) {
            $productIds[] = abs($productId2);
        } elseif (!empty($productId3)) {
            $productIds[] = abs($productId3);
        }

        if (count($productIds) > 0) {
            /** @var ProductRepository $repository */
            $repository = $this->getRepository(Product::class);
            $products = $repository->findProductsToCompare($productIds);

            foreach ($products as $product) {
                if (!empty($productId1) && (int) $productId1 === (int) $product['p_id']) {
                    $total = $repository->getTotalProductReviewByProduct((int) $product['p_id']);
                    $product1 = $product;
                    $product1['pr_total'] = (int) $total['count'];
                }

                if (!empty($productId2) && (int) $productId2 === (int) $product['p_id']) {
                    $total = $repository->getTotalProductReviewByProduct((int) $product['p_id']);
                    $product2 = $product;
                    $product2['pr_total'] = (int) $total['count'];
                }

                if (!empty($productId3) && (int) $productId3 === (int) $product['p_id']) {
                    $total = $repository->getTotalProductReviewByProduct((int) $product['p_id']);
                    $product3 = $product;
                    $product3['pr_total'] = (int) $total['count'];
                }
            }

            //dd($product1, $product2, $product3);
        }

        return $this->view('@__main__/public/product/compare.html.twig', [
            'page_title' => 'title.page.compare_products',
            'product1' => $product1,
            'product2' => $product2,
            'product3' => $product3,
        ]);
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            $query = [
                'page' => $page,
                'keywords' => '',
                'category1' => '',
                'category2' => '',
                'category3' => '',
                'price' => '',
                'sort' => '',
            ];

            if (isset($parameters['keywords'])) {
                $query['keywords'] = $parameters['keywords'];
            }

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

            if (isset($parameters['region'])) {
                $query['region'] = $parameters['region'];
            }

            if (isset($parameters['province_id'])) {
                $query['province_id'] = $parameters['province_id'];
            }

            if (isset($parameters['with_province_id'])) {
                $query['with_province_id'] = $parameters['with_province_id'];
            }

            return $this->get('router')->generate('search', $query);
        };
    }
}
