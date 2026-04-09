<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Session\Cookie\Cookie;
use Nette\Schema\Expect;

return Expect::structure([
    'prefix'   => Expect::string(env('cookie.prefix', '')),
    'expires'  => Expect::type('DateTimeInterface|int|string')->default(env('cookie.expires', 0)),
    'path'     => Expect::string(env('cookie.path', '/')),
    'domain'   => Expect::string(env('cookie.domain', '')),
    'secure'   => Expect::bool((bool) env('cookie.secure', false)),
    'httponly' => Expect::bool((bool) env('cookie.httponly', true)),
    'samesite' => Expect::anyOf('', ...Cookie::SAMESITE_VALUES)->default(env('cookie.samesite', Cookie::SAMESITE_LAX)),
    'raw'      => Expect::bool((bool) env('cookie.raw', false)),
]);
