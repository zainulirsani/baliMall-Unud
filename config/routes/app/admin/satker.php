<?php

use App\Controller\Satker\AdminSatkerController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminSatkerController::class, 'index'])
        ->methods(['GET'])
        ;

    $routes
        ->add('create', '/new')
        ->controller([AdminSatkerController::class, 'create'])
        ->methods(['GET'])
    ;

    $routes
        ->add('action', '/action')
        ->controller([AdminSatkerController::class, 'action'])
        ->methods(['GET','POST'])
    ;
    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminSatkerController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminSatkerController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([AdminSatkerController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminSatkerController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('print', '/{id}/print')
        ->controller([AdminSatkerController::class, 'print'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminSatkerController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminSatkerController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;
};
