<?php

return [

    'google' => [
        // Web client ID (server-side). Prefer GOOGLE_WEB_CLIENT_ID; fall back to GOOGLE_CLIENT_ID.
        'client_id' => env('GOOGLE_WEB_CLIENT_ID', env('GOOGLE_CLIENT_ID')),
        // Per-platform client IDs — accepted as alternative ID-token audiences so mobile
        // clients (which issue tokens with their own client ID) pass verification.
        'android_client_id' => env('GOOGLE_ANDROID_CLIENT_ID'),
        'ios_client_id' => env('GOOGLE_IOS_CLIENT_ID'),
    ],

    'apple' => [
        'service_id' => env('APPLE_SERVICE_ID'),
    ],

];
