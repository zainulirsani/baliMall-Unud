<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->setParameter('locale', 'id');
$container->setParameter('supported_locales', ['en', 'id']);
$container->setParameter('app_name', '%env(APP_NAME)%');
$container->setParameter('app_version', '%env(APP_VERSION)%');
$container->setParameter('admin_url', '%env(APP_ADMIN_URL)%');
$container->setParameter('admin_email', '%env(APP_ADMIN_EMAIL)%');
$container->setParameter('public_path', '%env(APP_PUBLIC_PATH)%');
$container->setParameter('public_dir_path', '%kernel.project_dir%/%public_path%');
$container->setParameter('upload_dir', 'uploads');
$container->setParameter('upload_dir_path', '%public_dir_path%/%upload_dir%');
$container->setParameter('media_dir', 'uploads/media');
$container->setParameter('media_dir_path', '%public_dir_path%/%media_dir%');
$container->setParameter('max_upload_image', 10240000); // 10 MB
$container->setParameter('max_upload_video', 51200000); // 50 MB
$container->setParameter('max_upload_file', 10240000); // 10 MB
$container->setParameter('session_expiration', 7200); // 2 Hours
$container->setParameter('google_map_api_key', '%env(GOOGLE_MAP_API_KEY)%');
$container->setParameter('google_map_server_key', '%env(GOOGLE_MAP_SERVER_KEY)%');
$container->setParameter('result_per_page', 10);
$container->setParameter('item_per_page', 12);
$container->setParameter('search_per_page', 9);
$container->setParameter('mail_sender', '%env(MAIL_SENDER)%');
$container->setParameter('mail_sender_name', '%env(MAIL_SENDER_NAME)%');
$container->setParameter('mail_receiver', '%env(MAIL_RECEIVER)%');
$container->setParameter('send_in_blue_key', '%env(SEND_IN_BLUE_KEY)%');
$container->setParameter('analytics_tracking_id', '%env(ANALYTICS_TRACKING_ID)%');
$container->setParameter('howuku_tracking_id', '%env(HOWUKU_TRACKING_ID)%');
$container->setParameter('gtm_tracking_id', '%env(GTM_TRACKING_ID)%');
$container->setParameter('fb_pixel_code', '%env(FB_PIXEL_CODE)%');
$container->setParameter('leaflet_access_token', '%env(LEAFLET_ACCESS_TOKEN)%');
$container->setParameter('maintenance_mode', getenv('APP_MAINTENANCE_MODE') === 'yes');
$container->setParameter('enable_chat', getenv('APP_ENABLE_CHAT') === 'yes');
$container->setParameter('app_url', getenv('APP_URL'));
$container->setParameter('rate_limit', 60);
$container->setParameter('tax_value', 11); // In percent
$container->setParameter('qris_amount_limit', 2000000);
$container->setParameter('lkpp_client_id', '%env(LKPP_CLIENT_ID)%');
$container->setParameter('lkpp_client_secret', '%env(LKPP_CLIENT_SECRET)%');
$container->setParameter('allowed_image_ext', ['jpg', 'jpeg', 'png', 'gif']);
$container->setParameter('reserved_image_mime_types', ['image/jpeg', 'image/png', 'image/gif']);
$container->setParameter('allowed_video_ext', ['mp4', 'webm', 'ogv', 'flv']);
$container->setParameter('reserved_video_mime_types', ['video/mp4', 'video/webm', 'video/ogg', 'video/x-flv']);
$container->setParameter('allowed_doc_ext', ['doc', 'xls', 'pdf']);
$container->setParameter('reserved_doc_mime_types', ['application/msword', 'application/vnd.ms-excel', 'application/pdf']);
$container->setParameter('csrf_exclude_routes', ['form_search_global']);
$container->setParameter('csrf_whitelist', [
    'order_qris_callback', 'order_va_callback', 'lkpp_portal_login', 'lkpp_portal_logout',
    'gosend-webhooks', 'doku-qris', 'doku-va', 'doku-cc', 'midtrans',
    'api_product_update', 'api_balimall_portal_login', 'api_balimall_portal_logout',
    'api_erzap_connect', 'api_erzap_product_create', 'api_erzap_product_update_price', 'api_erzap_product_update_stock',
    'api_erzap_product_delete','api_erzap_product_by_sku','api_erzap_product_by_idproduk_mp', 'ppk_approve', 'bni-va',
    'user_requestbinding_save', 'user_requestbinding_delete', 'user_order_print', 'admin_order_print'

]);
$container->setParameter('active_inactive', ['inactive', 'active']);
$container->setParameter('publish_draft', ['draft', 'pending', 'publish', 'new_product', 'product_updated']);
$container->setParameter('verified_unverified', ['unverified', 'verified']);
$container->setParameter('yes_no', ['no', 'yes']);
$container->setParameter('store_reserved_names', ['admin', 'media', 'user', 'cart', 'order', 'api', 'backend', 'secure', 'assets', 'uploads', 'vendor', 'dist', 'terms-and-conditions', 'privacy-policy', 'contact', 'faq', 'tips', 'register', 'login', 'notification', 'portal']);
$container->setParameter('images_reserved_names', ['dist/img/bg.jpg', 'dist/img/user.jpg', 'dist/img/no-image.png']);
$container->setParameter('bali_local_regions', ['Badung', 'Bangli', 'Buleleng', 'Denpasar', 'Gianyar', 'Jembrana', 'Karangasem', 'Klungkung', 'Tabanan']);
$container->setParameter('ajax_csrf_token_id', [
    'admin' => 'admin_ajax_request',
    'public' => 'public_ajax_request',
]);
$container->setParameter('user_roles', [
    'ROLE_USER' => 'ROLE_USER',
    'ROLE_USER_SELLER' => 'ROLE_USER_SELLER',
    'ROLE_USER_BUYER' => 'ROLE_USER_BUYER',
    'ROLE_USER_GOVERNMENT' => 'ROLE_USER_GOVERNMENT',
    'ROLE_USER_BUSINESS' => 'ROLE_USER_BUSINESS',
    'ROLE_ADMIN' => 'ROLE_ADMIN',
    'ROLE_ACCOUNTING_1' => 'ROLE_ACCOUNTING_1',
    'ROLE_ACCOUNTING_2' => 'ROLE_ACCOUNTING_2',
    'ROLE_HELPDESK_USER' => 'ROLE_HELPDESK_USER',
    'ROLE_HELPDESK_MERCHANT' => 'ROLE_HELPDESK_MERCHANT',
    'ROLE_ADMIN_PRODUCT' => 'ROLE_ADMIN_PRODUCT',
    'ROLE_ADMIN_MERCHANT' => 'ROLE_ADMIN_MERCHANT',
    'ROLE_ADMIN_VOUCHER' => 'ROLE_ADMIN_VOUCHER',
    'ROLE_ADMIN_MERCHANT_CABANG' => 'ROLE_ADMIN_MERCHANT_CABANG',
]);

$container->setParameter('user_sub_roles', [
    'PP' => 'PP',
    'PPK' => 'PPK',
    'TREASURER' => 'TREASURER',
]);

$container->setParameter('gender_choices', [
    'male' => 'label.male',
    'female' => 'label.female',
]);
$container->setParameter('price_types', [
    'hour' => 'label.hour',
    'day' => 'label.day',
    'week' => 'label.week',
    'month' => 'label.month',
    'year' => 'label.year',
]);
$container->setParameter('setting_types', [
    'text' => 'Text',
    'textarea' => 'Textarea',
    'password' => 'Password',
    'select' => 'Select',
    'select_multiple' => 'Select Multiple',
    'radio' => 'Radio',
    'checkbox' => 'Checkbox',
    'image' => 'Image',
]);
$container->setParameter('unit_types', [
    'unit' => 'label.unit',
    'pcs' => 'label.pcs_alt',
    'box' => 'label.box',
    'package' => 'label.package',
]);
$container->setParameter('image_resize_config', [
    'default' => [
        'width' => 1440,
        'height' => null,
    ],
    'product' => [
        'width' => 1200,
        'height' => null, // 900
    ],
    'photo_profile' => [
        'width' => 720,
        'height' => null,
    ],
]);
$container->setParameter('cart_config', [
    'cart_max_item' => 0,
    'item_max_quantity' => 0,
    'use_cookie' => false,
]);
$container->setParameter('raja_ongkir_config', [
    'base_url' => '%env(RAJA_ONGKIR_BASE_URL)%',
    'account_type' => '%env(RAJA_ONGKIR_ACCOUNT_TYPE)%',
    'api_key' => '%env(RAJA_ONGKIR_API_KEY)%',
    'rebuild_cache' => false,
]);
$container->setParameter('raja_ongkir_couriers', [
    // Additional
    //    'free_shipping' => 'Kantor Balimall Jl. Moh Yamin IX No.19 Denpasar Bali (Catatan: biaya pengiriman dari
    //    lokasi merchant ke kantor balimall.id menjadi tanggungan merchant)',
    'free_shipping' => 'Kantor Balimall Jl. Moh Yamin IX No.19 Denpasar Bali',
    'free_shipping_2' => 'Pameran Bali Bangkit',
    'free_shipping_3' => 'Detran (Layanan Pengiriman Gratis Wilayah Denpasar Khusus Pameran Seni Rupa)',
    'free_shipping_4' => 'Pengambilan di Koridor Kedatangan Domestik Bandara I Gusti Ngurah Rai Bali',
    //'samitra' => 'Samitra Ekspedisi (SAMITRA)',
    // Starter
    'jne' => 'Jalur Nugraha Ekakurir (JNE)',
    'tiki' => 'Citra Van Titipan Kilat (TIKI)',
    'pos' => 'POS Indonesia (POS)',
    // Basic
    'pcp' => 'Priority Cargo and Package (PCP)',
    'esl' => 'Eka Sari Lorena (ESL)',
    'rpx' => 'RPX Holding (RPX)',
    // Pro
    'pandu' => 'Pandu Logistics (PANDU)',
    'wahana' => 'Wahana Prestasi Logistik (WAHANA)',
    'jnt' => 'J&T Express (J&T)',
    'sicepat' => 'SiCepat Express (SICEPAT)',
    'pahala' => 'Pahala Kencana Express (PAHALA)',
    'sap' => 'SAP Express (SAP)',
    'jet' => 'JET Express (JET)',
    'slis' => 'Solusi Ekspres (SLIS)',
    'dse' => '21 Express (DSE)',
    'first' => 'First Logistics (FIRST)',
    'ncs' => 'Nusantara Card Semesta (NCS)',
    'star' => 'Star Cargo (STAR)',
    'lion' => 'Lion Parcel (LION)',
    'ninja' => 'Ninja Xpress (NINJA)',
    'idl' => 'IDL Cargo (IDL)',
    'rex' => 'Royal Express Indonesia (REX)',
    //'expedito' => 'Expedito (EXPEDITO)',
    'gosend' => 'GoSend'
]);
$container->setParameter('bank_lists', [
    'bca' => 'BCA',
    'bni' => 'BNI',
    'mandiri' => 'Mandiri',
    'permata' => 'Permata',
    'cimb' => 'CIMB',
    'bri' => 'BRI',
    'btn' => 'BTN',
    'danamon' => 'DANAMON',
    'mega' => 'BANK MEGA',
    'sinarmas' => 'SINARMAS',
    'bukopin' => 'BUKOPIN',
    'lippo' => 'LIPPO',
    'bpd_bali' => 'BPD BALI',
    'other' => 'LAINNYA',
]);
$container->setParameter('order_statuses', [
    'new_order' => 'label.new_order',
    'pending' => 'label.pending',
    'pending_payment' => 'label.pending_payment',
    'approve_order_ppk' => 'label.approve_order_ppk',
    'pending_approve' => 'label.pending_approve',
    'paid' => 'label.paid',
    'payment_process' => 'label.payment_process',
    'confirmed' => 'label.confirmed',
    'processed' => 'label.processed',
    'confirm_order_ppk' => 'label.confirm_order_ppk',
    'approved_order' => 'label.approved_order',
    'shipped' => 'label.shipped',
    'received' => 'label.received',
    'cancel' => 'label.cancelled',
    'cancel_request' => 'label.cancel_request',
    'tax_invoice' => 'label.tax_invoice',
    'document' => 'label.document',
    'partial_delivery' => 'label.partial_delivery',
]);
$container->setParameter('predefined_voucher_amount', [
    '50000' => 'Rp. 50.000',
    '100000' => 'Rp. 100.000',
]);
$container->setParameter('order_payment_accounts', [
    'b2g' => ['BPD Cabang Renon', 'PT Bali Unggul Sejahtera', '010.01.11.000290'],
    'b2c_with_tax_1' => ['BPD Cab. Renon', 'PT. Elka Solutions Nusantara', '010.01.11000368'],
    'b2c_with_tax_2' => ['MANDIRI Cab. Jkt Mediterania Tj Duren', 'PT. Elka Solutions Nusantara', '165-00-0190703-0'],
    'b2c_without_tax_1' => ['BPD Cab. Renon Denpasar', 'CV. ELKA MANDIRI', '010.01.1100038.2'],
    'b2c_without_tax_2' => ['BRI Cab. Denpasar Renon', 'CV. ELKA MANDIRI', '0368-01-002-416-304'],
    'all_1' => ['BPD Cabang Renon', 'PT Bali Unggul Sejahtera', '010.01.11.000290'],
    'all_2' => ['Mandiri Cabang Denpasar Udayana', 'PT Bali Unggul Sejahtera', '145.001.337.6419'],
]);
$container->setParameter('execution_time_options', [
    30 => 'label.30_days',
    60 => 'label.60_days',
    90 => 'label.90_days',
]);

$container->setParameter('lkpp_restricted_categories', [
    'stage' => [
        12 => [4, 5, 10, 33],
    ],
    'prod' => [
        12 => [
            25, 29, 66, 67, 5, 6, 68, 148, 149, 150, 151, 74, 75, 76, 77, 10, 30, 65, 4, 14, 27, 12, 28, 138, 126, 158, 159, 7, 13, 26, 84, 160, 166,
            16, 23, 32, 93, 114, 162, 4, 14, 1, 11, 167, 82, 100, 152, 163, 139, 140, 141, 142, 168, 109, 164, 44, 45, 46, 48, 53, 54, 55, 56, 57, 58, 59, 156, 171, 172,
            85, 86, 87, 88, 129, 137, 128, 172, 192, 193
        ],
    ],
]);

$container->setParameter('lkpp_restricted_merchant_classification', [
    'bela_pengadaan' => ['"USAHA_MIKRO"', '"USAHA_KECIL"'],
    'kurasi_lainnya' => ['"USAHA_MENENGAH"', '"USAHA_BESAR"'],
]);

$container->setParameter('type_of_business', [
    'PERSEORANGAN' => [
        'label' => 'individual',
    ],
    'BADAN_USAHA' => [
        'label' => 'corporate'
    ]
]);

$container->setParameter('business_criteria', [
    'USAHA_MIKRO' => [
        'label' => 'micro_business',
        'display' => 'Usaha Mikro',
        'description' => 'Modal usaha maksimal 1 milyar rupiah atau penjualan tahunan paling banyak 2 milyar rupiah'
    ],
    'USAHA_KECIL' => [
        'label' => 'small_business',
        'display' => 'Usaha Kecil',
        'description' => 'Modal usaha lebih dari 1 milyar rupiah - 5 milyar rupiah atau penjualan tahunan lebih dari 2 milyar rupiah - 15 milyar rupiah'
    ],
    'USAHA_MENENGAH' => [
        'label' => 'medium_business',
        'display' => 'Usaha Menengah',
        'description' => 'Modal usaha lebih dari 5 milyar rupiah - 10 milyar rupiah atau penjualan tahunan lebih dari 15 milyar rupiah - 50 milyar rupiah'
    ],
    'USAHA_BESAR' => [
        'label' => 'big_business',
        'display' => 'Usaha Besar',
        'description' => 'Modal usaha lebih dari 10 milyar atau penjualan tahunan lebih dari 50 milyar rupiah'
    ]
]);

$container->setParameter('store_status', [
    'NEW_MERCHANT' => 'NEW_MERCHANT',
    'VERIFIED' => 'VERIFIED',
    'ACTIVE' => 'ACTIVE',
    'UPDATE' => 'UPDATE',
    'DRAFT' => 'DRAFT',
    'PENDING' => 'PENDING',
    'INACTIVE' => 'INACTIVE'
]);

$container->setParameter('position', [
    'DIREKTUR' => [
        'label' => 'director',
    ],
    'PEMILIK' => [
        'label' => 'owner'
    ]
]);

$container->setParameter('b2gLimitAmountForNonPkpStore', 2000000);
$container->setParameter('b2gLimitAmountForPkpStore', 200000000);
$container->setParameter('additionalDocumentsTransaction', 50000000);
$container->setParameter('dokuMinimumTransactionAmount', 10000);
$container->setParameter('midtrans_client_key', '%env(MIDTRANS_CLIENT_KEY)%');
$container->setParameter('midtrans_script_url', '%env(MIDTRANS_SCRIPT_URL)%');
$container->setParameter('is_midtrans_enable', false);
$container->setParameter('disbursement_statuses', [
    'pending' => 'Pending',
    'processed' => 'Processed',
    'done' => 'Done'
]);
$container->setParameter('pdn_options', [
    'pdn', 'non_pdn', 'lokal'
]);
$container->setParameter('admin_merchant_roles', [
    'ROLE_ADMIN_MERCHANT_OWNER',
    'ROLE_ADMIN_MERCHANT_PRODUCT',
    'ROLE_ADMIN_MERCHANT_ORDER',
    'ROLE_ADMIN_MERCHANT_CUSTOMER',
    'ROLE_ADMIN_MERCHANT_CHAT',
]);

$container->setParameter('umkm_categories', [
    'usaha_mikro' => [
        'label' => 'usaha_mikro',
        'display' => 'Usaha Mikro',
        'description' => 'Modal usaha maksimal 1 milyar rupiah atau penjualan tahunan paling banyak 2 milyar rupiah'
    ],
    'usaha_kecil' => [
        'label' => 'usaha_kecil',
        'display' => 'Usaha Kecil',
        'description' => 'Modal usaha lebih dari 1 milyar rupiah - 5 milyar rupiah atau penjualan tahunan lebih dari 2 milyar rupiah - 15 milyar rupiah'
    ],
    'usaha_menengah' => [
        'label' => 'usaha_menengah',
        'display' => 'Usaha Menengah',
        'description' => 'Modal usaha lebih dari 5 milyar rupiah - 10 milyar rupiah atau penjualan tahunan lebih dari 15 milyar rupiah - 50 milyar rupiah'
    ],
    'usaha_besar' => [
        'label' => 'usaha_besar',
        'display' => 'Usaha Besar',
        'description' => 'Modal usaha lebih dari 10 milyar atau penjualan tahunan lebih dari 50 milyar rupiah'
    ]
]);

$container->setParameter('role_pp_amount_without_approval', 50000000);
$container->setParameter('role_pp_max_amount_with_approval', 200000000);
$container->setParameter('free_tax_for_category', [148, 142, 6]);
$container->setParameter('ppk_type_options', ['ppk', 'budget_user', 'proxy_budget_user']);
$container->setParameter('treasurer_type_options', ['general_treasurer', 'expenditure_treasurer', 'assistant_treasurer', 'ppk_support']);
$container->setParameter('status_change_filter_option', [
    'desc' => 'Terbaru diubah',
    'asc' => 'Terlama diubah'
]);
$container->setParameter('ppk_method_options', [
    'pembayaran_langsung' => 'Pembayaran Langsung (LS)',
    'uang_persediaan' => 'Uang Persediaan (UP)',
]);

$container->setParameter('shipped_method_options', [
    'normal_shipped' => 'Kurir',
    'self_courier' => 'Mandiri',
]);

$container->setParameter('data_courier', [
    'jne' => [
        'address' => 'Jl. Tomang Raya No. 11 Jakarta',
        'npwp'    => '01.539.710.2-036.000',
    ],
    'pos' => [
        'address' => 'Jl Gedung Kesenian No.2 Jakarta Pusat 10710 - Jakarta',
        'npwp'    => '01.001.620.2-051.000',
    ],
]);

$container->setParameter('ket_order_statuses', [
    'paid' => [
        'seller' => 'Mohon menunggu proses disbursment',
        'buyer'  => 'Terima kasih telah bertransaksi melalui Bmall.id'
    ],
    'payment_process' => [
        'seller' => 'Mohon menunggu proses verifikasi pembayaran',
        'buyer'  => 'Mohon menunggu proses verifikasi pembayaran'
    ],
    'processed' => [
        'seller' => 'Mohon melakukan proses persiapan pengiriman dengan memperhatikan prosedur pengiriman pada sistem, melakukan proses pengiriman dan mengiputkan data pengiriman sesuai pilihan yang telah disediakan',
        'buyer'  => 'Mohon menunggu proses persiapan pengiriman produk dari penjual'
    ],
    'shipped' => [
        'seller' => 'Mohon menunggu proses penerimaan produk dari pembeli',
        'buyer'  => 'Mohon melakukan proses penerimaan produk dengan mengkonfirmasi tanda terima yang telah diterbitkan'
    ],
    'received' =>
    [
        'seller' => 'Mohon menunggu proses pembayaran dari pembeli',
        'buyer'  => 'Mohon melanjutkan proses pembayaran dengan klik lanjut pembayaran. Upload bukti pembayaran untuk metode transfer'
    ],
    'cancel' => [
        'seller' => 'Alasan pembatalan :',
        'buyer'  => 'Alasan pembatalan :'
    ],
    'cancel_request' => [
        'seller' => 'Alasan pembatalan :',
        'buyer'  => 'Alasan pembatalan :'
    ],
    'disbursement_process' => [
        'seller' => 'Mohon menunggu dana masuk ke rekening yang didaftarkan',
        'buyer'  => '-'
    ],
    'disbursement_done' => [
        'seller' => 'Terima kasih telah bertransaksi melalui Bmall.id',
        'buyer'  => ''
    ],
    'nego_on_buyer' => [
        'seller' => 'Mohon menunggu tanggapan dari pembeli',
        'buyer'  => 'Mohon menanggapi negosiasi yang diajukan pembeli'
    ],
    'nego_on_buyer_approve_seller' => [
        'seller' => 'Mohon menunggu tanggapan dari pembeli',
        'buyer'  => 'Mohon menanggapi dengan klik setuju/ tidak setuju negosiasi yang diajukan penjual'
    ],
    'nego_on_seller' => [
        'seller' => 'Mohon menanggapi negosiasi yang diajukan pembeli',
        'buyer'  => 'Mohon menunggu tanggapan dari penjual'
    ],
    'disbursement_processed' => [
        'seller' => 'Mohon menunggu dana masuk ke rekening yang didaftarkan',
        'buyer'  => 'Terima kasih telah bertransaksi melalui Bmall.id'
    ],
    'disbursement_done' => [
        'seller' => 'Terima kasih telah bertransaksi melalui Bmall.id',
        'buyer'  => 'Terima kasih telah bertransaksi melalui Bmall.id'
    ],
    'approve_order_ppk' => [
        'seller' => '',
        'buyer'  => ''
    ],
    'pending' => [
        'seller' => '',
        'buyer'  => ''
    ],
    'pending_payment' => [
        'seller' => '',
        'buyer'  => ''
    ],
    'pending_approve' => [
        'seller' => '',
        'buyer'  => ''
    ],
    'new_order' => [
        'seller' => '',
        'buyer'  => ''
    ],
    'confirmed' => [
        'seller' => '',
        'buyer'  => ''
    ],
    'tax_invoice' => [
        'seller' => '',
        'buyer'  => ''
    ],
    'document' => [
        'seller' => '',
        'buyer'  => ''
    ],
    'partial_delivery' => [
        'seller' => '',
        'buyer'  => ''
    ],
    'confirm_order_ppk' => [
        'seller' => '',
        'buyer'  => ''
    ],
    'approved_order' => [
        'seller' => '',
        'buyer'  => ''
    ],
]);

$container->setParameter('bank_method_options', [
    'bank_transfer' => 'Transfer',
    'virtual_account' => 'Virtual Account',
]);

$container->setParameter('indonesian_days_name', [
    'Sun' => 'Minggu',
    'Mon' => 'Senin',
    'Tue' => 'Selasa',
    'Wed' => 'Rabu',
    'Thu' => 'Kamis',
    'Fri' => 'Jumat',
    'Sat' => 'Sabtu',
]);

$container->setParameter('indonesian_months_name', [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
]);

$container->setParameter('tax_payment_types', [
    58 => [
        'label' => 'PMK Nomor 58/PMK.03/2022',
        'description' => 'Tentang penunjukan pihak lain sebagai pemungut pajak dan tata cara pemungutan, penyetoran dan/atau pelaporan pajak yang dipungut oleh Pihak Lain atas transaksi pengadaan barang dan/atau jasa melalui Sistem Informasi Pengadaan Pemerintah.',
    ],
    59 => [
        'label' => 'PMK Nomor 59/PMK.03/2022',
        'description' => 'Tentang perubahan atas Peraturan Meteri Keuangan nomor : 231/PMK.03/2019 tentang tata cara pendaftaran dan penghapusan Nomor Pokok Wajib Pajak, pengukuhan dan pencabutan Pengukuhan Pengusaha Kena Pajak, serta pemotongan dan/atau pemungutan, penyetoran dan pelaporan pajak bagi instansi Pemerintah.',
    ],
]);

$container->setParameter('pph_choose_options', [
    '0.5' => 'PPh (0,5%)',
    '1.5' => 'PPh Pasal 22 (1,5%)',
    '2' => 'PPh Pasal 23 (2%)',
    'lainnya' => 'Lainnya',
]);

$container->setParameter('ppn_choose_options', [
    '11' => 'PPN',
    // '10' => 'PHR (10%)',
    // 'lainnya' => 'Lainnya',
]);

$container->setParameter('status_utama', [
    'new_order' => 'label.new_order',
    'confirmed' => 'label.confirmed',
    'processed' => 'label.processed',
    'shipped' => 'label.shipped',
    'received' => 'label.received',
    'pending_payment' => 'label.pending_payment',
    'paid' => 'label.paid',
]);

$container->setParameter('djp_send_status', [
    'djp_report_not_send' => 'Belum terkirim',
    'djp_report_sent' => 'Berhasil terkirim',
    'djp_report_failed' => 'Gagal terkirim',
]);

$container->setParameter('status_icon', [
    'new_order' => '<i class="far fa-bell" style="color: #dc3545"></i>',
    'confirmed' => '<i class="far fa-thumbs-up" style="color: #198754"></i>',
    'processed' => '<i class="fas fa-box" style="color: #212529"></i>',
    'shipped' => '<i class="fas fa-truck" style="color: #0d6efd"></i>',
    'received' => '<i class="fas fa-archive" style="color: #fd7e14"></i>',
    'pending_approve' => '<i class="fas fa-clock" style="color: #0dcaf0"></i>',
    'document' => '<i class="fas fa-file" style="color: #ffc107"></i>',
    'tax_invoice' => '<i class="fas fa-dollar-sign" style="color: #6f42c1"></i>',
    'pending_payment' => '<i class="fas fa-money-bill" style="color: #fd7e14"></i>',
    'payment_process' => '<i class="fas fa-money-bill" style="color: #0d6efd"></i>',
    'paid' => '<i class="fas fa-money-bill" style="color: #198754"></i>',
    'cancel_request' => '<i class="fas fa-ban" style="color: #fd7e14"></i>',
    'cancel' => '<i class="fas fa-ban" style="color: #dc3545"></i>',
]);

$container->setParameter('source_of_fund_options', [
    'APBD' => 'APBD',
    'APBN' => 'APBN',
    'APBDP' => 'APBDP',
    'APBNP' => 'APBNP',
    'BLU' => 'BLU',
    'BLUD' => 'BLUD',
    'LAINNYA' => 'Lainnya',
]);

$container->setParameter('ppk_step_status', [
    'new_order' => [
        'img' => 'order.png',
        'user' => 'PP',
    ],
    'confirmed' => [
        'img' => 'chat.png',
        'user' => 'PP',
    ],
    'confirm_order_ppk' => [
        'img' => 'deal.png',
        'user' => 'PPK',
    ],
    'processed' => [
        'img' => 'box.png',
        'user' => 'MERCHANT',
    ],
    'shipped' => [
        'img' => 'fast-delivery.png',
        'user' => 'MERCHANT',
    ],
    'received' => [
        'img' => 'receiver.png',
        'user' => 'PPK',
    ],
    'pending_payment' => [
        'img' => 'money.png',
        'user' => 'TREASURER',
    ],
    'paid' => [
        'img' => 'check.png',
        'user' => 'TREASURER',
    ],
]);

$container->setParameter('label_log_order', [
    'status' => [
        'label' => 'Status',
        'value' => [
            'new_order' => 'label.new_order',
            'pending' => 'label.pending',
            'pending_payment' => 'label.pending_payment',
            'approve_order_ppk' => 'label.approve_order_ppk',
            'pending_approve' => 'label.pending_approve',
            'paid' => 'label.paid',
            'payment_process' => 'label.payment_process',
            'confirmed' => 'label.confirmed',
            'processed' => 'label.processed',
            'shipped' => 'label.shipped',
            'received' => 'label.received',
            'cancel' => 'label.cancelled',
            'cancel_request' => 'label.cancel_request',
            'tax_invoice' => 'label.tax_invoice',
            'document' => 'label.document',
            'partial_delivery' => 'label.partial_delivery',
        ],
    ],
    'negotiationStatus' => [
        'label' => 'Status Negosiasi',
        'value' => [
            'pending' => 'Belum Selesai',
            'finish' => 'Selesai',
        ],
    ],
    'shipped_method' => [
        'label' => 'Metode Pengiriman',
        'value' => [
            'normal_shipped' => 'Kurir',
            'self_courier' => 'Mandiri',
        ],
    ],
    'self_courier_name' => [
        'label' => 'Nama Penerima',
    ],
    'self_courier_telp' => [
        'label' => 'No. Telp Penerima',
    ],
    'self_courier_position' => [
        'label' => 'Jabatan Penerima',
    ],
    'trackingCode' => [
        'label' => 'No. Resi',
    ],
    'state_img' => [
        'label' => 'Foto Resi',
    ],
    'statusRating' => [
        'label' => 'Status Rating',
        'value' => [
            'all' => 'Semua sudah dirating',
        ],
    ],
    'ppk_payment_method' => [
        'label' => 'Metode Pembayaran',
        'value' => [
            'pembayaran_langsung' => 'Pembayaran Langsung (LS)',
            'uang_persediaan' => 'Uang Persediaan (UP)',
        ],
    ],
    'treasurer_pph' => [
        'label' => 'PPh',
    ],
    'treasurerPphNominal' => [
        'label' => 'Nominal PPh',
    ],
    'treasurer_ppn' => [
        'label' => 'PPN',
    ],
    'treasurer_ppn_nominal' => [
        'label' => 'Nominal PPN',
    ],
    'other_document' => [
        'label' => 'Dokumen Lainnya',
    ],
    'other_document_name' => [
        'label' => 'Nama Dokumen Lainnya',
    ],
    'taxInvoiceFile' => [
        'label' => 'Faktur Pajak',
    ],
    'withholdingTaxSlipFile' => [
        'label' => 'Bukti Potong',
    ],
]);

$container->setParameter('limit_trx_50', 50000000);

$container->setParameter('sftp_host', $_ENV['SFTP_HOST']);
$container->setParameter('sftp_username', $_ENV['SFTP_USERNAME']);
$container->setParameter('sftp_password', $_ENV['SFTP_PASSWORD']);
