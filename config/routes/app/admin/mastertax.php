<?php

use App\Controller\MasterTax\AdminMasterTaxController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminMasterTaxController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/edit')
        ->controller([AdminMasterTaxController::class, 'editdata'])
        ->methods(['GET'])
    ;

    $routes
        ->add('savedata', '/savedata')
        ->controller([AdminMasterTaxController::class, 'savedata'])
        ->methods(['POST'])
    ;

    $routes
        ->add('detail', '/{category}')
        ->controller([AdminMasterTaxController::class, 'detail'])
        ->methods(['GET'])
    ;
};
