<?php

use App\Controller\Product\ProductController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('search', '/search')
        ->controller([ProductController::class, 'index'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('view_count', '/view-count')
        ->controller([ProductController::class, 'viewCount'])
        ->methods(['POST'])
    ;

    $routes
        ->add('compare', '/compare')
        ->controller([ProductController::class, 'compare'])
        ->methods(['GET'])
    ;
};
