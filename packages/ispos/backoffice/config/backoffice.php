<?php

return [
    'inertia_root_view' => 'backoffice::app',
    'pos_app_url' => env('POS_APP_URL', 'http://127.0.0.1:8003/pos'),

    'locales' => [
        'default' => env('APP_LOCALE', 'en'),
        'supported' => [
            'en' => [
                'name' => 'English',
                'native' => 'English',
                'google' => 'en',
            ],
            'zh-CN' => [
                'name' => 'Chinese (Simplified)',
                'native' => '简体中文',
                'google' => 'zh-CN',
            ],
            'fil' => [
                'name' => 'Filipino',
                'native' => 'Filipino',
                'google' => 'tl',
            ],
        ],
    ],
];
