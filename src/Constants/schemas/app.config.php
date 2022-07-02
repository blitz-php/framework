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
    'base_url'                     => Expect::string()->default('auto'),
    'charset'                      => Expect::string()->default('UTF-8'),
    'environment'                  => Expect::string()->default('auto'),
    'language'                     => Expect::string()->default('en'),
    'force_global_secure_requests' => Expect::bool()->default(false),
    'url_suffix'                   => Expect::string()->default(''),
    'use_absolute_link'            => Expect::bool()->default(true),
    'negotiate_locale'             => Expect::bool()->default(true),
    'supported_locales'            => Expect::listOf('string')->default(['fr', 'en']),
    'timezone'                     => Expect::string()->default('Africa/Douala'),
    'index_page'                   => Expect::string()->default(''),

])->otherItems();
