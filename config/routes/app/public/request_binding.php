<?php

use App\Controller\User\RequestBindingController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '')
        ->controller([RequestBindingController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('new', '/new')
        ->controller([RequestBindingController::class, 'new'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([RequestBindingController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('update', '/update')
        ->controller([RequestBindingController::class, 'updateCpan'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([RequestBindingController::class, 'delete'])
        ->methods(['POST'])
    ;

    // $routes
    //     ->add('view', '/{id}')
    //     ->controller([UserTaxDocumentController::class, 'detail'])
    //     ->requirements(['id' => '\d+'])
    //     ->methods(['GET'])
    // ;

    // $routes
    //     ->add('edit', '/edit/{id}')
    //     ->controller([UserTaxDocumentController::class, 'edit'])
    //     ->requirements(['id' => '\d+'])
    //     ->methods(['GET'])
    // ; 

    // $routes
    //     ->add('update', '/update')
    //     ->controller([UserTaxDocumentController::class, 'update'])
    //     ->methods(['POST'])
    // ;
};
