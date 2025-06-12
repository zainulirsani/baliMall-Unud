<?php

use App\Controller\Site\HelperPageController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('time_remaining', '/_tr')
        ->controller([HelperPageController::class, 'timeRemaining'])
        ->methods(['POST'])
    ;

    $routes
        ->add('set_active_locale', '/_sl')
        ->controller([HelperPageController::class, 'setActiveLocale'])
        ->methods(['POST'])
    ;

    /*$routes
        ->add('geocode', '/geocode')
        ->controller([HelperPageController::class, 'geocode'])
        ->methods(['POST'])
    ;*/

    $routes
        ->add('email_activation', '/email-activation/{code}')
        ->controller([HelperPageController::class, 'emailActivation'])
        ->methods(['GET'])
    ;

    $routes
        ->add('email_check', '/email-check')
        ->controller([HelperPageController::class, 'emailCheck'])
        ->methods(['POST'])
    ;

    $routes
        ->add('forgot_password', '/forgot-password')
        ->controller([HelperPageController::class, 'forgotPassword'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('recover_password', '/recover-password/{code}')
        ->controller([HelperPageController::class, 'recoverPassword'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('find_product', '/find-product')
        ->controller([HelperPageController::class, 'findProduct'])
        ->methods(['POST'])
    ;
};
