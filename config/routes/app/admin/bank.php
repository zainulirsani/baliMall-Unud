<?php

use App\Controller\Bank\AdminBankController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminBankController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('create', '/new')
        ->controller([AdminBankController::class, 'create'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([AdminBankController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminBankController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('action', '/action')
        ->controller([AdminBankController::class, 'action'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminBankController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('fix_duplicate', '/fix_duplicate')
        ->controller([AdminBankController::class, 'fixDuplicate'])
        ->methods(['GET'])
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminBankController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminBankController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminBankController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;
};
