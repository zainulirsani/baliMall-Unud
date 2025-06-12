<?php

namespace App\Plugins;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;

class ProductPlugin extends BasePlugin
{
    public function showcase()
    {
        $data = [
            'products' => $this->getSelectedProducts(),
            'main' => [
                'province_id' => 1,
                'region' => '',
                'img' => 'dist/img/merchant-bali-main.jpeg',
                'products' => $this->getProductByProvince(1)
            ],
            'provinces' => [
                ['id' => 1, 'region' => '', 'img' => 'dist/img/merchant-denpasar.jpg'],
                ['id' => 3, 'region' => '', 'img' => 'dist/img/merchant-banten.jpg'],
                ['id' => 6, 'region' => '', 'img' => 'dist/img/merchant-jakarta.jpg'],
                ['id' => 9, 'region' => '', 'img' => 'dist/img/merchant-bandung.jpg'],
                ['id' => 10, 'region' => '', 'img' => 'dist/img/merchant-semarang.jpg'],
                ['id' => 11, 'region' => '', 'img' => 'dist/img/merchant-surabaya.jpg'],
                ['id' => 5, 'region' => '', 'img' => 'dist/img/merchant-jogja.jpg'],
                ['id' => 15, 'region' => '', 'img' => 'dist/img/merchant-samarinda.jpg', 'with_province_id' => 16],
                ['id' => 22, 'region' => '', 'img' => 'dist/img/merchant-mataram.jpg'],
                ['id' => 23, 'region' => '', 'img' => 'dist/img/merchant-kupang.jpg'],
                ['id' => 26, 'region' => '', 'img' => 'dist/img/merchant-riau.jpg'],
                ['id' => 28, 'region' => '', 'img' => 'dist/img/merchant-makasar.jpg'],
                ['id' => 31, 'region' => '', 'img' => 'dist/img/merchant-manado.jpg'],
                ['id' => 34, 'region' => '', 'img' => 'dist/img/merchant-medan.jpg'],
                ['id' => 33, 'region' => '', 'img' => 'dist/img/merchant-palembang.jpg'],

                ['id' => 29, 'region' => 'palu', 'img' => 'dist/img/merchant-palu.jpg'],
                ['id' => 17, 'region' => 'batam', 'img' => 'dist/img/merchant-batam.jpg'],
                ['id' => 8, 'region' => '', 'img' => 'dist/img/merchant-jambi.jpg'],
                ['id' => 30, 'region' => 'kendari', 'img' => 'dist/img/merchant-kendari.jpg'],
                ['id' => 2, 'region' => 'pangkalpinang', 'img' => 'dist/img/merchant-pangkal-pinang.jpg'],
                ['id' => 26, 'region' => 'pekanbaru', 'img' => 'dist/img/merchant-pekan-baru.jpg'],
                ['id' => 3, 'region' => 'serang', 'img' => 'dist/img/merchant-serang.jpg'],


            ]
        ];

        return $this->view('@__main__/plugins/product/showcase.html.twig', $data, 'html');
    }

    public function onError()
    {
        $data = [
            'products' => $this->getSelectedProducts(),
        ];

        return $this->view('@__main__/plugins/product/on_error.html.twig', $data, 'html');
    }

    public function showcaseRecommendations()
    {
        $data = [
            'products' => $this->getSelectedProductRecommendations(),
        ];

        return $this->view('@__main__/plugins/product/showcase_recommendations.html.twig', $data, 'html');
    }

    public function showcaseBestSeller()
    {
        $data = [
            'products' => $this->getSelectedProductsByCategory(),
        ];
        // dd($data);

        return $this->view('@__main__/plugins/product/showcase_bestseller.html.twig', $data, 'html');
    }

    private function getSelectedProducts(int $limit = 4): array
    {
        $allowed = $this->getAllowedCategoriesForLKPPUser();
        $parameters = [];

        if (count($allowed) > 0) {
            $parameters['allowed'] = $allowed;
        }

         /** @var User $user */
        $user = $this->getUser();

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        /** @var Product[] $products */
        $products = $repository->getShowcaseProducts($limit, $parameters, $user);

        /**
         * refactor 10 sep 2024:
         * tidak diketahui kenapa ada proses untuk update review total ketika fetch data
         * harusnya proses update review total pada product terjadi ketika 
         * ada review baru yang masuk setelah transaksi. 
         * sementara code didisable
         */

        // foreach ($products as $product) {
        //     if ($product->getReviewTotal() === 0) {
        //         $total = $repository->getTotalProductReviewByProduct($product->getId());
        //         $product->setReviewTotal((int) $total['count']);

        //         $em = $this->getEntityManager();
        //         $em->persist($product);
        //         $em->flush();
        //     }
        // }

        return $products;
    }

    private function getSelectedProductRecommendations(int $limit = 8): array
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        /** @var Product[] $products */
        $products = $repository->getRecommendationProducts($limit, $user);

        
        foreach ($products as &$product) {
            $total = $repository->getTotalProductReviewByProduct($product->getId());
            $product->setReviewTotal((int) $total['count']);
            $product->setRatingCount((float) $total['avg_rating']);
        }


        // dd($products);

        /**
         * refactor 10 sep 2024:
         * tidak diketahui kenapa ada proses untuk update review total ketika fetch data
         * harusnya proses update review total pada product terjadi ketika 
         * ada review baru yang masuk setelah transaksi. 
         * sementara code didisable
         */

        // foreach ($products as $product) {
        //     if ($product->getReviewTotal() === 0) {
        //         $total = $repository->getTotalProductReviewByProduct($product->getId());
        //         $product->setReviewTotal((int) $total['count']);

        //         $em = $this->getEntityManager();
        //         $em->persist($product);
        //         $em->flush();
        //     }
        // }

        return $products;
    }

    private function getSelectedProductsByCategory(int $limit = 3): array
    {
        /* refactor 10 Sep 2024: 
         * awalnya get category harus loop category > get product by category di dalam looping
         * refactor jadi get bulk product grouped by repository (getShowcaseProductsByCategoryV2)
         * format / bentuk result dari V2 sama dengan V1 hanya tidak perlu looping
         */ 

          /** @var User $user */
        $user = $this->getUser();

        /** @var ProductCategoryRepository $categoryRepository */
        $categoryRepository = $this->getRepository(ProductCategory::class);
        /** @var ProductCategory[] $categories */

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        /** @var Product[] $products */

        $categories = $categoryRepository->getCategoryParents();

        $categoryIDs = array_column($categories, 'id');
        $categoryIDs = array_map('intval', $categoryIDs);

        $products = $repository->getShowcaseProductsByCategoryV2($limit, $categoryIDs, $user);

        return $products;
    }

    private function getAllowedCategoriesForLKPPUser(): array
    {
        $session = $this->get('session');
        $cache = $this->getCache();
        $cacheKey = 'user_lkpp_restricted_categories';
        $allowed = [];

        if ($session->has($cacheKey)) {
            try {
                if ($cache->hasItem($cacheKey)) {
                    /** @var CacheItem $restricted */
                    $restricted = $cache->getItem($cacheKey);

                    foreach ($restricted->get() as $key => $items) {
                        $allowed[] = $key;

                        foreach ($items as $item) {
                            $allowed[] = $item;
                        }
                    }
                }
            } catch (InvalidArgumentException $e) {
            }
        }

        return $allowed;
    }

    private function getProductByProvince(int $provinceId): array
    {
         /** @var User $user */
        $user = $this->getUser();

        $repository = $this->getRepository(Product::class);
        $data = $repository->getProductByProvince($provinceId, $user);

        return $data;
    }
}
