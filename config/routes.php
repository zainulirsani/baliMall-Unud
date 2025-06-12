<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->import('routes/admin.php')
        ->prefix('/%admin_url%')
    ;

    $routes
        ->import('routes/media.php')
        ->prefix('/media')
    ;

    $routes
        ->import('routes/public.php')
    ;
};
