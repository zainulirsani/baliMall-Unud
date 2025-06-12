<?php

use App\Controller\Api\ApiStoreController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([ApiStoreController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('show', '/{id}')
        ->controller([ApiStoreController::class, 'show'])
        ->methods(['GET'])
    ;
};
