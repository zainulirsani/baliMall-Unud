<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use App\Controller\Banner\AdminBannerController;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminBannerController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('create', '/new')
        ->controller([AdminBannerController::class, 'create'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([AdminBannerController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminBannerController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('action', '/action')
        ->controller([AdminBannerController::class, 'action'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminBannerController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminBannerController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('print', '/{id}/print')
        ->controller([AdminBannerController::class, 'print'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminBannerController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminBannerController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;

    $routes
        ->add('show_image', '/image/{path}')
        ->controller([AdminBannerController::class, 'showImage'])
        ->methods(['GET'])
        ->requirements(['path' => '.+'])
    ;
};
