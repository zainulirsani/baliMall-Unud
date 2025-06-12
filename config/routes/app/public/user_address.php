<?php

use App\Controller\User\UserAddressController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([UserAddressController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('new', '/new')
        ->controller([UserAddressController::class, 'new'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([UserAddressController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([UserAddressController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('edit', '/{id}')
        ->controller([UserAddressController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}')
        ->controller([UserAddressController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;
};
