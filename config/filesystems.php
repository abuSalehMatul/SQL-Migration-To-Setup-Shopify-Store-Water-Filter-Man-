<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'image' => [
            'driver' => 'local',
            'root' => storage_path('app/image'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'collection' => [
            'driver' => 'local',
            'root' => storage_path('app/collection'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'connect' => [
            'driver' => 'local',
            'root' => storage_path('app/connect'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'customer' => [
            'driver' => 'local',
            'root' => storage_path('app/customer'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'monthCustomVariant' => [
            'driver' => 'local',
            'root' => storage_path('app/monthCustomVariant'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'customVariant' => [
            'driver' => 'local',
            'root' => storage_path('app/customVariant'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'monthBasedProduct' => [
            'driver' => 'local',
            'root' => storage_path('app/monthBasedProduct'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
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
