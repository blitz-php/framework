<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

return [
    'handler'            => \BlitzPHP\Session\Handlers\File::class,
    'cookie_name'        => 'blitz_session',
    'expiration'         => 7200,
    'savePath'           => FRAMEWORK_STORAGE_PATH . 'session',
    'matchIP'            => false,
    'time_to_update'     => 300,
    'regenerate_destroy' => false,
    'group'              => null,
];
