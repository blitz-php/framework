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
    'key'            => Expect::string()->default(''),
    'driver'         => Expect::string()->default('OpenSSL'),
    'block_size'     => Expect::int()->default(16),
    'digest'         => Expect::string()->default('SHA512'),
    'rawData'        => Expect::bool()->default(true),
    'encryptKeyInfo' => Expect::string()->default(''),
    'authKeyInfo'    => Expect::string()->default(''),
    'cipher'         => Expect::string()->default('AES-256-CTR'),
])->otherItems();
