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
    'driver' => Expect::anyOf('bcrypt', 'argon', 'argon2id')->default('bcrypt'),
    'bcrypt' => Expect::structure([
        'rounds' => Expect::int()->default(12),
        'verify' => Expect::bool()->default(true),
    ]),
    'argon' => Expect::structure([
        'memory'  => Expect::int()->default(65536),
        'threads' => Expect::int()->default(1),
        'time'    => Expect::int()->default(4),
        'verify'  => Expect::bool()->default(true),
    ]),
])->otherItems();
