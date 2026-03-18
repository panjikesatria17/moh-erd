<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'visibility' => env('FILESYSTEM_LOCAL_VISIBILITY', 'private'),
            'directory_visibility' => env('FILESYSTEM_LOCAL_DIRECTORY_VISIBILITY', 'private'),
            'permissions' => [
                'file' => [
                    'public' => intval((string) env('FILESYSTEM_FILE_PUBLIC_PERMISSION', '0644'), 8),
                    'private' => intval((string) env('FILESYSTEM_FILE_PRIVATE_PERMISSION', '0600'), 8),
                ],
                'dir' => [
                    'public' => intval((string) env('FILESYSTEM_DIR_PUBLIC_PERMISSION', '0755'), 8),
                    'private' => intval((string) env('FILESYSTEM_DIR_PRIVATE_PERMISSION', '0700'), 8),
                ],
            ],
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'directory_visibility' => 'public',
            'permissions' => [
                'file' => [
                    'public' => intval((string) env('FILESYSTEM_FILE_PUBLIC_PERMISSION', '0644'), 8),
                    'private' => intval((string) env('FILESYSTEM_FILE_PRIVATE_PERMISSION', '0600'), 8),
                ],
                'dir' => [
                    'public' => intval((string) env('FILESYSTEM_DIR_PUBLIC_PERMISSION', '0755'), 8),
                    'private' => intval((string) env('FILESYSTEM_DIR_PRIVATE_PERMISSION', '0700'), 8),
                ],
            ],
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => env('FILESYSTEM_S3_VISIBILITY', 'private'),
            'directory_visibility' => env('FILESYSTEM_S3_DIRECTORY_VISIBILITY', 'private'),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
