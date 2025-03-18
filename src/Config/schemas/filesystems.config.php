<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Nette\Schema\Expect;

return Expect::structure([
    'default' => Expect::string(env('FILESYSTEM_DISK', 'local')),
    'disks'   => Expect::structure([
        'local' => Expect::structure([
            'driver' => Expect::string('local'),
            'root'   => Expect::string(storage_path('app/private')),
            'throw'  => Expect::bool(false),
        ])->otherItems(),

        'public' => Expect::structure([
            'driver'     => Expect::string('local'),
            'root'       => Expect::string(storage_path('app/public')),
            'url'        => Expect::string(config('app.base_url') . '/storage'),
            'visibility' => Expect::anyOf('public', 'private')->default('public'),
            'throw'      => Expect::bool(false),
        ])->otherItems(),

        's3' => Expect::structure([
            'driver'                  => Expect::string('s3'),
            'key'                     => Expect::string(env('AWS_ACCESS_KEY_ID'))->nullable(),
            'secret'                  => Expect::string(env('AWS_SECRET_ACCESS_KEY'))->nullable(),
            'region'                  => Expect::string(env('AWS_DEFAULT_REGION'))->nullable(),
            'bucket'                  => Expect::string(env('AWS_BUCKET'))->nullable(),
            'url'                     => Expect::string(env('AWS_URL'))->nullable(),
            'endpoint'                => Expect::string(env('AWS_ENDPOINT'))->nullable(),
            'use_path_style_endpoint' => Expect::bool(env('AWS_USE_PATH_STYLE_ENDPOINT', false)),
            'throw'                   => Expect::bool(false),
        ])->otherItems(),
    ])->otherItems(),
])->otherItems();
