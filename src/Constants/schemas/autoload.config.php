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
    'psr4'     => Expect::arrayOf('string', 'string')->default([APP_NAMESPACE => APP_PATH]),
    'classmap' => Expect::arrayOf('string', 'string'),
    'files'    => Expect::listOf('string'),
    'helpers'  => Expect::listOf('string')
]);
