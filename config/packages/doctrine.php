<?php

use DoctrineExtensions\Query\Mysql;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->setParameter('env(DATABASE_URL)', '');

$container->loadFromExtension('doctrine', [
    'dbal' => [
        'url' => '%env(resolve:DATABASE_URL)%',
        'driver' => 'pdo_mysql',
        'server_version' => '%env(DB_SERVER_VERSION)%',
        'charset' => 'utf8mb4',
        'default_table_options' => [
            'collate' => 'utf8mb4_unicode_ci',
        ],
    ],
    'orm' => [
        'auto_generate_proxy_classes' => '%kernel.debug%',
        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
        'auto_mapping' => true,
        'mappings' => [
            'App' => [
                'is_bundle' => false,
                'type' => 'annotation',
                'dir' => '%kernel.project_dir%/src/Entity',
                'prefix' => 'App\Entity',
                'alias' => 'App',
            ],
        ],
        'dql' => [
            'string_functions' => [
                'find_in_set' => Mysql\FindInSet::class,
            ],
            'numeric_functions' => [
                'rand' => Mysql\Rand::class,
            ],
            'datetime_functions' => [
                'date' => Mysql\Date::class,
                'day' => Mysql\Day::class,
                'month' => Mysql\Month::class,
                'year' => Mysql\Year::class,
            ],
        ],
    ],
]);
