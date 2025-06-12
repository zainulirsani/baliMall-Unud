<?php

use App\Controller\Setting\AdminSettingController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminSettingController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/save')
        ->controller([AdminSettingController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('update', '/')
        ->controller([AdminSettingController::class, 'updateSetting'])
        ->methods(['POST'])
    ;
};
