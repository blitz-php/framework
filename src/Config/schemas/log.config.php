<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use Psr\Log\LogLevel;
use Nette\Schema\Expect;

return Expect::structure([
    'name'        => Expect::string(config('app.name', 'Application')),
    'date_format' => Expect::string('Y-m-d H:i:s'),
    'processors'  => Expect::listOf('string')->default(['web', 'introspection', 'hostname', 'psr']),
    'handlers'    => Expect::structure([
        'file' => Expect::structure([
            'active'         => Expect::bool(true)->nullable(),
            'level'          => Expect::string(on_prod() ? LogLevel::ERROR : LogLevel::DEBUG),
            'extension'      => Expect::string('.log'),
            'permissions'    => Expect::int(0644),
            'path'           => Expect::string(LOG_PATH),
            'format'         => Expect::anyOf('json', 'line', 'normalizer', 'scalar')->default('line'),
            'dayly_rotation' => Expect::bool(true),
            'max_files'      => Expect::int(30),
        ]),
    ])->otherItems(),
])->otherItems();
