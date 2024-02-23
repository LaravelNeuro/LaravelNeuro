<?php

return [
        'lneuro' => [
            'driver' => 'local',
            'root' => storage_path('app/LaravelNeuro'),
            'throw' => false,
        ],
        'lneuro_app' => [
            'driver' => 'local',
            'root' => app_path(),
            'throw' => false,
        ],
];
