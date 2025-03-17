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
    'active_adapter'  => Expect::string('native'),
    'compress_output' => Expect::type('bool|closure|string')->default('auto'),
    'view_base'       => Expect::string(VIEW_PATH),
    'debug'           => Expect::anyOf(true, false, 'auto')->default('auto'),
    'shared'          => Expect::type('closure')->default(static fn () => []),
    'decorators'      => Expect::listOf('string')->default([]),
    'adapters'        => Expect::structure([
        'native' => Expect::structure([
            'extension' => Expect::string('php'),
            'save_data' => Expect::bool(true),
        ]),
    ])->otherItems(),
])->otherItems();
