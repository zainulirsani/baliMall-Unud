<?php

use App\Controller\Product\AdminProductController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminProductController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('create', '/new')
        ->controller([AdminProductController::class, 'create'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([AdminProductController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminProductController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('action', '/action')
        ->controller([AdminProductController::class, 'action'])
        ->methods(['POST'])
    ;

    $routes
        ->add('import', '/import')
        ->controller([AdminProductController::class, 'import'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('export', '/export')
        ->controller([AdminProductController::class, 'export'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminProductController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('fetch_select', '/fetch_select')
        ->controller([AdminProductController::class, 'fetchSelect'])
        ->methods(['GET'])
    ;

    $routes
        ->add('quick_save', '/quick_save')
        ->controller([AdminProductController::class, 'quickSave'])
        ->methods(['POST'])
    ;

    $routes
        ->add('qrcode', '/qrcode')
        ->controller([AdminProductController::class, 'qrcode'])
        ->methods(['POST'])
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminProductController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminProductController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminProductController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;

    $routes
        ->add('show_image', '/image/{path}')
        ->controller([AdminProductController::class, 'showImage'])
        ->methods(['GET'])
        ->requirements(['path' => '.+'])
    ;
};
