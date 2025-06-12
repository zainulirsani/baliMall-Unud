<?php

use App\Controller\Balimall\UserController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('portal_login', '/portal-login')
        ->controller([UserController::class, 'portalLogin'])
        ->methods(['POST']);

    $routes
        ->add('login', '/login')
        ->controller([UserController::class, 'login'])
        ->methods(['GET']);

    $routes
        ->add('portal_logout', '/portal-logout')
        ->controller([UserController::class, 'portalLogout'])
        ->methods(['POST']);
};
