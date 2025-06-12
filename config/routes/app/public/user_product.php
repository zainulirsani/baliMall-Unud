<?php

use App\Controller\User\UserProductController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([UserProductController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('new', '/new')
        ->controller([UserProductController::class, 'new'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([UserProductController::class, 'save'])
        ->methods(['POST'])
    ;
    
    $routes
        ->add('excel_new', '/excel_new')
        ->controller([UserProductController::class, 'excel_new'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save_excel_new', '/excel_new')
        ->controller([UserProductController::class, 'saveExcel'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([UserProductController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('edit', '/{id}')
        ->controller([UserProductController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}')
        ->controller([UserProductController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;
};
