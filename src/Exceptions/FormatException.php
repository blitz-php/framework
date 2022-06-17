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
 * FormatException
 */
class FormatException extends RuntimeException implements ExceptionInterface
{
    use DebugTraceableTrait;

    /**
     * Levée lorsque la classe instanciée n'existe pas.
     */
    public static function invalidFormatter(string $class)
    {
        return new static(lang('Format.invalidFormatter', [$class]));
    }

    /**
     * Lancé dans JSONFormatter lorsque le json_encode produit
     * un code d'erreur autre que JSON_ERROR_NONE et JSON_ERROR_RECURSION.
     */
    public static function invalidJSON(?string $error = null)
    {
        return new static(lang('Format.invalidJSON', [$error]));
    }

    /**
     * Levé lorsque le type MIME fourni n'a pas
     * classe Formatter définie.
     */
    public static function invalidMime(string $mime)
    {
        return new static(lang('Format.invalidMime', [$mime]));
    }

    /**
     * Lancé sur XMLFormatter lorsque l'extension `simplexml`
     * N'est pas installé.
     *
     * @codeCoverageIgnore
     */
    public static function missingExtension()
    {
        return new static(lang('Format.missingExtension'));
    }
}
