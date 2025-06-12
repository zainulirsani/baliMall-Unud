<?php

use App\Controller\User\AdminUserAddressController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('save', '/save')
        ->controller([AdminUserAddressController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('edit', '/edit')
        ->controller([AdminUserAddressController::class, 'edit'])
        ->methods(['POST'])
    ;

    $routes
        ->add('update', '/update')
        ->controller([AdminUserAddressController::class, 'update'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminUserAddressController::class, 'delete'])
        ->methods(['POST'])
    ;
};
