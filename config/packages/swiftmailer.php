<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('swiftmailer', [
    'url' => '%env(MAILER_URL)%',
    'spool' => [
        'type' => 'memory',
    ],
]);
