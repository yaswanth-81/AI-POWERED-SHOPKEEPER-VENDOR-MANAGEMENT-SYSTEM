<?php
// config.php
// Centralized configuration for AI providers

return [
    'providers' => [
        'order' => ['groq', 'gemini', 'openai'],

        'gemini' => [
            'enabled' => true,
            'api_key' => getenv('GEMINI_API_KEY') ?: '',
            'models' => [
                'gemini-1.5-flash',
                'gemini-1.5-pro'
            ],
            'api_base' => 'https://generativelanguage.googleapis.com',
            'api_version' => 'v1beta'
        ],

        'openai' => [
            'enabled' => false,
            'api_key' => getenv('OPENAI_API_KEY') ?: '',
            'model' => 'gpt-4o-mini',
            'api_base' => 'https://api.openai.com',
            'api_version' => 'v1'
        ],

        'groq' => [
            'enabled' => true,
            'api_key' => getenv('GROQ_API_KEY') ?: '',
            'model' => 'llama-3.1-8b-instant',
            'api_base' => 'https://api.groq.com',
            'api_version' => 'openai/v1'
        ]
    ]
];
