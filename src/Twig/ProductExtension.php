<?php

namespace App\Twig;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductReview;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductReviewRepository;
use App\Traits\ContainerTrait;
use Hashids\Hashids;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\RuntimeExtensionInterface;

class ProductExtension implements RuntimeExtensionInterface
{
    use ContainerTrait;

    /** @var ContainerInterface $container */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getProductMainImage(int $productId)
    {
        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        $productFiles = $repository->getProductFiles($productId);
        $image = 'dist/img/no-image.png';

        if (count($productFiles) > 0) {
            $image = (strpos($productFiles[0]['filePath'], 'uploads/temp') === false) ? $productFiles[0]['filePath'] : $image;
        }

        return $image;
    }

    public function getProductHashId(
        string $hash,
        string $hashType = 'encode',
        string $hashKey = 'BaliMallProduct',
        int $hashLength = 16
    )
    {
        if (in_array($hashType, ['encode', 'decode'], false)) {
            $encoder = new Hashids($hashKey, $hashLength);

            if ($hashType === 'encode') {
                return $encoder->encode($hash);
            }

            return current($encoder->decode($hash));
        }

        return $hash;
    }

    public function getProductReview(int $orderId, int $productId, int $buyerId)
    {
        /** @var ProductReviewRepository $repository */
        $repository = $this->getRepository(ProductReview::class);
        $review = $repository->getProductReviewDetail($orderId, $productId, $buyerId);

        return count($review) > 0 ? current($review) : [];
    }

    public function getProductCategoryName(int $productId): string
    {
        /** @var ProductCategoryRepository $repository */
        $repository = $this->getRepository(ProductCategory::class);
        /** @var ProductCategory $productCategory */
        $productCategory = $repository->getCategoryFromProductId($productId);

        return !empty($productCategory) ? $productCategory->getName() : '';
    }
}
