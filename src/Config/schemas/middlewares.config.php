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
    'aliases' => Expect::arrayOf('string', 'string'),
    'globals' => Expect::listOf(Expect::type('string|closure')),
    'groups'  => Expect::arrayOf('array', 'string'),
    'build'   => Expect::type('closure'),
])->otherItems();
