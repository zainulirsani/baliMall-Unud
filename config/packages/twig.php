<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('twig', [
    'debug' => '%kernel.debug%',
    'strict_variables' => '%kernel.debug%',
    'auto_reload' => true,
    'exception_controller' => null,
    'date' => [
        'timezone' => 'Asia/Jakarta',
    ],
]);
