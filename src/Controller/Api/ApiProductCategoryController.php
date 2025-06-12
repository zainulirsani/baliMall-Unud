<?php

namespace App\Controller\Api;

use App\Controller\PublicController;
use App\Entity\ProductCategory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiProductCategoryController extends PublicController
{
    public function index(): JsonResponse
    {
        if (!$this->authorizeApiRequest()) {
            return $this->response([], 'Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $productCategoryRepository = $this->getRepository(ProductCategory::class);
        $productCategories = $productCategoryRepository->findBy(['id' => $this->allowedCategories, 'status' => true]);
        $results = [];

        foreach ($productCategories as $productCategory) {
            $child = [];

            $parentImages = [
                'desktop_image' => $productCategory->getDesktopImage() ? $this->getBaseUrl() . '/' . $productCategory->getDesktopImage() : '',
                'mobile_image' => $productCategory->getMobileImage() ? $this->getBaseUrl() . '/' . $productCategory->getMobileImage() : ''
            ];

            if ($productCategory->getParentId() === 0) {
                $tmpChild = $productCategoryRepository->findBy(['parentId' => $productCategory->getId(), 'status' => true]);

                if (count($tmpChild) > 0) {
                    foreach ($tmpChild as $item) {
                        $child[] = array_merge($item->getData(), [
                            'desktop_image' => $item->getDesktopImage() ? $this->getBaseUrl() . '/' . $item->getDesktopImage() : '',
                            'mobile_image' => $item->getMobileImage() ? $this->getBaseUrl() . '/' . $item->getMobileImage() : '',
                        ]);
                    }
                }
            }


            $results[] = array_merge($productCategory->getData(), $parentImages, ['child' => $child]);
        }

        return $this->response($results);
    }

    public function show(int $id): JsonResponse
    {
        if (!$this->authorizeApiRequest()) {
            return $this->response([], 'Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        if (!in_array($id, $this->allowedCategories)) {
            return $this->response([], 'Not Allowed', 403);
        }

        $productCategoryRepository = $this->getRepository(ProductCategory::class);

        try {
            $productCategory = $productCategoryRepository->find($id);
        } catch (\Throwable $exception) {
            return $this->response([], $exception->getMessage(), $exception->getCode());
        }

        $parentImages = [
            'desktop_image' => $productCategory->getDesktopImage() ? $this->getBaseUrl() . '/' . $productCategory->getDesktopImage() : '',
            'mobile_image' => $productCategory->getMobileImage() ? $this->getBaseUrl() . '/' . $productCategory->getMobileImage() : ''
        ];

        $tmpChild = $productCategoryRepository->findBy(['parentId' => $productCategory->getId()]);
        $child = [];

        if (count($tmpChild) > 0) {
            foreach ($tmpChild as $item) {
                $child[] = array_merge($item->getData(), [
                    'desktop_image' => $item->getDesktopImage() ? $this->getBaseUrl() . '/' . $item->getDesktopImage() : '',
                    'mobile_image' => $item->getMobileImage() ? $this->getBaseUrl() . '/' . $item->getMobileImage() : '',
                ]);
            }
        }

        $result = array_merge($productCategory->getData(), $parentImages, ['child' => $child]);

        return $this->response($result);
    }

}
