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
    'key'              => Expect::string(env('encryption.key', '')),
    'driver'           => Expect::anyOf('OpenSSL', 'Sodium')->default(env('encryption.driver', 'OpenSSL')),
    'block_size'       => Expect::int((int) env('encryption.blockSize', 16)),
    'digest'           => Expect::string(env('encryption.digest', 'SHA512')),
    'rawData'          => Expect::bool(true),
    'encrypt_key_info' => Expect::string(''),
    'auth_key_info'    => Expect::string(''),
    'cipher'           => Expect::string('AES-256-CTR'),
])->otherItems();
