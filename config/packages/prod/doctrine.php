<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Cache\DoctrineProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('doctrine', [
    'orm' => [
        'metadata_cache_driver' => [
            'type' => 'service',
            'id' => 'doctrine.system_cache_provider',
        ],
        'query_cache_driver' => [
            'type' => 'service',
            'id' => 'doctrine.system_cache_provider',
        ],
        'result_cache_driver' => [
            'type' => 'service',
            'id' => 'doctrine.result_cache_provider',
        ],
    ],
]);

$container->loadFromExtension('framework', [
    'cache' => [
        'pools' => [
            'doctrine.result_cache_pool' => [
                'adapter' => 'cache.app',
            ],
            'doctrine.system_cache_pool' => [
                'adapter' => 'cache.system',
            ],
        ],
    ],
]);

return function (ContainerConfigurator  $configurator) {
    $services = $configurator->services();

    $services
        ->set('doctrine.result_cache_provider', DoctrineProvider::class)
        ->args([ref('doctrine.result_cache_pool')])
    ;

    $services
        ->set('doctrine.system_cache_provider', DoctrineProvider::class)
        ->args([ref('doctrine.system_cache_pool')])
    ;
};
