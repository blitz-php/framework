<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Exceptions;

use Exception;

/**
 * RedirectException
 */
class RedirectException extends Exception
{
    /**
     * Status code pour les redirections
     *
     * @var int
     */
    protected $code = 302;
}
