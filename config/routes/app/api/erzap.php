<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use App\Controller\Api\ApiErzapController;
return function (RoutingConfigurator $routes) {

    $routes
        ->add('connect', '/merchant-connect')
        ->controller([ApiErzapController::class, 'merchantConnect'])
        ->methods(['POST'])
    ;

    $routes
        ->add('product_create', '/product')
        ->controller([ApiErzapController::class, 'productCreate'])
        ->methods(['POST'])
    ;

    $routes
        ->add('product_update_price', '/product/price')
        ->controller([ApiErzapController::class, 'updateProductPrice'])
        ->methods(['PUT'])
    ;

    $routes
        ->add('product_update_stock', '/product/stock')
        ->controller([ApiErzapController::class, 'updateProductStock'])
        ->methods(['PUT'])
    ;

    $routes
        ->add('product_delete', '/product')
        ->controller([ApiErzapController::class, 'productDelete'])
        ->methods(['DELETE'])
    ;

    $routes
        ->add('order_request_manual', '/orders')
        ->controller([ApiErzapController::class, 'orderRequestManual'])
        ->methods(['GET'])
    ;

    $routes
        ->add('product_by_sku', '/product/sku')
        ->controller([ApiErzapController::class, 'productInfoBySKU'])
        ->methods(['GET'])
    ;

    $routes
        ->add('product_by_idproduk_mp', '/product/idproduk_mp')
        ->controller([ApiErzapController::class, 'productInfoByIdProduk'])
        ->methods(['POST'])
    ;
};
