<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use App\Controller\Disbursement\AdminDisbursementController;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminDisbursementController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('create', '/new')
        ->controller([AdminDisbursementController::class, 'create'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([AdminDisbursementController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminDisbursementController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('action', '/action')
        ->controller([AdminDisbursementController::class, 'action'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminDisbursementController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminDisbursementController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminDisbursementController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminDisbursementController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;

    $routes
        ->add('done', '/done')
        ->controller([AdminDisbursementController::class, 'setDisbursementToDone'])
        ->methods(['POST']);

    $routes
        ->add('set_status', '/set_status')
        ->controller([AdminDisbursementController::class, 'setDisbursementStatus'])
        ->methods(['POST']);

    $routes
        ->add('export', '/export')
        ->controller([AdminDisbursementController::class, 'export'])
        ->methods(['GET', 'POST'])
    ;

};
