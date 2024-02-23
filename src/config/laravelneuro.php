<?php

return [
    'default_namespace' => 'Corporations',
    'default_model' => env('LARAVELAI_DEFAULT_MODEL', ''),
    'default_api' => env('LARAVELAI_DEFAULT_API', ''),
    'keychain' => [
        'openai' => env('OPENAI_API_KEY', ''),
        'elevenlabs' => env('ELEVENLABS_API_KEY', ''),
    ],
    'models' => [
        'DeepSeekCoder' => [
            'model' => 'deepseek-coder',
            'api' => '',
        ],
        'Zephyr' => [
            'model' => 'zephyr',
            'api' => '',
        ],
        'gpt-3-5-turbo' => [
            'model' => 'gpt-3.5-turbo-1106',
            'api' => 'https://api.openai.com/v1/chat/completions',
        ],
        'dall-e-2' => [
            'model' => 'dall-e-2',
            'api' => 'https://api.openai.com/v1/images/generations',
        ],
        'tts-1' => [
            'model' => 'tts-1',
            'api' => 'https://api.openai.com/v1/audio/speech',
        ],
        'eleven-monolingual-v1' => [
            'model' => 'eleven_monolingual_v1', 
            'voice' => '21m00Tcm4TlvDq8ikWAM', //Default: Rachel
            'api' => 'https://api.elevenlabs.io/v1/text-to-speech/{voice}',
        ],
    ],
];