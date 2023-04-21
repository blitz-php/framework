<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Session;

use Exception;

class SessionException extends Exception
{
    public static function missingDatabaseTable()
    {
        return new static(lang('Session.missingDatabaseTable'));
    }

    public static function invalidSavePath(?string $path = null)
    {
        return new static(lang('Session.invalidSavePath', [$path]));
    }

    public static function writeProtectedSavePath(?string $path = null)
    {
        return new static(lang('Session.writeProtectedSavePath', [$path]));
    }

    public static function emptySavepath()
    {
        return new static(lang('Session.emptySavePath'));
    }

    public static function invalidSavePathFormat(string $path)
    {
        return new static(lang('Session.invalidSavePathFormat', [$path]));
    }
}
