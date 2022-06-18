<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cache;

use Exception;
use Psr\SimpleCache\InvalidArgumentException as InvalidArgumentInterface;

/**
 * Exception déclenchée lorsque les clés de cache ne sont pas valides.
 */
class InvalidArgumentException extends Exception implements InvalidArgumentInterface
{
}
