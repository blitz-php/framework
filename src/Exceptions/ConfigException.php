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
 * Exception for automatic logging.
 */
class ConfigException extends CriticalError
{
    use DebugTraceableTrait;

    /**
     * Error code
     *
     * @var int
     */
    protected $code = 3;

    public static function disabledMigrations()
    {
        return new static(lang('Migrations.disabled'));
    }

    public static function configDontExist(string $config, string $file)
    {
        return new static(lang('Config.fileDoesNotExist', [$config, $file]));
    }

    public static function viewAdapterConfigNotFound(string $adapter)
    {
        return new static(lang('Config.viewAdapterConfigNotFound', [$adapter]));
    }
}
