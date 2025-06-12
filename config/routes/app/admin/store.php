<?php

use App\Controller\Store\AdminStoreController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminStoreController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('create', '/new')
        ->controller([AdminStoreController::class, 'create'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([AdminStoreController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminStoreController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('action', '/action')
        ->controller([AdminStoreController::class, 'action'])
        ->methods(['POST'])
    ;

    $routes
        ->add('export', '/export')
        ->controller([AdminStoreController::class, 'export'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminStoreController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('fetch_select', '/fetch_select')
        ->controller([AdminStoreController::class, 'fetchSelect'])
        ->methods(['GET'])
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminStoreController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminStoreController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminStoreController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;

    $routes
        ->add('change_owner', '/{id}/change_owner')
        ->controller([AdminStoreController::class, 'changeOwner'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('download', '/download')
        ->controller([AdminStoreController::class, 'download'])
        ->methods(['GET']);
};
