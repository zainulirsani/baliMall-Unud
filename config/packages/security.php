<?php

use App\Entity\User;
use App\EventListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('security', [
    'providers' => [
        'user_provider' => [
            'entity' => [
                'class' => User::class,
            ],
        ],
    ],
    'encoders' => [
        User::class => 'bcrypt',
    ],
    'role_hierarchy' => [
        'ROLE_USER' => ['ROLE_USER_SELLER', 'ROLE_USER_BUYER', 'ROLE_USER_GOVERNMENT', 'ROLE_USER_BUSINESS'],
        'ROLE_ADMIN' => ['ROLE_USER'],
        'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'],
    ],
    'firewalls' => [
        'dev' => [
            'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
            'security' => false,
        ],
        'admin' => [
            'pattern' => '/%admin_url%(.*)',
            'provider' => 'user_provider',
            'form_login' => [
                'login_path' => '/%admin_url%/login',
                'check_path' => '/%admin_url%/login_check',
                'csrf_token_generator' => 'security.csrf.token_manager',
                'default_target_path' => 'admin_dashboard',
                'use_referer' => true,
            ],
            'logout' => [
                'path' => '/%admin_url%/logout',
                'target' => '/%admin_url%/login',
                'invalidate_session' => true,
            ],
            'anonymous' => true,
        ],
        'main' => [
            'pattern' => '.*',
            'provider' => 'user_provider',
            'form_login' => [
                'login_path' => '/login',
                'check_path' => '/login_check',
                'csrf_token_generator' => 'security.csrf.token_manager',
                'default_target_path' => 'user_dashboard',
                'use_referer' => true,
                'success_handler' => EventListener\UserLoginListener::class,
            ],
            'logout' => [
                'path' => '/logout',
                'target' => '/',
                'invalidate_session' => true,
                'success_handler' => EventListener\UserLogoutListener::class,
            ],
            'anonymous' => true,
        ],
    ],
    'access_control' => [
        [
            'path' => '^/register',
            'role' => 'IS_AUTHENTICATED_ANONYMOUSLY',
        ],
        [
            'path' => '^/login$',
            'role' => 'IS_AUTHENTICATED_ANONYMOUSLY',
        ],
        [
            'path' => '^/login_check$',
            'role' => 'IS_AUTHENTICATED_ANONYMOUSLY',
        ],
        [
            'path' => '^/logout$',
            'role' => 'IS_AUTHENTICATED_ANONYMOUSLY',
        ],
        [
            'path' => '^/%admin_url%/login$',
            'role' => 'IS_AUTHENTICATED_ANONYMOUSLY',
        ],
        [
            'path' => '^/%admin_url%/login_check$',
            'role' => 'IS_AUTHENTICATED_ANONYMOUSLY',
        ],
        [
            'path' => '^/%admin_url%/logout$',
            'role' => 'IS_AUTHENTICATED_ANONYMOUSLY',
        ],
        [
            'path' => '^/%admin_url%',
            'role' => [
                'ROLE_ADMIN','ROLE_ACCOUNTING_1','ROLE_ACCOUNTING_2', 'ROLE_HELPDESK_USER',
                'ROLE_HELPDESK_MERCHANT','ROLE_ADMIN_PRODUCT','ROLE_ADMIN_MERCHANT', 'ROLE_ADMIN_VOUCHER',
                'ROLE_ADMIN_MERCHANT_CABANG'
            ],
        ],
        [
            'path' => '^/user',
            'role' => ['ROLE_USER', 'ROLE_USER_SELLER', 'ROLE_USER_BUYER', 'ROLE_USER_GOVERNMENT', 'ROLE_USER_BUSINESS'],
        ],
        [
            'path' => '^/.*',
            'role' => 'IS_AUTHENTICATED_ANONYMOUSLY',
        ],
    ],
]);
