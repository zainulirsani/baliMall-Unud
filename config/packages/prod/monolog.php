<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('monolog', [
    'handlers' => [
        'main' => [
            'type' => 'fingers_crossed',
            'action_level' => 'error',
            'handler' => 'nested',
            'excluded_404s' => ['^/'],
        ],
        'nested' => [
            'type' => 'rotating_file',
            'path' => '%kernel.logs_dir%/%kernel.environment%.log',
            'level' => 'debug',
        ],
        'console' => [
            'type' => 'console',
            'process_psr_3_messages' => false,
            'channels' => ['!event', '!doctrine'],
        ],
    ],
]);
