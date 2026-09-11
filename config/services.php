<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    |
    | Protection anti-robots du formulaire de demande. Gratuite et sans limite.
    |
    | Retenue plutôt que reCAPTCHA parce qu'elle ne dépose AUCUN témoin : pas
    | de bannière de consentement à ajouter, ce qui préserve l'objectif Loi 25.
    | reCAPTCHA a valu des sanctions à plusieurs entreprises pour usage sans
    | consentement, et collecte au-delà de la finalité de sécurité.
    |
    | Clés à créer sur https://dash.cloudflare.com → Turnstile. Sans elles, le
    | widget ne s'affiche pas et la validation est ignorée : le formulaire
    | continue de fonctionner, protégé par le honeypot, le délai minimal et la
    | limite par IP.
    |
    */

    'turnstile' => [
        'key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET_KEY'),
    ],

];
