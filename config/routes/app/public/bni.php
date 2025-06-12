<?php

use App\Controller\User\BniController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('dashboard', '/dashboard')
        ->controller([BniController::class, 'dashboard'])
        ->methods(['GET'])
    ;

    $routes
        ->add('bni_payment_multiple', 'bni_payment_multiple')
        ->controller([BniController::class, 'bniPaymentMultiple'])
        ->methods(['POST'])
    ;
};
