<?php

return [
    'default_model' => env('LARAVELAI_DEFAULT_MODEL', ''),
    'default_api' => env('LARAVELAI_DEFAULT_API', ''),
    'models' => [
        'DeepSeekCoder' => [
            'model' => 'deepseek-coder',
            'api' => '',
        ],
        'Zephyr' => [
            'model' => 'zephyr',
            'api' => '',
        ],
    ],
];