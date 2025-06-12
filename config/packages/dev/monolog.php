<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('monolog', [
    'handlers' => [
        'main' => [
            'type' => 'rotating_file',
            'path' => '%kernel.logs_dir%/%kernel.environment%.log',
            'level' => 'debug',
            'channels' => ['!event'],
        ],
        'console' => [
            'type' => 'console',
            'process_psr_3_messages' => false,
            'channels' => ['!event', '!doctrine', '!console'],
        ],
    ],
]);
