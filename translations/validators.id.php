<?php

return [
    'user' => [
        'username_taken' => 'Nama Pengguna sudah digunakan',
        'email_taken' => 'Surel sudah digunakan',
        'email_not_valid' => 'Surel tidak valid',
        'email_valid' => 'Surel valid',
        'password_not_match' => 'Kata Sandi dan Konfirmasi Kata Sandi tidak cocok',
        'password_weak' => 'Kata Sandi anda lemah. Harus terdiri dari: 1 angka, 1 karakter spesial, dengan panjang minimal 6 karakter',
        'not_valid' => 'Bukan entitas Pengguna yang valid',
        'valid' => 'Valid',
        'disabled_account' => 'Akun Anda masih sedang diverifikasi oleh tim kami. Mohon menunggu.',
        'inactive_account' => 'Akun Anda belum diaktivasi. Kami telah mengirimkan langkah-langkah untuk mengaktivasi akun Anda melalui email. Harap periksa kotak masuk Anda.',
        'phone_numeric' => 'Tidak sesuai format. Format nomor telepon: 081x-xxxx-xxx',
        'invalid_credentials' => 'Email atau kata sandi salah. Harap periksa kembali',
    ],
    'product' => [
        'price_check' => 'Harga reseller tidak boleh lebih besar dari harga jual',
    ],
    'store' => [
        'invalid_new_owner' => 'Silahkan pilih pemilik baru yang valid!',
    ],
    'order' => [
        'select_address' => 'Silahkan pilih alamat pengiriman!',
        'select_courier' => 'Silahkan pilih kurir pengiriman!',
        'select_service' => 'Silahkan pilih jasa pengiriman!',
        'select_tax_document' => 'Silahkan pilih dokumen NPWP!',
        'accept_tnc' => 'Silahkan setujui Syarat & Ketentuan!',
        'inactive_items' => 'Beberapa item dari keranjang belanja anda tidak tersedia: %products% !',
        'invalid_vouchers' => 'Beberapa voucher dari keranjang belanja anda tidak tersedia: %vouchers% !',
        'lkpp_invalid_category' => 'Kategori produk yang anda pilih tidak tersedia: %categories% !',
        'no_stock_items' => 'Stok dari beberapa item dari keranjang belanja anda tidak tersedia: %products% !',

    ],
    'search' => [
        'query_not_empty' => 'Silahkan masukkan pencarian anda',
        'search_not_empty' => 'Kata pencarian tidak boleh kosong',
        'location_not_empty' => 'Lokasi pencarian tidak boleh kosong',
    ],
    'global' => [
        'empty_input' => 'Input kosong',
        'not_empty' => 'Nilai ini tidak boleh kosong.',
        'message_empty' => 'Pesan tidak boleh kosong.',
        'too_long' => 'Nilai ini terlalu panjang. Harus memiliki %limit% karakter atau kurang.',
        'invalid_date' => 'Nilai ini bukan merupakan tanggal yang benar.',
        'not_valid' => 'Nilai ini tidak benar.',
        'slug_taken' => 'Slug sudah digunakan',
        'reserved_names' => 'Nilai ini tidak dapat digunakan karena termasuk ke dalam nama yang dicadangkan',
        'file_not_valid' => "Harap masukan type file yang valid.",
        'proof_not_empty' => 'Bukti Pembayaran tidak boleh kosong.',
    ],
    'disbursement' => [
        'disbursement_protect_done' => "Tidak dapat mengubah status disbursement menjadi 'done' menggunakan fitur ini.",
        'allowed_change_status' => "Hanya dapat mengubah status transaksi dengan status 'done'.",
    ]
];
