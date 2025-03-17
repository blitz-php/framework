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
    'log'             => Expect::bool()->default(true),
    'ignore_codes'    => Expect::listOf('int')->default([404]),
    'error_view_path' => Expect::string()->default(VIEW_PATH . 'errors'),
    'title'           => Expect::string()->default('Oups ! Il y avait une erreur.'),
    'editor'          => Expect::type('string|closure')->default('vscode'),
    'blacklist'       => Expect::listOf('string')->default([]),
    'data'            => Expect::arrayOf(Expect::type('array|closure'), 'string')->default([]),
    'handlers'        => Expect::listOf('string')->default([]),
])->otherItems();
