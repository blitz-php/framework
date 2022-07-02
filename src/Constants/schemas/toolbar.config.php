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
    'collectors'       => Expect::listOf('string')->default([]),
    'collect_var_data' => Expect::bool()->default(true),
    'max_history'      => Expect::int()->default(20),
    'view_path'        => Expect::string()->default(SYST_PATH . 'Debug' . DS . 'Toolbar' . DS . 'Views'),
    'max_queries'      => Expect::int()->default(100),
    'show_debugbar'    => Expect::bool()->default(true),

])->otherItems();
