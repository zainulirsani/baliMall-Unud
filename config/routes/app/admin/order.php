<?php

use App\Controller\Order\AdminOrderController;
use App\Controller\User\UserOrderController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminOrderController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminOrderController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete_payment', '/delete_payment')
        ->controller([AdminOrderController::class, 'deletePayment'])
        ->methods(['POST'])
    ;

    $routes
        ->add('set_shared_invoice', '/set_shared_invoice')
        ->controller([AdminOrderController::class, 'setSharedInvoice'])
        ->methods(['GET'])
    ;

    $routes
        ->add('restore', '/restore')
        ->controller([AdminOrderController::class, 'restore'])
        ->methods(['POST'])
    ;

    $routes
        ->add('export', '/export')
        ->controller([AdminOrderController::class, 'export'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('shared', '/shared/{id}')
        ->controller([AdminOrderController::class, 'shared'])
        ->methods(['GET'])
    ;

    $routes
        ->add('payment_check', '/payment-check/{channel}')
        ->controller([AdminOrderController::class, 'paymentCheck'])
        ->methods(['GET'])
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminOrderController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminOrderController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminOrderController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;

    $routes
        ->add('cancel', '/{id}/cancel')
        ->controller([AdminOrderController::class, 'cancel'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;

    $routes
        ->add('resend', '/{id}/resend-{type}')
        ->controller([AdminOrderController::class, 'resend'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('resend_email', '/resend_email/{type}/{id}')
        ->controller([AdminOrderController::class, 'resend_email'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('reset_va_doku', '/reset_va_doku/{id}')
        ->controller([AdminOrderController::class, 'reset_va_doku'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('print', '/{id}/print-{type}')
        ->controller([UserOrderController::class, 'print'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('resolved', '/resolved/{complaint_id}')
        ->controller([AdminOrderController::class, 'resolved'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('printword', '/{id}/printword-{type}')
        ->controller([UserOrderController::class, 'printWord'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('resend_djp', '/resend_djp/{id}')
        ->controller([AdminOrderController::class, 'resend_djp'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;
};
