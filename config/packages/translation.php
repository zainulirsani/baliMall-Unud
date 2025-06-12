<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('framework', [
    'default_locale' => '%locale%',
    'translator' => [
        'paths' => ['%kernel.project_dir%/translations'],
        'fallbacks' => ['%locale%'],
    ],
]);
