<?php

use App\Controller\User\UserPpkTreasurerController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([UserPpkTreasurerController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('dashboard', '/dashboard')
        ->controller([UserPpkTreasurerController::class, 'dashboard'])
        ->methods(['GET'])
    ;

    $routes
        ->add('transaction', '/transaction')
        ->controller([UserPpkTreasurerController::class, 'transaction'])
        ->methods(['GET'])
    ;

    $routes
        ->add('shipping_partial', '/shipping/{id}')
        ->controller([UserPpkTreasurerController::class, 'shipping_partial'])
        ->methods(['GET'])
    ;

    $routes
        ->add('shipping_partial_proccess', '/shipping-proccess')
        ->controller([UserPpkTreasurerController::class, 'shipping_partial_proccess'])
        ->methods(['POST'])
    ;

    $routes
        ->add('new', '/new')
        ->controller([UserPpkTreasurerController::class, 'new'])
        ->methods(['GET'])
    ;

    $routes
        ->add('export', '/export')
        ->controller([UserPpkTreasurerController::class, 'export'])
        ->methods(['GET'])
    ;
    
    $routes
        ->add('req-faktur', '/req-faktur/{id}')
        ->controller([UserPpkTreasurerController::class, 'req_faktur_pajak'])
        ->methods(['GET'])
    ;

    $routes
        ->add('detail', '/detail/{id}')
        ->controller([UserPpkTreasurerController::class, 'detail'])
        ->methods(['GET'])
    ;

    $routes
        ->add('detail-pemesanan', '/detail-pemesanan/{id}')
        ->controller([UserPpkTreasurerController::class, 'detailPemesanan'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([UserPpkTreasurerController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('save_detail', '/save')
        ->controller([UserPpkTreasurerController::class, 'save_detail'])
        ->methods(['POST'])
    ;

    $routes
        ->add('other_document', '/other_document')
        ->controller([UserPpkTreasurerController::class, 'other_document'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([UserPpkTreasurerController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('approve', '/approve')
        ->controller([UserPpkTreasurerController::class, 'approve'])
        ->methods(['POST'])
    ;

    $routes
        ->add('received', '/received')
        ->controller([UserPpkTreasurerController::class, 'received'])
        ->methods(['POST'])
    ;

    $routes
        ->add('edit', '/edit/{id}')
        ->controller([UserPpkTreasurerController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ; 

    $routes
        ->add('update', '/update')
        ->controller([UserPpkTreasurerController::class, 'update'])
        ->methods(['POST'])
    ;

    $routes
        ->add('addendum_ppk', '/addendum-ppk/{id}')
        ->controller([UserPpkTreasurerController::class, 'addendum_ppk'])
        ->methods(['GET'])
    ;
};
