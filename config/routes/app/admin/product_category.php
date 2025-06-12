<?php

use App\Controller\Product\AdminProductCategoryController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminProductCategoryController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('create', '/new')
        ->controller([AdminProductCategoryController::class, 'create'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([AdminProductCategoryController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminProductCategoryController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('action', '/action')
        ->controller([AdminProductCategoryController::class, 'action'])
        ->methods(['POST'])
    ;

    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminProductCategoryController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('fetch_select', '/fetch_select')
        ->controller([AdminProductCategoryController::class, 'fetchSelect'])
        ->methods(['GET'])
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminProductCategoryController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminProductCategoryController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminProductCategoryController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;
};
