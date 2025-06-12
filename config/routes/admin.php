<?php

use App\Controller\Site\AdminSiteController;
use App\Controller\User\UserLoginController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use App\Controller\Banner\AdminBannerController;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('admin_login', '/login')
        ->controller([UserLoginController::class, 'login'])
        ->methods(['GET'])
    ;

    $routes
        ->add('admin_login_check', '/login_check')
    ;

    $routes
        ->add('admin_logout', '/logout')
    ;

    $routes
        ->add('admin_dashboard', '/dashboard')
        ->controller([AdminSiteController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('admin_notification', '/notification')
        ->controller([AdminSiteController::class, 'notification'])
        ->methods(['GET'])
    ;

    $routes
        ->add('admin_download', '/download')
        ->controller([AdminSiteController::class, 'download'])
        ->methods(['GET'])
    ;

    $routes
        ->add('admin_file_upload', '/file/upload')
        ->controller([AdminSiteController::class, 'fileUpload'])
        ->methods(['POST'])
    ;

    $routes
        ->add('admin_file_delete', '/file/delete')
        ->controller([AdminSiteController::class, 'fileDelete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('admin_dev', '/dev')
        ->controller([AdminSiteController::class, 'dev'])
        ->methods(['GET'])
    ;

    $routes
        ->import('app/admin/product.php')
        ->prefix('/product')
        ->namePrefix('admin_product_')
    ;

    $routes
        ->import('app/admin/product_category.php')
        ->prefix('/product_category')
        ->namePrefix('admin_product_category_')
    ;

    $routes
        ->import('app/admin/order.php')
        ->prefix('/order')
        ->namePrefix('admin_order_')
    ;

    $routes
        ->import('app/admin/store.php')
        ->prefix('/store')
        ->namePrefix('admin_store_')
    ;

    $routes
        ->import('app/admin/user.php')
        ->prefix('/user')
        ->namePrefix('admin_user_')
    ;

    $routes
        ->import('app/admin/kldi.php')
        ->prefix('/kldi')
        ->namePrefix('admin_kldi_')
    ;

    $routes
        ->import('app/admin/satker.php')
        ->prefix('/satker')
        ->namePrefix('admin_satker_')
    ;

    $routes
        ->import('app/admin/newsletter.php')
        ->prefix('/newsletter')
        ->namePrefix('admin_newsletter_')
    ;

    $routes
        ->import('app/admin/setting.php')
        ->prefix('/setting')
        ->namePrefix('admin_setting_')
    ;

    $routes
        ->import('app/admin/voucher.php')
        ->prefix('/voucher')
        ->namePrefix('admin_voucher_')
    ;

    $routes
        ->import('app/admin/bank.php')
        ->prefix('/bank')
        ->namePrefix('admin_bank_')
    ;

    $routes
        ->import('app/admin/banner.php')
        ->prefix('/banner')
        ->namePrefix('admin_banner_')
    ;

    $routes
        ->import('app/admin/disbursement.php')
        ->prefix('/disbursement')
        ->namePrefix('admin_disbursement_')
    ;
    
    $routes
        ->import('app/admin/mastertax.php')
        ->prefix('/mastertax')
        ->namePrefix('admin_mastertax_')
    ;

};
