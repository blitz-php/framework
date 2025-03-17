<?php

return [    
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Ici, vous pouvez spécifier le disque du système de fichiers par défaut qui 
    | doit être utilisé par le framework. Le disque "local", ainsi qu'une variété 
    | de disques basés sur le cloud sont disponibles pour votre application. Stockez simplement!
    |
    */
    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Ici, vous pouvez configurer autant de "disques" de système de fichiers que 
    | vous le souhaitez, et vous pouvez même configurer plusieurs disques du même pilote. 
    | Des valeurs par défaut ont été définies pour chaque pilote à titre d'exemple des valeurs requises.
    |
    | Pilotes supportes : "local", "ftp", "sftp", "s3"
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => config('app.base_url').'/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION'),
            'bucket'                  => env('AWS_BUCKET'),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
        ],

    ],

    /**
    *--------------------------------------------------------------------------
    * Liens symbolique
    *--------------------------------------------------------------------------
    * Ici, vous pouvez configurer les liens symboliques qui seront créés lors de 
    * l'exécution de la commande Klinge `storage:link`. 
    * Les clés du tableau doivent être les emplacements des liens et les valeurs doivent être leurs cibles.
    */
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
