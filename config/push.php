<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID keys — who is allowed to ask the push service to wake a device
    |--------------------------------------------------------------------------
    | Generated once with `php artisan push:generate-keys`, then pasted into
    | .env on every environment (local + server). Push silently does nothing
    | when these are empty, so a missing key never breaks the app.
    */

    'vapid_public_key'  => env('PUSH_VAPID_PUBLIC_KEY'),

    'vapid_private_key' => env('PUSH_VAPID_PRIVATE_KEY'),

    // Contact the push service may show if delivery fails (URL or mailto).
    'vapid_subject'     => env('PUSH_VAPID_SUBJECT', 'mailto:' . env('MAIL_FROM_ADDRESS', 'admin@aquilala.local')),

];
