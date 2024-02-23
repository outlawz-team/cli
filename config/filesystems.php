<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used.
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => getcwd()
        ],
        'internal' => [
            'driver' => 'local',
            'root' => (Phar::running(false)) ? Phar::running() . DIRECTORY_SEPARATOR : dirname(app_path())
        ]
    ]
];