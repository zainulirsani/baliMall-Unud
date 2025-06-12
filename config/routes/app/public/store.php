<?php

use App\Controller\Store\StoreController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('page', '/{store}')
        ->controller([StoreController::class, 'storePage'])
        ->methods(['GET'])
    ;

    $routes
        ->add('product_page', '/{store}/{product}')
        ->controller([StoreController::class, 'productPage'])
        ->methods(['GET'])
    ;
};
