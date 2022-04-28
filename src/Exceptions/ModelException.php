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

/**
 * Model Exceptions.
 */
class ModelException extends FrameworkException
{
    public static function noPrimaryKey(string $modelName)
    {
        return new static(lang('Database.noPrimaryKey', [$modelName]));
    }

    public static function noDateFormat(string $modelName)
    {
        return new static(lang('Database.noDateFormat', [$modelName]));
    }
}
