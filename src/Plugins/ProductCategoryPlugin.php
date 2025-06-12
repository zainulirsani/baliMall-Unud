<?php

namespace App\Plugins;

use App\Entity\ProductCategory;
use App\Repository\ProductCategoryRepository;

class ProductCategoryPlugin extends BasePlugin
{
    public function homepage()
    {
        $data = [
            'categories' => $this->getProductCategoriesFeatured(),
        ];

        return $this->view('@__main__/plugins/product_category/homepage.html.twig', $data, 'html');
    }

    public function header(string $region = 'desktop')
    {
        $template = sprintf('@__main__/plugins/product_category/header_block_%s.html.twig', $region);
        $data = [
            'categories' => $this->getProductCategoriesFeatured(0, 'no', 'yes'),
        ];

        return $this->view($template, $data, 'html');
    }

    public function categories()
    {
        /** @var ProductCategoryRepository $repository */
        $repository = $this->getRepository(ProductCategory::class);
        /** @var ProductCategory[] $categories */
        $categories = $repository->getCategoryParents();

        $data = [
            'categories' => $categories,
        ];

        return $this->view('@__main__/plugins/product_category/category_list.html.twig', $data, 'html');
    }
}
