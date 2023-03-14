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
 * CLIException
 */
class CLIException extends RuntimeException
{
    use DebugTraceableTrait;

    /**
     * Lève quand `$color` spécifié pour `$type` n'est pas dans la
     * liste de couleurs autorisée.
     */
    public static function invalidColor(string $type, string $color)
    {
        return new static(lang('CLI.invalidColor', [$type, $color]));
    }

    /**
     * Lévée que on essaie d'utiliser une commande non valide
     */
    public static function invalidCommand(string $commandName)
    {
        return new static(lang('CLI.invalidCommand', [$commandName]));
    }

    /**
     * Lévée que on essaie d'utiliser une commande qui n'est pas enregistree
     */
    public static function commandNotFound(string $commandName)
    {
        return new static(lang('CLI.commandNotFound', [$commandName]));
    }
}
