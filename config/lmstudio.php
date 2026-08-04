<?php

/**
 * Konfigurasi LM Studio — LLM lokal (OpenAI-compatible) untuk klasifikasi
 * kandidat matching PLN ↔ ESDM yang ambiguous (stage 2 pipeline matching).
 *
 * LM Studio: https://lmstudio.ai. Jalankan server lokal dengan model ter-load
 * (mis. qwen2.5-7b-instruct); server expose endpoint OpenAI-compatible di
 * http://localhost:1234/v1 secara default.
 *
 * Bila LM Studio mati / disabled, PlnEsdmMatchService otomatis fallback ke
 * penilaian geo+nama (kandidat jadi `pending` utk review admin) — tidak crash.
 */
return [

    // Base URL OpenAI-compatible LM Studio.
    'base_url' => env('LMSTUDIO_BASE_URL', 'http://localhost:1234/v1'),

    // API key dummy — LM Studio menerima nilai apa pun.
    'api_key' => env('LMSTUDIO_API_KEY', 'lm-studio'),

    // Nama model yang dimuat di LM Studio (cek "My Models" di UI).
    'model' => env('LMSTUDIO_MODEL', 'local-model'),

    // Timeout request klasifikasi (detik) — model besar bisa lambat.
    'timeout' => (int) env('LMSTUDIO_TIMEOUT', 120),

    // Matikan seluruh integrasi LM Studio (fallback murni geo+nama).
    'enabled' => filter_var(env('LMSTUDIO_ENABLED', true), FILTER_VALIDATE_BOOL),
];
