<?php

use App\Controller\Kldi\AdminKldiController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminKldiController::class, 'index'])
        ->methods(['GET'])
        ;

    $routes
        ->add('create', '/new')
        ->controller([AdminKldiController::class, 'create'])
        ->methods(['GET'])
    ;

    $routes
        ->add('action', '/action')
        ->controller([AdminKldiController::class, 'action'])
        ->methods(['GET','POST'])
    ;
    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminKldiController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminKldiController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([AdminKldiController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminKldiController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('print', '/{id}/print')
        ->controller([AdminKldiController::class, 'print'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminKldiController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminKldiController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;
};
