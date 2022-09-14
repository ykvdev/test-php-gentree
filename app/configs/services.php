<?php

return [
    'services' => [
        'console_io' => [
            'logs_path' => __DIR__ . '/../../data/logs/console-{cmd}.log',
        ],

        'whoops' => [
            'editor' => 'phpstorm',
        ],
    ],
];