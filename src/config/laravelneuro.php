<?php

return [
    'default_namespace' => 'Corporations',
    'keychain' => [
        'openai' => env('OPENAI_API_KEY', ''),
        'elevenlabs' => env('ELEVENLABS_API_KEY', ''),
        'google' => env('GOOGLE_AI_API_KEY', ''),
    ],
    'models' => [
        'default' => [
            'chat' => 'gpt-3.5-turbo-0125',
            'tts' => 'tts-1',
            'image' => 'dall-e-2',
            'stt' => 'whisper-1',
        ],        
        'DeepSeekCoder' => [
            'model' => 'deepseek-coder',
            'api' => '',
        ],
        'Zephyr' => [
            'model' => 'zephyr',
            'api' => '',
        ],
        'gpt-3-5-turbo' => [
            'model' => 'gpt-3.5-turbo-0125',
            'api' => 'https://api.openai.com/v1/chat/completions',
        ],
        'dall-e-2' => [
            'model' => 'dall-e-2',
            'api' => 'https://api.openai.com/v1/images/generations',
        ],
        'gpt-image-1' => [
            'model' => 'gpt-image-1',
            'api' => 'https://api.openai.com/v1/images/generations',
        ],
        'tts-1' => [
            'model' => 'tts-1',
            'api' => 'https://api.openai.com/v1/audio/speech',
            'voice' => 'onyx',
        ],
        'whisper-1' => [
            'model' => 'whisper-1',
            'api' => 'https://api.openai.com/v1/audio/transcriptions',
        ],
        'eleven-monolingual-v1' => [
            'model' => 'eleven_monolingual_v1', 
            'voice' => '21m00Tcm4TlvDq8ikWAM', //Default: Rachel
            'api' => 'https://api.elevenlabs.io/v1/text-to-speech/{voice}',
        ],
        'gemini-pro-1-5' => [
            'model' => 'gemini-1.5-pro', 
            'api' => 'https://generativelanguage.googleapis.com/v1beta/models',
        ]
    ],
];