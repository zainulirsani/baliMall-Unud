<?php

use App\Controller\Order\CartController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([CartController::class, 'cart'])
        ->methods(['GET'])
    ;

    $routes
        ->add('add', '/add')
        ->controller([CartController::class, 'addItem'])
        ->methods(['POST'])
    ;

    $routes
        ->add('update', '/update')
        ->controller([CartController::class, 'updateItem'])
        ->methods(['POST'])
    ;

    $routes
        ->add('remove', '/remove')
        ->controller([CartController::class, 'removeItem'])
        ->methods(['POST'])
    ;

    $routes
        ->add('clear', '/clear')
        ->controller([CartController::class, 'clearCart'])
        ->methods(['POST'])
    ;

    $routes
        ->add('checkout', '/checkout')
        ->controller([CartController::class, 'checkout'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('calculate', '/calculate')
        ->controller([CartController::class, 'calculate'])
        ->methods(['POST'])
    ;

    $routes
        ->add('apply_voucher', '/voucher/{code}')
        ->controller([CartController::class, 'applyVoucher'])
        ->methods(['GET'])
    ;

    $routes
        ->add('remove_voucher', '/remove-voucher')
        ->controller([CartController::class, 'removeVoucher'])
        ->methods(['POST'])
    ;
};
