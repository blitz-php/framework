<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Session\Handlers\ArrayHandler;
use BlitzPHP\Session\Handlers\File;
use Nette\Schema\Expect;

return Expect::structure([
    'handler'            => Expect::string()->default(environment('test') ? ArrayHandler::class : File::class),
    'cookie_name'        => Expect::string(env('session.cookieName', config('app.name', 'blitz_app') . '_session')),
    'expiration'         => Expect::int(env('session.expiration', 7200)),
    'savePath'           => Expect::string(env('session.savePath', FRAMEWORK_STORAGE_PATH . 'session')),
    'matchIP'            => Expect::bool(env('session.matchIP', false)),
    'time_to_update'     => Expect::int(env('session.timeToUpdate', 300)),
    'regenerate_destroy' => Expect::bool(env('session.regenerateDestroy', false)),
    'group'              => Expect::string()->nullable(),
]);
