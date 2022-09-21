<?php
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
}
