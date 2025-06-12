<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->setParameter('admin_menu', [
    'product' => [
        'label' => 'menu.product',
        'href' => 'admin_product_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-tags',
        'children' => [
            'product' => [
                'label' => 'menu.product',
                'href' => 'admin_product_index',
                'options' => [],
                'id' => '',
                'class' => '',
                'icon' => 'fa-angle-double-right',
            ],
            'product_category' => [
                'label' => 'menu.category',
                'href' => 'admin_product_category_index',
                'options' => [],
                'id' => '',
                'class' => '',
                'icon' => 'fa-angle-double-right',
                'hide_for_role' => [
                    'ROLE_ADMIN_MERCHANT_CABANG'
                ]
            ],
        ],
    ],
    'order' => [
        'label' => 'menu.order',
        'href' => 'admin_order_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-bar-chart',
        'children' => [
            // 'order_buyer' => [
            //     'label' => 'menu.user_buyer',
            //     'href' => 'admin_order_index',
            //     'options' => ['role' => 'buyer'],
            //     'id' => '',
            //     'class' => '',
            //     'icon' => 'fa-angle-double-right',
            // ],
            'order_business' => [
                'label' => 'menu.user_business',
                'href' => 'admin_order_index',
                'options' => ['role' => 'business'],
                'id' => '',
                'class' => '',
                'icon' => 'fa-angle-double-right',
            ],
            'order_government' => [
                'label' => 'menu.user_government',
                'href' => 'admin_order_index',
                'options' => ['role' => 'government'],
                'id' => '',
                'class' => '',
                'icon' => 'fa-angle-double-right',
            ],
        ],
    ],
    'store' => [
        'label' => 'menu.store',
        'href' => 'admin_store_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-sitemap',
        'children' => [],
    ],
    'voucher' => [
        'label' => 'menu.voucher',
        'href' => 'admin_voucher_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-gift',
        'children' => [],
        'hide_for_role' => [
            'ROLE_ADMIN_MERCHANT_CABANG'
        ]
    ],
    'bank' => [
        'label' => 'menu.bank',
        'href' => 'admin_bank_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-university',
        'children' => [],
        'hide_for_role' => [
            'ROLE_ADMIN_MERCHANT_CABANG'
        ]
    ],
    'user' => [
        'label' => 'menu.user',
        'href' => 'admin_user_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-users',
        'children' => [
            // 'buyer' => [
            //     'label' => 'menu.user_buyer',
            //     'href' => 'admin_user_index',
            //     'options' => ['role' => 'buyer'],
            //     'id' => '',
            //     'class' => '',
            //     'icon' => 'fa-angle-double-right',
            // ],
            'seller' => [
                'label' => 'menu.user_seller',
                'href' => 'admin_user_index',
                'options' => ['role' => 'seller'],
                'id' => '',
                'class' => '',
                'icon' => 'fa-angle-double-right',
            ],
            // 'business' => [
            //     'label' => 'menu.user_business',
            //     'href' => 'admin_user_index',
            //     'options' => ['role' => 'business'],
            //     'id' => '',
            //     'class' => '',
            //     'icon' => 'fa-angle-double-right',
            // ],
            'government' => [
                'label' => 'menu.user_government',
                'href' => 'admin_user_index',
                'options' => ['role' => 'government'],
                'id' => '',
                'class' => '',
                'icon' => 'fa-angle-double-right',
            ],
            'admin' => [
                'label' => 'menu.user_admin',
                'href' => 'admin_user_index',
                'options' => ['role' => 'admin'],
                'id' => '',
                'class' => '',
                'icon' => 'fa-angle-double-right',
                'hide_for_role' => [
                    'ROLE_ADMIN_MERCHANT_CABANG'
                ]
            ],
        ],
    ],

    'kldi' => [
        'label' => 'menu.kldi',
        'href' => 'admin_kldi_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-users',
        'children' => [],
        'hide_for_role' => [
            'ROLE_ADMIN_MERCHANT_CABANG'
        ]
    ],

    'satker' => [
        'label' => 'menu.satker',
        'href' => 'admin_satker_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-university',
        'children' => [],
        'hide_for_role' => [
            'ROLE_ADMIN_MERCHANT_CABANG'
        ]
    ],
    'banner' => [
        'label' => 'menu.banner',
        'href' => 'admin_banner_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-image',
        'children' => [],
        'hide_for_role' => [
            'ROLE_ADMIN_MERCHANT_CABANG'
        ]
    ],
    'mastertax' => [
        'label' => 'menu.master_tax',
        'href' => 'admin_mastertax_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-usd',
        'children' => [],
        'hide_for_role' => [
            'ROLE_ADMIN_MERCHANT_CABANG'
        ]
    ],
    'disbursement' => [
        'label' => 'menu.disbursement',
        'href' => 'admin_disbursement_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-money',
        'children' => [
            'disbursement_buyer' => [
                'label' => 'menu.user_buyer',
                'href' => 'admin_disbursement_index',
                'options' => ['role' => 'buyer'],
                'id' => '',
                'class' => '',
                'icon' => 'fa-angle-double-right',
            ],
            'disbursement_government' => [
                'label' => 'menu.user_government',
                'href' => 'admin_disbursement_index',
                'options' => ['role' => 'government'],
                'id' => '',
                'class' => '',
                'icon' => 'fa-angle-double-right',
            ],
        ],
    ],
    'newsletter' => [
        'label' => 'menu.newsletter',
        'href' => 'admin_newsletter_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-mail-reply-all',
        'children' => [],
        'hide_for_role' => [
            'ROLE_ADMIN_MERCHANT_CABANG'
        ]
    ],
    'setting' => [
        'label' => 'menu.setting',
        'href' => 'admin_setting_index',
        'options' => [],
        'id' => '',
        'class' => '',
        'icon' => 'fa-cog',
        'children' => [],
        'hide_for_role' => [
            'ROLE_ADMIN_MERCHANT_CABANG'
        ]
    ],
]);
