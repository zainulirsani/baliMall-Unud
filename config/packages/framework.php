<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('framework', [
    'secret' => '%env(APP_SECRET)%',
    'csrf_protection' => [
        'enabled' => true,
    ],
    //'http_method_override' => true,
    //'trusted_hosts' => '%env(TRUSTED_HOSTS)%',
    'assets' => [
        'json_manifest_path' => null,
        'version' => '%env(APP_ASSETS_VERSION)%',
    ],
    'session' => [
        'enabled' => true,
        'handler_id' => null,
        'cookie_lifetime' => 7200,
        'cookie_secure' => getenv('APP_COOKIE_SECURE') === 'yes',
        'cookie_samesite' => getenv('APP_COOKIE_SAMESITE'),
    ],
    //'esi' => null,
    //'fragments' => null,
    'php_errors' => [
        'log' => true,
    ],
    'cache' => null,
    'error_controller' => 'App\Controller\CustomExceptionController::show',
]);
