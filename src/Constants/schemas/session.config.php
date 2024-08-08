<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Session\Handlers\File;
use Nette\Schema\Expect;

return Expect::structure([
    'handler'            => Expect::string()->default(File::class),
    'cookie_name'        => Expect::string()->default('blitz_session'),
    'expiration'         => Expect::int()->default(7200),
    'savePath'           => Expect::string()->default(FRAMEWORK_STORAGE_PATH . 'session'),
    'matchIP'            => Expect::bool()->default(false),
    'time_to_update'     => Expect::int()->default(300),
    'regenerate_destroy' => Expect::bool()->default(true),
    'group'              => Expect::string()->nullable(),
]);
