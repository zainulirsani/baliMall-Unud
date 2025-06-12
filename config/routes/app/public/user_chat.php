<?php

use App\Controller\User\UserChatController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([UserChatController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('init', '/init')
        ->controller([UserChatController::class, 'init'])
        ->methods(['POST'])
    ;

    $routes
        ->add('submit', '/submit')
        ->controller([UserChatController::class, 'submit'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([UserChatController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('detail', '/{room}')
        ->controller([UserChatController::class, 'detail'])
        ->methods(['GET'])
    ;

    $routes
        ->add('load', '/{room}/fetch')
        ->controller([UserChatController::class, 'fetch'])
        ->methods(['POST'])
    ;
};
