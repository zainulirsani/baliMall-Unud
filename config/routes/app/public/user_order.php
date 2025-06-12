<?php

use App\Controller\User\UserOrderController;
use App\Controller\User\UserOrderListBindingController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use App\Controller\GoSend\GoSendController;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([UserOrderController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('review', '/review')
        ->controller([UserOrderController::class, 'review'])
        ->methods(['POST'])
    ;

    $routes
        ->add('publish_review', '/publish-review')
        ->controller([UserOrderController::class, 'publishReview'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete_payment', '/delete-payment')
        ->controller([UserOrderController::class, 'deletePayment'])
        ->methods(['POST'])
    ;

    $routes
        ->add('shared', '/shared/{id}')
        ->controller([UserOrderController::class, 'shared'])
        ->methods(['GET'])
    ;

    $routes
        ->add('shared_invoice', '/shared/{id}/invoice')
        ->controller([UserOrderController::class, 'printSharedInvoice'])
        ->methods(['GET'])
    ;

    $routes
        ->add('pay_with_channel', '/pay/{channel}')
        ->controller([UserOrderController::class, 'payWithChannel'])
        ->methods(['GET'])
    ;

    $routes
        ->add('detail', '/{id}')
        ->controller([UserOrderController::class, 'detail'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}')
        ->controller([UserOrderController::class, 'update'])
        ->methods(['POST'])
    ;

    $routes
        ->add('export', '/export/{id}')
        ->controller([UserOrderController::class, 'exportData'])
        ->methods(['GET'])
    ;

    $routes
        ->add('complaint', '/{id}/complaint')
        ->controller([UserOrderController::class, 'complaint'])
        ->methods(['POST'])
    ;

    $routes
        ->add('document', '/{id}/document')
        ->controller([UserOrderController::class, 'document'])
        ->methods(['POST'])
    ;

    $routes
        ->add('print', '/{id}/print-{type}')
        ->controller([UserOrderController::class, 'print'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('cancel_order', '/{id}/cancel')
        ->controller([UserOrderController::class, 'cancelOrder'])
        ->methods(['POST'])
    ;

    $routes
        ->add('negotiate', '/{id}/negotiate')
        ->controller([UserOrderController::class, 'negotiate'])
        ->methods(['POST'])
    ;

    $routes
        ->add('approve_negotiation', '/{id}/approve-negotiation')
        ->controller([UserOrderController::class, 'approveNegotiation'])
        ->methods(['POST'])
    ;

    $routes
        ->add('process_order', '/{orderId}/process')
        ->controller([UserOrderController::class, 'setOrderToProcessed'])
        ->methods(['POST']);

    $routes
        ->add('print_disbursement', '/disbursement/{orderId}')
        ->controller([UserOrderController::class, 'printDisbursement'])
        ->methods(['GET']);

    $routes
        ->add('pickup_order', '/pickup-order/{orderId}')
        ->controller([GoSendController::class, 'pickupOrder'])
        ->methods(['GET'])
    ;

    $routes
        ->add('gosend_booking_details', '/gosend/{orderId}')
        ->controller([GoSendController::class, 'getGosendBookingDetails'])
        ->methods(['GET'])
    ;

    $routes
        ->add('gosend_booking_retry', '/gosend-booking/{orderId}')
        ->controller([GoSendController::class, 'findDriver'])
        ->methods(['PUT']);

    $routes
        ->add('gosend_booking_cancel', '/gosend-booking/{orderId}/cancel')
        ->controller([GoSendController::class, 'cancelBookingOrder'])
        ->methods(['PUT']);

    $routes
        ->add('approve', 'approve/{id}')
        ->controller([UserOrderController::class, 'approveOrder'])
        ->methods(['GET'])
    ;

    $routes
        ->add('bni_payment_single', 'bni_payment_single/{orderSharedId}')
        ->controller([UserOrderController::class, 'bniPaymentSingle'])
        ->methods(['GET'])
    ;

    $routes
        ->add('cc_payment', 'cc_payment/{id}')
        ->controller([UserOrderController::class, 'ccPayment'])
        ->methods(['GET'])
    ;

    $routes
        ->add('cc_payment_list_binding', 'cc-payment/list-binding')
        ->controller([UserOrderListBindingController::class, 'handleGetListBinding'])
        ->methods(['GET'])
    ;

    $routes
        ->add('cc_payment_store', 'cc_payment_store/{id}')
        ->controller([UserOrderController::class, 'ccPaymentStore'])
        ->methods(['POST'])
    ;

    $routes
        ->add('cc_payment_list_binding_page', 'cc_payment_list_binding_page/{id}')
        ->controller([UserOrderController::class, 'ccPaymentUpdate'])
        ->methods(['GET'])
    ;

    $routes
        ->add('cc_payment_check', 'cc_payment_check/{id}')
        ->controller([UserOrderController::class, 'ccPaymentCheck'])
        ->methods(['GET'])
    ;

    $routes
        ->add('addendum_aggrement', 'addendum-aggrement/{id}')
        ->controller([UserOrderController::class, 'addendumAggrement'])
        ->methods(['GET'])
    ;

    $routes
        ->add('addendum_agreed', 'addendum/{id}')
        ->controller([UserOrderController::class, 'addendumAgreed'])
        ->methods(['GET'])
    ;
};
