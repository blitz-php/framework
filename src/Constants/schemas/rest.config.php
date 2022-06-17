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
    'force_https'     => Expect::bool()->default(false),
    'allowed_methods' => Expect::listOf('string')->default(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
    'format'          => Expect::string()->default('json'),
    'strict'          => Expect::bool()->default(true),
    'fields'          => Expect::arrayOf('string', 'string'),
    'ip_blacklist'    => Expect::listOf('string'),
    'ip_whitelist'    => Expect::listOf('string'),
    'ajax_only'       => Expect::bool()->default(false),
    'auth '           => Expect::anyOf('jwt', 'session')->default('jwt'),
    'jwt'             => Expect::arrayOf('mixed', 'string'),
])->otherItems();
