<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Disque par défaut
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
    | Disques de système de fichiers
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
            'root'   => storage_path('app/private'),
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => config('app.base_url') . '/storage',
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
    * Disques dont les fichiers peuvent être visible
    *--------------------------------------------------------------------------
    * Ici, vous pouvez renseigner une liste de disques dont les fichiers peuvent être visible sur un navigateur.
    * Par exemple, les images d'avatar uploadées dans le disque "public" pourront être affichées dans le navigateur
    */
    'viewable' => [
		'public',
    ],
];
