<?php

use App\Controller\User\AdminUserController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('index', '/')
        ->controller([AdminUserController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('create', '/new')
        ->controller([AdminUserController::class, 'create'])
        ->methods(['GET'])
    ;

    $routes
        ->add('save', '/new')
        ->controller([AdminUserController::class, 'saveUser'])
        ->methods(['POST'])
    ;

    $routes
        ->add('delete', '/delete')
        ->controller([AdminUserController::class, 'delete'])
        ->methods(['POST'])
    ;

    $routes
        ->add('action', '/action')
        ->controller([AdminUserController::class, 'action'])
        ->methods(['POST'])
    ;

    $routes
        ->add('export', '/export')
        ->controller([AdminUserController::class, 'export'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('fetch_data', '/fetch_data')
        ->controller([AdminUserController::class, 'fetchData'])
        ->methods(['POST'])
    ;

    $routes
        ->add('fetch_select', '/fetch_select')
        ->controller([AdminUserController::class, 'fetchSelect'])
        ->methods(['GET'])
    ;

    $routes
        ->add('send_activation_mail', '/send_activation_mail')
        ->controller([AdminUserController::class, 'sendActivationMail'])
        ->methods(['POST'])
    ;

    $routes
        ->add('chat_room', '/chat/{room}')
        ->controller([AdminUserController::class, 'chatRoom'])
        ->methods(['POST'])
    ;

    $routes
        ->add('import_lkpp', '/import/lkpp')
        ->controller([AdminUserController::class, 'importLKPP'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->import('user_address.php')
        ->prefix('/address')
        ->namePrefix('address_')
    ;

    $routes
        ->add('view', '/{id}/view')
        ->controller([AdminUserController::class, 'read'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('edit', '/{id}/edit')
        ->controller([AdminUserController::class, 'edit'])
        ->requirements(['id' => '\d+'])
        ->methods(['GET'])
    ;

    $routes
        ->add('update', '/{id}/edit')
        ->controller([AdminUserController::class, 'update'])
        ->requirements(['id' => '\d+'])
        ->methods(['POST'])
    ;
};
