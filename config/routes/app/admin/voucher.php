<?php

use App\Controller\Voucher\AdminVoucherController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminVoucherController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('create', '/new')
        ->controller([AdminVoucherController::class, 'create'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([AdminVoucherController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminVoucherController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('action', '/action')
        ->controller([AdminVoucherController::class, 'action'])
        ->methods(['GET', 'POST'])
    ;

    
    $routes
        ->add('export', '/export')
        ->controller([AdminVoucherController::class, 'export'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminVoucherController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('fix_duplicate', '/fix_duplicate')
        ->controller([AdminVoucherController::class, 'fixDuplicate'])
        ->methods(['GET'])
    ;

    $routes
        ->add('regenerate_qr_image', '/regenerate_qr_image')
        ->controller([AdminVoucherController::class, 'regenerateQrImage'])
        ->methods(['GET'])
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminVoucherController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('print', '/{id}/print')
        ->controller([AdminVoucherController::class, 'print'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminVoucherController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminVoucherController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;
};
