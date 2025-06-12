<?php

use App\Controller\Api\ApiProductController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([ApiProductController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/update')
        ->controller([ApiProductController::class, 'update'])
        ->methods(['POST'])
    ;

    $routes
        ->add('show', '/{id}')
        ->controller([ApiProductController::class, 'show'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;
};
