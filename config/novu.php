<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Novu — notification infrastructure (novu.co)
    |--------------------------------------------------------------------------
    | One trigger fans a notice out to every channel the dashboard workflow
    | has: the in-app Inbox feed (bell in the header), and browser push once
    | the workflow's Push step exists and NOVU_PUSH_ENABLED=true.
    |
    | Nothing here is required for the app to run: with the keys empty every
    | Novu call is skipped and the system behaves exactly as before.
    */

    // Secret REST key from the Novu dashboard: Settings → API Keys.
    'api_key' => env('NOVU_API_KEY'),

    // Public application identifier from the same page (per environment —
    // Dev and Production each have their own).
    'app_id' => env('NOVU_APP_ID'),

    // Optional HMAC subscriber identity (dashboard → Settings → Security).
    // When set, every page load proves who the subscriber is server-side;
    // leave empty and the Inbox works without the hash.
    'hmac_secret' => env('NOVU_HMAC_SECRET'),

    // Trigger identifier of the shared workflow (dashboard → Workflows →
    // the workflow's "Trigger" tab). The body/subject templates should read
    // {{payload.title}} / {{payload.body}}; link targets {{payload.link}}.
    'workflow_notice' => env('NOVU_WORKFLOW_NOTICE', 'aquilala-important-notice'),

    // false → the original VAPID web push keeps sending (transition phase:
    // Novu covers the in-app feed only). true  → the workflow's Push step
    // delivers outside-the-app and the VAPID sender retires, so members
    // never receive the same notice twice.
    'push_enabled' => env('NOVU_PUSH_ENABLED', false),

    // Firebase Cloud Messaging — the provider Novu routes WEB PUSH through
    // (free Firebase project; the web config below is public by design, it
    // identifies the project and is embedded in any FCM web app).
    'firebase' => [
        'api_key'            => env('FIREBASE_API_KEY'),
        'auth_domain'        => env('FIREBASE_AUTH_DOMAIN'),
        'project_id'         => env('FIREBASE_PROJECT_ID'),
        'storage_bucket'     => env('FIREBASE_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'app_id'             => env('FIREBASE_APP_ID'),
        // Web Push certificate (VAPID) key from Firebase console:
        // Project settings → Cloud Messaging → Web configuration.
        'vapid_key'          => env('FIREBASE_VAPID_KEY'),
    ],

];
