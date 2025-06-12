<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('web_profiler', [
    'toolbar' => true,
    'intercept_redirects' => false,
]);

$container->loadFromExtension('framework', [
    'profiler' => [
        'only_exceptions' => false,
    ],
]);
