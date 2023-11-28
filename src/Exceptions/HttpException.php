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

class HttpException extends FrameworkException
{
    public static function methodNotAllowed(string $method): self
    {
        return new static(self::lang('HTTP.methodNotAllowed', [$method]));
    }

    public static function invalidStatusCode(int $code)
    {
        return new static(lang('HTTP.invalidStatusCode', [$code]));
    }

    public static function unkownStatusCode(int $code)
    {
        return new static(lang('HTTP.unknownStatusCode', [$code]));
    }

    public static function invalidRedirectRoute(string $route)
    {
        return new static(lang('HTTP.invalidRoute', [$route]));
    }

    public static function badRequest(string $message = 'Bad Request')
    {
        return new static($message, 400);
    }
}
