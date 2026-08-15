<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    |
    | Prefer HTTP v1 (service account JSON). Legacy server key is a fallback
    | for older Firebase projects still using the deprecated FCM API.
    |
    */

    'project_id' => env('FCM_PROJECT_ID', ''),

    'server_key' => env('FCM_SERVER_KEY', ''),

    /*
    | Absolute path OR storage-relative path to the Firebase service account JSON.
    | Example: storage/app/firebase/service-account.json
    */
    'credentials' => env('FCM_CREDENTIALS', storage_path('app/firebase/service-account.json')),

    'timeout' => 15,

];
