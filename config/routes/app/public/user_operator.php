<?php

use App\Controller\User\UserOperatorController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([UserOperatorController::class, 'index'])
        ->methods(['GET']);

    $routes
        ->add('new', '/new')
        ->controller([UserOperatorController::class, 'new'])
        ->methods(['GET']);

    $routes
        ->add('save', '/new')
        ->controller([UserOperatorController::class, 'save'])
        ->methods(['POST']);

    $routes
        ->add('edit', '/{id}')
        ->controller([UserOperatorController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET']);

    $routes
        ->add('update', '/{id}')
        ->controller([UserOperatorController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST']);

    $routes
        ->add('select', '/select')
        ->controller([UserOperatorController::class, 'select'])
        ->methods(['GET', 'POST']);
};
