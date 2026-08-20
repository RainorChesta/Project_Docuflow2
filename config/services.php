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

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'temperature' => env('GROQ_TEMPERATURE', 0.2),
        'max_tokens' => env('GROQ_MAX_TOKENS', 4096),
        'timeout' => env('GROQ_TIMEOUT', 90),
    ],

    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-v4-flash'),
        'temperature' => env('DEEPSEEK_TEMPERATURE', 0.2),
        'max_tokens' => env('DEEPSEEK_MAX_TOKENS', 4096),
        'timeout' => env('DEEPSEEK_TIMEOUT', 90),
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'), // native ollama API /api/generate
        'model' => env('OLLAMA_MODEL', 'qwen2.5:1.5b'),
        'temperature' => env('OLLAMA_TEMPERATURE', 0.2),
        'max_tokens' => env('OLLAMA_MAX_TOKENS', 1500),
        'timeout' => env('OLLAMA_TIMEOUT', 120),
    ],

];
