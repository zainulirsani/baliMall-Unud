<?php

use App\Controller\User\UserStoreController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('apply', '/register')
        ->controller([UserStoreController::class, 'apply'])
        ->methods(['GET'])
    ;

    $routes
        ->add('register', '/register')
        ->controller([UserStoreController::class, 'register'])
        ->methods(['POST'])
    ;

    $routes
        ->add('edit', '/')
        ->controller([UserStoreController::class, 'edit'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/')
        ->controller([UserStoreController::class, 'update'])
        ->methods(['POST'])
    ;

    $routes
        ->add('subdistrict', '/subdistrict/{cityId}')
        ->controller([UserStoreController::class, 'getSubdistrict'])
        ->methods(['GET']);
};
