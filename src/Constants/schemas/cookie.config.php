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
    'prefix'   => Expect::string()->default(''),
    'expires'  => Expect::type('DateTimeInterface|int|string')->default(0),
    'path'     => Expect::string()->default('/'),
    'domain'   => Expect::string()->default(''),
    'secure'   => Expect::bool()->default(false),
    'httponly' => Expect::bool()->default(true),
    'samesite' => Expect::anyOf('', ...Cookie::SAMESITE_VALUES)->default(Cookie::SAMESITE_LAX),
    'raw'      => Expect::bool()->default(false),
]);
