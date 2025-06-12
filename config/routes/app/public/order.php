<?php

use App\Controller\Order\OrderController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('shipping', '/shipping')
        ->controller([OrderController::class, 'shipping'])
        ->methods(['POST'])
    ;

    $routes
        ->add('pre_process', '/pre-process')
        ->controller([OrderController::class, 'preProcess'])
        ->methods(['POST'])
    ;

    $routes
        ->add('process', '/process')
        ->controller([OrderController::class, 'process'])
        ->methods(['POST' ,'GET'])
    ;

    $routes
        ->add('confirmation_ppk', '/confirmation-ppk')
        ->controller([OrderController::class, 'confirmationOrderPpk'])
        ->methods(['POST' ,'GET'])
    ;

    $routes
        ->add('success', '/success')
        ->controller([OrderController::class, 'success'])
        ->methods(['GET'])
    ;

    $routes
        ->add('pay_with_channel', '/pay/{channel}')
        ->controller([OrderController::class, 'payWithChannel'])
        ->methods(['GET'])
    ;

    $routes
        ->add('qris_callback', '/qris-callback')
        ->controller([OrderController::class, 'qrisCallback'])
        ->methods(['POST'])
    ;

    $routes
        ->add('va_callback', '/va-callback')
        ->controller([OrderController::class, 'vaCallback'])
        ->methods(['POST'])
    ;

};
