<?php

use App\Controller\Site\StaticPageController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('terms_and_conditions', '/terms-and-conditions')
        ->controller([StaticPageController::class, 'termsAndConditions'])
        ->methods(['GET'])
    ;

    $routes
        ->add('privacy_policy', '/privacy-policy')
        ->controller([StaticPageController::class, 'privacyPolicy'])
        ->methods(['GET'])
    ;

    $routes
        ->add('contact', '/contact')
        ->controller([StaticPageController::class, 'contact'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('faq', '/faq')
        ->controller([StaticPageController::class, 'faq'])
        ->methods(['GET'])
    ;

    $routes
        ->add('be_a_vendor', '/be-a-merchant')
        ->controller([StaticPageController::class, 'beAVendor'])
        ->methods(['GET'])
    ;

    $routes
        ->add('tips', '/tips')
        ->controller([StaticPageController::class, 'tips'])
        ->methods(['GET'])
    ;

    $routes
        ->add('merchant_terms_and_conditions', '/merchant-terms-and-conditions')
        ->controller([StaticPageController::class, 'merchantTermsAndConditions'])
        ->methods(['GET'])
    ;
};
