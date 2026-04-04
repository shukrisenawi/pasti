<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'n8n' => [
        'api_token' => env('N8N_API_TOKEN'),
        'webhook_url' => env('N8N_WEBHOOK_URL', 'https://n8n-mt8umikivytz.n8x.biz.id/webhook/3caf9b20-d664-491b-81db-57984d626341'),
        'webhook_url_test' => env('N8N_WEBHOOK_URL_TEST', 'https://n8n-mt8umikivytz.n8x.biz.id/webhook-test/3caf9b20-d664-491b-81db-57984d626341'),
        'webhook_url_production' => env('N8N_WEBHOOK_URL_PRODUCTION', env('N8N_WEBHOOK_URL', 'https://n8n-mt8umikivytz.n8x.biz.id/webhook/3caf9b20-d664-491b-81db-57984d626341')),
        'webhook_url_group2' => env('N8N_WEBHOOK_URL_GROUP2', 'https://n8n-mt8umikivytz.n8x.biz.id/webhook-test/3caf9b20-d664-491b-81db-57984d626341'),
        'webhook_url_group2_test' => env('N8N_WEBHOOK_URL_GROUP2_TEST', env('N8N_WEBHOOK_URL_GROUP2', 'https://n8n-mt8umikivytz.n8x.biz.id/webhook-test/3caf9b20-d664-491b-81db-57984d626341')),
        'webhook_url_group2_production' => env('N8N_WEBHOOK_URL_GROUP2_PRODUCTION', env('N8N_WEBHOOK_URL_GROUP2', 'https://n8n-mt8umikivytz.n8x.biz.id/webhook-test/3caf9b20-d664-491b-81db-57984d626341')),
        'public_domain' => env('N8N_PUBLIC_DOMAIN', 'https://pastikawasansik.my.id'),
    ],

];
