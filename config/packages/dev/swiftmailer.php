<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('swiftmailer', [
    'delivery_addresses' => [
        '%admin_email%',
    ],
]);
