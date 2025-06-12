<?php

use App\Controller\Api\ApiProductCategoryController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([ApiProductCategoryController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('show', '/{id}')
        ->controller([ApiProductCategoryController::class, 'show'])
        ->methods(['GET'])
    ;
};
