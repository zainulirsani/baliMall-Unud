<?php

use App\Controller\User\UserPicDocumentController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([UserPicDocumentController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('new', '/new')
        ->controller([UserPicDocumentController::class, 'new'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([UserPicDocumentController::class, 'save'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([UserPicDocumentController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('edit', '/edit/{id}')
        ->controller([UserPicDocumentController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ; 

    $routes
        ->add('update', '/update')
        ->controller([UserPicDocumentController::class, 'update'])
        ->methods(['POST'])
    ;

};
