<?php

use App\Controller\Site\SiteController;
use App\Controller\User;
use App\Controller\GoSend\GoSendController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use App\Controller\Doku\DokuController;
use App\Controller\User\BniController;
use App\Controller\Midtrans\MidtransController;

return function (RoutingConfigurator $routes) {
    $routes
        ->import('app/public/user.php')
        ->prefix('/user')
        ->namePrefix('user_')
    ;

    $routes
        ->import('app/public/order.php')
        ->prefix('/order')
        ->namePrefix('order_')
    ;

    $routes
        ->import('app/public/sftp_file.php')
        ->prefix('/sftp')
        ->namePrefix('sftp_')
    ;

    $routes
        ->import('app/public/static.php')
    ;

    $routes
        ->import('app/public/helper.php')
    ;

    $routes
        ->import('app/public/product.php')
    ;

    $routes
        ->import('app/public/lkpp.php')
    ;

    $routes
        ->import('app/public/cart.php')
        ->prefix('/cart')
        ->namePrefix('cart_')
    ;

    $routes
        ->add('notification', '/notification')
        ->controller([User\UserNotificationController::class, 'notification'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('document_pic', '/pic/{id}/print-{type}')
        ->controller([User\UserOrderController::class, 'print'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('ppk_approve', 'ppk_approve/{id}')
        ->controller([User\UserOrderController::class, 'ppk_approve'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('treasurer_pay_with_channel', '/pay/{channel}')
        ->controller([User\UserOrderController::class, 'payWithChannel'])
        ->methods(['GET'])
    ;

    $routes
        ->add('register', '/register')
        ->controller([User\UserRegisterController::class, 'register'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('register-vendor', '/register-vendor')
        ->controller([User\UserRegisterController::class, 'registerVendor'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('login', '/login')
        ->controller([User\UserLoginController::class, 'login'])
        ->methods(['GET'])
    ;

    $routes
        ->add('login_check', '/login_check')
    ;

    $routes
        ->add('logout', '/logout')
    ;

    $routes
        ->import('app/public/store.php')
        ->namePrefix('store_')
    ;

    $routes
        ->add('homepage', '/')
        ->controller([SiteController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('agreement', '/download/agreement/{type}/{filehash}')
        ->controller([User\UserStoreController::class, 'downloadAgreement'])
        ->methods(['GET']);

    $routes
        ->add('doku-qris', getenv('DOKU_QRIS_NOTIFICATION_URL'))
        ->controller([DokuController::class, 'qrisNotifications'])
        ->methods(['POST'])
    ;

    $routes
        ->add('doku-va', getenv('DOKU_VA_NOTIFICATION_URL'))
        ->controller([DokuController::class, 'vaNotifications'])
        ->methods(['POST'])
    ;

    $routes
        ->add('doku-cc', getenv('DOKU_CC_NOTIFICATION_URL'))
        ->controller([DokuController::class, 'ccNotifications'])
        ->methods(['POST'])
    ;

    $routes
        ->add('midtrans', '/payments/notification/midtrans')
        ->controller([MidtransController::class, 'notification'])
        ->methods(['POST'])
    ;

    $routes
        ->add('bni-va', getenv('BNI_VA_NOTIFICATION_URL'))
        ->controller([BniController::class, 'vaNotifications'])
        ->methods(['POST'])
    ;

    $routes
        ->add('gosend-webhooks', getenv('GOSEND_WEBHOOKS_URL'))
        ->controller([GoSendController::class, 'gosendWebhooks'])
        ->methods(['POST'])
    ;

    $routes
        ->import('app/api/product.php')
        ->prefix('/api/v1/products')
        ->namePrefix('api_product_')
    ;

    $routes
        ->import('app/api/store.php')
        ->prefix('/api/v1/stores')
        ->namePrefix('api_store_')
    ;

    $routes
        ->import('app/api/product_category.php')
        ->prefix('/api/v1/product-categories')
        ->namePrefix('api_product_category_')
    ;

    $routes
        ->import('app/api/user.php')
        ->prefix('/api/v1/balimall')
        ->namePrefix('api_balimall_')
    ;

    $routes
        ->import('app/api/erzap.php')
        ->prefix('/api/v1/erzap')
        ->namePrefix('api_erzap_');
};
