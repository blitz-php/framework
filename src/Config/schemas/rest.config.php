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
    'locale'      => Expect::string('en'),
    'force_https' => Expect::bool()->default(false),
    'format'      => Expect::string()->default('json'),
    'strict'      => Expect::bool()->default(true),
    'field'       => Expect::arrayOf('string', 'string')->default([
        'status'  => 'status',
        'message' => 'message',
        'code'    => 'code',
        'errors'  => 'errors',
        'result'  => 'result',
    ]),
    'ip_blacklist' => Expect::listOf('string'),
    'ip_whitelist' => Expect::listOf('string'),
    'ajax_only'    => Expect::bool()->default(false),
])->otherItems();
