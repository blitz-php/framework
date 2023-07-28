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
 * Exception pour la journalisation automatique.
 */
class ConfigException extends CriticalError
{
    use DebugTraceableTrait;

    /**
     * code d'erreur
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

    public static function fileDontExist(string $config)
    {
        return new static(lang('Config.configFileDoesNotExist', [$config]));
    }

    public static function notFound(string $key)
    {
        return new static(lang('Config.notFound', [$key]));
    }

    public static function viewAdapterConfigNotFound(string $adapter)
    {
        return new static(lang('Config.viewAdapterConfigNotFound', [$adapter]));
    }
}
