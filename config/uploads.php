<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image upload limits (admin + API-facing forms)
    |--------------------------------------------------------------------------
    |
    | Laravel file `max` rules use kilobytes. 5120 KB = 5 MB.
    |
    */

    'image_max_mb' => 5,

    'image_max_kb' => 5120,

    'image_mimes' => ['jpeg', 'jpg', 'png', 'webp'],

    'image_accept' => 'image/jpeg,image/jpg,image/png,image/webp',

];
