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

use OutOfBoundsException;

class PageNotFoundException extends OutOfBoundsException implements ExceptionInterface
{
    use DebugTraceableTrait;

    /**
     * Code d'erreur
     *
     * @var int
     */
    protected $code = 404;

    public static function pageNotFound(?string $message = null)
    {
        return new static($message ?? self::lang('HTTP.pageNotFound'));
    }

    public static function emptyController()
    {
        return new static(self::lang('HTTP.emptyController'));
    }

    public static function controllerNotFound(string $controller, string $method)
    {
        return new static(self::lang('HTTP.controllerNotFound', [$controller, $method]));
    }

    public static function methodNotFound(string $method)
    {
        return new static(self::lang('HTTP.methodNotFound', [$method]));
    }
}
