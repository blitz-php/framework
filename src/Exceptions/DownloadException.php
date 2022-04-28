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

use RuntimeException;

/**
 * Class DownloadException
 */
class DownloadException extends RuntimeException implements ExceptionInterface
{
    use DebugTraceableTrait;

    public static function cannotSetFilePath(string $path)
    {
        return new static(lang('HTTP.cannotSetFilepath', [$path]));
    }

    public static function cannotSetBinary()
    {
        return new static(lang('HTTP.cannotSetBinary'));
    }

    public static function notFoundDownloadSource()
    {
        return new static(lang('HTTP.notFoundDownloadSource'));
    }

    public static function cannotSetCache()
    {
        return new static(lang('HTTP.cannotSetCache'));
    }

    public static function cannotSetStatusCode(int $code, string $reason)
    {
        return new static(lang('HTTP.cannotSetStatusCode', [$code, $reason]));
    }
}
