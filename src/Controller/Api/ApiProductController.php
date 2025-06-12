<?php

namespace App\Controller\Api;

use App\Controller\PublicController;
use App\Entity\Product;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiProductController extends PublicController
{
    public function index(): JsonResponse
    {
        if (!$this->authorizeApiRequest()) {
            return $this->response([], 'Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $request = $this->getRequest()->query;
        $parameters = [];
        $name = $request->get('name', null);
        $store = $request->get('store_id', null);
        $perPage = $request->get('per_page', null);
        $page = $request->get('page', null);

        if (!empty($name)) {
            $parameters['name'] = $name;
        }

        if (!empty($store)) {
            $parameters['store'] = $store;
        }

        if (!empty($perPage)) {
            $parameters['per_page'] = $perPage;
        }

        if (!empty($page)) {
            $parameters['page'] = $page;
        }

        $parameters['category'] = $this->allowedCategories;

        $productRepository = $this->getRepository(Product::class);
        $products = $productRepository->getProductData($parameters);



        $results = [];

        foreach ($products as $product) {
            $results[] = $this->constructResponse($product, ['images' => $this->getProductImages($product)]);
        }

        return $this->response($results);
    }

    public function show($id): JsonResponse
    {
        if (!$this->authorizeApiRequest()) {
            return $this->response([], 'Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $productRepository = $this->getRepository(Product::class);

        $product = $productRepository->find($id);

        if ($product instanceof Product) {
            if (!in_array((int)$product->getCategory(), $this->allowedCategories)) {
                return $this->response([], 'Category not allowed', 403);
            }

            $results = $this->constructResponse($product, ['images' => $this->getProductImages($product)]);

            return $this->response($results);
        }

        return $this->response([], 'Product not found', 404);
    }

    public function update(): JsonResponse
    {
        if (!$this->authorizeApiRequest()) {
            return $this->response([], 'Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $productRepository = $this->getRepository(Product::class);

        $formData = json_decode(file_get_contents('php://input'), true);

        $em = $this->getEntityManager();
        $failedProducts = [];

        try {
            if (count($formData) > 0) {
                foreach ($formData as $data) {
                    $productId = (int) $data['product_id'];
                    $reduceQty = abs($data['quantity']);

                    $product = $productRepository->find($productId);

                    if ($product instanceof Product) {
                        if (!in_array((int) $product->getCategory(), $this->allowedCategories)) {
                            $failedProducts[] = $data;
                            continue;
                        }

                        $currentQty = $product->getQuantity();
                        $newQty = $currentQty - $reduceQty;

                        if ($reduceQty > $currentQty) {
                            continue;
                        }

                        if ($newQty >= 0) {
                            $product->setQuantity($newQty);
                            $em->persist($product);
                        }
                    }else {
                        $failedProducts[] = $data;
                    }
                }

                $em->flush();

                $totalProductRequest = count($formData);
                $totalFailedProduct = count($failedProducts);

                if ($totalProductRequest === $totalFailedProduct) {
                    return $this->response($failedProducts, 'Products not found, failed to update stock', 404);
                }

                if ($totalFailedProduct > 0 && $totalFailedProduct <= $totalProductRequest) {
                    return $this->response($failedProducts, sprintf('Failed to update %s products. Products updated %s', $totalFailedProduct, ($totalProductRequest - $totalFailedProduct)));
                }

                return $this->response([],'Success update products quantity');
            }
        }catch (\Throwable $throwable){}

        return $this->response([], 'Bad request', 400);
    }

}
