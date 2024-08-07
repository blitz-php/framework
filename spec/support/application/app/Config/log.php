<?php

use Psr\Log\LogLevel;
/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
return [
    'name'        => 'Application',
    'date_format' => 'Y-m-d H:i:s',
    'processors'  => [],
    'handlers'    => [
        'file' => [
            'level'          => LogLevel::DEBUG,
            'extension'      => '',
            'permissions'    => 644,
            'path'           => '',
            'format'         => 'line',
            'dayly_rotation' => true,
            'max_files'      => 0,
        ],
    ],
];
