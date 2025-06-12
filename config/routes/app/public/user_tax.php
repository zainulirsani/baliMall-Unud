<?php

use App\Controller\User\UserTaxDocumentController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([UserTaxDocumentController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('new', '/new')
        ->controller([UserTaxDocumentController::class, 'new'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([UserTaxDocumentController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([UserTaxDocumentController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('view', '/{id}')
        ->controller([UserTaxDocumentController::class, 'detail'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/edit/{id}')
        ->controller([UserTaxDocumentController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ; 

    $routes
        ->add('update', '/update')
        ->controller([UserTaxDocumentController::class, 'update'])
        ->methods(['POST'])
    ;
};
