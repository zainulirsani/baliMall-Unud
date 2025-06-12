<?php

use App\Controller\User\UserLKPPController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('lkpp_login', '/login/lkpp')
        ->controller([UserLKPPController::class, 'login'])
        ->methods(['GET'])
    ;

    $routes
        ->add('lkpp_portal_login', '/portal/login/lkpp')
        ->controller([UserLKPPController::class, 'portalLogin'])
        ->methods(['POST'])
    ;

    $routes
        ->add('lkpp_portal_logout', '/portal/logout/lkpp')
        ->controller([UserLKPPController::class, 'portalLogout'])
        ->methods(['POST'])
    ;

    $routes
        ->add('lkpp_portal_import', '/portal/import/lkpp')
        ->controller([UserLKPPController::class, 'portalImport'])
        ->methods(['GET'])
    ;

    $routes
        ->add('lkpp_portal_get_booking', '/portal/get-booking/lkpp')
        ->controller([UserLKPPController::class, 'portalGetBooking'])
        ->methods(['POST'])
    ;

    $routes
        ->add('lkpp_portal_send_rup', '/portal/send-rup/lkpp')
        ->controller([UserLKPPController::class, 'portalSendRUP'])
        ->methods(['POST'])
    ;
};
