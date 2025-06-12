<?php

use App\Controller\User\UserSatkerController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([UserSatkerController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('dashboard', '/dashboard')
        ->controller([UserSatkerController::class, 'dashboard'])
        ->methods(['GET'])
    ;

    $routes
        ->add('new', '/new')
        ->controller([UserSatkerController::class, 'new'])
        ->methods(['GET'])
    ;

    $routes
        ->add('export', '/export')
        ->controller([UserSatkerController::class, 'export'])
        ->methods(['GET'])
    ;
    
    $routes
        ->add('req-faktur', '/req-faktur/{id}')
        ->controller([UserSatkerController::class, 'req_faktur_pajak'])
        ->methods(['GET'])
    ;

    $routes
        ->add('detail', '/detail/{id}')
        ->controller([UserSatkerController::class, 'detail'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([UserSatkerController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('save_detail', '/save')
        ->controller([UserSatkerController::class, 'save_detail'])
        ->methods(['POST'])
    ;


    $routes
        ->add('delete', '/delete')
        ->controller([UserSatkerController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('approve', '/approve')
        ->controller([UserSatkerController::class, 'approve'])
        ->methods(['POST'])
    ;

    $routes
        ->add('received', '/received')
        ->controller([UserSatkerController::class, 'received'])
        ->methods(['POST'])
    ;

    $routes
        ->add('edit', '/edit/{id}')
        ->controller([UserSatkerController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ; 

    $routes
        ->add('update', '/update')
        ->controller([UserSatkerController::class, 'update'])
        ->methods(['POST'])
    ;

};
