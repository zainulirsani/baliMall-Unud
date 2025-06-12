<?php

namespace App\Controller\Api;

use App\Controller\PublicController;
use App\Entity\Store;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiStoreController extends PublicController
{
    public function index(): JsonResponse
    {
        if (!$this->authorizeApiRequest()) {
            return $this->response([], 'Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $parameters = [];

        $request = $this->getRequest()->query;
        $name = $request->get('name', null);
        $perPage = $request->get('per_page', null);
        $page = $request->get('page', null);

        if (!empty($name)) {
            $parameters['name'] = $name;
        }

        if (!empty($perPage)) {
            $parameters['per_page'] = $perPage;
        }

        if (!empty($page)) {
            $parameters['page'] = $page;
        }

        $parameters['category'] = $this->allowedCategories;

        $storeRepository = $this->getRepository(Store::class);
        $stores = $storeRepository->getStoreByProductCategory($parameters);
        $results = [];

        foreach ($stores as $store) {
            $products = [];

            if (count($store->getProducts()) > 0) {
                foreach ($store->getProducts() as $product) {
                    if (in_array((int) $product->getCategory(), $this->allowedCategories)) {
                        $products[] = $this->constructResponse($product, ['images' => $this->getProductImages($product)]);
                    }
                }
            }

            $image = [];

            if ($store->getUser() && !empty($store->getUser()->getPhotoProfile())) {
                $image = $this->getBaseUrl().'/'.$store->getUser()->getPhotoProfile();
            }

            $results[] = array_merge($store->getData(), ['image' => $image,'products' => $products,]);
        }

        return $this->response($results);
    }

    public function show($id): JsonResponse
    {
        if (!$this->authorizeApiRequest()) {
            return $this->response([], 'Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $storeRepository = $this->getRepository(Store::class);
        $store = $storeRepository->find($id);

        if ($store instanceof Store) {
            $products = [];

            if (count($store->getProducts()) > 0) {
                foreach ($store->getProducts() as $product) {
                    if (in_array((int) $product->getCategory(), $this->allowedCategories)) {
                        $products[] = $this->constructResponse($product, ['images' => $this->getProductImages($product)]);
                    }
                }
            }

            $image = [];

            if ($store->getUser() && !empty($store->getUser()->getPhotoProfile())) {
                $image = $this->getBaseUrl().'/'.$store->getUser()->getPhotoProfile();
            }

            $result = array_merge($store->getData(), ['image' => $image, 'products' => $products]);

            return $this->response($result);
        }

        return $this->response([], 'Store not found', 404);
    }

}
