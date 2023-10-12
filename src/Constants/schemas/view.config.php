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
    'active_adapter'  => Expect::string()->default('native'),
    'compress_output' => Expect::type('bool|closure|string')->default('auto'),
    'view_base'       => Expect::string()->default(VIEW_PATH),
    'debug'           => Expect::anyOf(true, false, 'auto')->default('auto'),
    'shared'          => Expect::type('closure')->required()->default(static fn () => []),
    'decorators'      => Expect::listOf('string')->required()->default([]),
    'adapters'        => Expect::arrayOf('array', 'string')->required(),
])->otherItems();
