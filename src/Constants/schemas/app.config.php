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
    'base_url'                     => Expect::string('auto'),
    'charset'                      => Expect::string('UTF-8'),
    'environment'                  => Expect::string('auto'),
    'language'                     => Expect::string('en'),
    'force_global_secure_requests' => Expect::bool(false),
    'url_suffix'                   => Expect::string(''),
    'use_absolute_link'            => Expect::bool(true),
    'negotiate_locale'             => Expect::bool(true),
    'supported_locales'            => Expect::listOf('string')->default([]),
    'timezone'                     => Expect::anyOf(...DateTimeZone::listIdentifiers())->default('UTC'),
    'index_page'                   => Expect::string(''),
])->otherItems();
