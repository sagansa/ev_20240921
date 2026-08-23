<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Notification (tester registered email)
    |--------------------------------------------------------------------------
    |
    | Email notifikasi dikirim saat ada tester baru terdaftar (event created).
    |
    | - ADMIN_NOTIFY_EMAIL: alamat email tujuan. Kosong = tidak ada email.
    | - ADMIN_NOTIFY_ENABLED: false = nonaktifkan tanpa menghapus env.
    |
    */

    'email' => (string) env('ADMIN_NOTIFY_EMAIL', ''),

    'enabled' => (bool) env('ADMIN_NOTIFY_ENABLED', true),

];
