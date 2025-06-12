<?php

use App\Controller\Newsletter\AdminNewsletterController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminNewsletterController::class, 'index'])
        ->methods(['GET'])
    ;
};
