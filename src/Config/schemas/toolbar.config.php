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
    'collectors'          => Expect::listOf('string')->default([]),
    'collect_var_data'    => Expect::bool(true),
    'max_history'         => Expect::int(20),
    'view_path'           => Expect::string(SYST_PATH . 'Debug' . DS . 'Toolbar' . DS . 'Views'),
    'show_debugbar'       => Expect::bool(true),
    'watched_directories' => Expect::listOf('string')->default(['app']),
    'watched_extensions'  => Expect::listOf('string')->default(['php', 'css', 'js', 'html', 'svg', 'json', 'env']),
    'disable_on_headers'  => Expect::arrayOf('mixed', 'string')->default([
        'X-Requested-With' => 'xmlhttprequest', // AJAX requests
        'HX-Request'       => 'true',           // HTMX requests
        'X-Up-Version'     => null,             // Unpoly partial requests
	]),
])->otherItems();
