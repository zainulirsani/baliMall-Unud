<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('doctrine_migrations', [
    'dir_name' => '%kernel.project_dir%/src/Migrations',
    'namespace' => 'DoctrineMigrations',
]);
