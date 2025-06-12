<?php

use App\Controller\User\UserController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('dashboard', '/dashboard')
        ->controller([UserController::class, 'dashboard'])
        ->methods(['GET'])
    ;

    $routes
        ->add('pemesanan', '/pemesanan')
        ->controller([UserController::class, 'pemesanan'])
        ->methods(['GET'])
    ;

    $routes
        ->add('profile', '/profile')
        ->controller([UserController::class, 'profile'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('payment_confirmation', '/payment-confirmation')
        ->controller([UserController::class, 'paymentConfirmation'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('notification', '/notification')
        ->controller([UserController::class, 'notification'])
        ->methods(['GET'])
    ;

    $routes
        ->import('user_address.php')
        ->prefix('/address')
        ->namePrefix('address_')
    ;

    $routes
        ->import('user_product.php')
        ->prefix('/product')
        ->namePrefix('product_')
    ;

    $routes
        ->import('user_store.php')
        ->prefix('/store')
        ->namePrefix('store_')
    ;

    $routes
        ->import('user_order.php')
        ->prefix('/order')
        ->namePrefix('order_')
    ;

    $routes
        ->import('user_chat.php')
        ->prefix('/chat')
        ->namePrefix('chat_')
    ;

    $routes
        ->import('user_tax.php')
        ->prefix('/tax')
        ->namePrefix('tax_')
    ;

    $routes
        ->import('user_pic.php')
        ->prefix('/pic')
        ->namePrefix('pic_')
    ;

    $routes
        ->import('user_ppk.php')
        ->prefix('/ppk')
        ->namePrefix('ppk_')
    ;

    $routes
        ->import('user_ppk.php')
        ->prefix('/ppk-treasurer')
        ->namePrefix('ppktreasurer_')
    ;

    $routes
        ->import('user_satker.php')
        ->prefix('/satker')
        ->namePrefix('satker_')
    ;

    $routes
        ->import('request_binding.php')
        ->prefix('/request-binding')
        ->namePrefix('requestbinding_')
    ;

    $routes
        ->import('bni.php')
        ->prefix('/bni-va')
        ->namePrefix('bnipayment_')
    ;

    $routes
        ->import('user_operator.php')
        ->prefix('/operator')
        ->namePrefix('operator_')
    ;
};
