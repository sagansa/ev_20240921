<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App Config (force update & build usage tracking)
    |--------------------------------------------------------------------------
    |
    | Nilai disajikan via GET /api/v1/app/config (publik) untuk klien mobile.
    |
    | - APP_MIN_VERSION_CODE: build di bawah kode ini wajib update
    |   (ForceUpdateScreen blocking di Android). Default 1 = tidak pernah blokir.
    | - APP_LATEST_VERSION_NAME: versi terbaru (label di layar update).
    | - APP_UPDATE_MESSAGE: pesan singkat yang ditampilkan di layar update.
    | - APP_UPDATE_URL: tujuan tombol update (fallback client: market://).
    | - APP_TRACK_BUILD_USAGE: kirim ping tester tiap app start. Matikan
    |   (false) saat production supaya tidak ada pengiriman ping build.
    | - APP_LATEST_STORE_VERSION_CODE: versionCode build Closed Testing yang
    |   sudah LIVE di Play Store. Klien build IAS membandingkannya dengan
    |   versionCode sendiri; bila lebih besar → dorong user ke Closed Testing.
    |   Default 0 = belum ada build store yang lebih baru. JANGAN dinaikkan
    |   sebelum release Closed Testing disetujui/live, agar Play Store
    |   benar-benar menyediakan build itu saat user diarahkan.
    | - APP_MIN_VERSION_CODE_IOS: build iOS di bawah kode ini wajib update.
    |   Terpisah dari Android supaya hotfix Android tak sengaja memblokir iOS.
    |   Default 0 = tidak pernah blokir.
    | - APP_UPDATE_URL_IOS: tujuan tombol update iOS (App Store). Kosong =
    |   tombol update disembunyikan (hanya pesan + Coba Lagi).
    |
    */

    'min_version_code' => (int) env('APP_MIN_VERSION_CODE', 1),

    'latest_version_name' => (string) env('APP_LATEST_VERSION_NAME', ''),

    'update_message' => (string) env('APP_UPDATE_MESSAGE', ''),

    'update_url' => (string) env('APP_UPDATE_URL', ''),

    'track_build_usage' => (bool) env('APP_TRACK_BUILD_USAGE', true),

    'latest_store_version_code' => (int) env('APP_LATEST_STORE_VERSION_CODE', 0),

    'min_version_code_ios' => (int) env('APP_MIN_VERSION_CODE_IOS', 0),

    'update_url_ios' => (string) env('APP_UPDATE_URL_IOS', ''),

];
