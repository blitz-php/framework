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
 * Class FrameworkException
 *
 * Une collection d'exceptions lancées par le framework
 * qui ne peut être déterminé qu'au moment de l'exécution.
 */
class FrameworkException extends RuntimeException implements ExceptionInterface
{
    use DebugTraceableTrait;

    public static function enabledZlibOutputCompression()
    {
        return new static(lang('Core.enabledZlibOutputCompression'));
    }

    public static function invalidFile(string $path)
    {
        return new static(lang('Core.invalidFile', [$path]));
    }

    public static function copyError(string $path)
    {
        return new static(lang('Core.copyError', [$path]));
    }

    public static function missingExtension(string $extension)
    {
        if (str_contains($extension, 'intl')) {
            // @codeCoverageIgnoreStart
            $message = sprintf(
                'The framework needs the following extension(s) installed and loaded: %s.',
                $extension
            );
        // @codeCoverageIgnoreEnd
        } else {
            $message = lang('Core.missingExtension', [$extension]);
        }

        return new static($message);
    }

    public static function noHandlers(string $class)
    {
        return new static(lang('Core.noHandlers', [$class]));
    }

    public static function fabricatorCreateFailed(string $table, string $reason)
    {
        return new static(lang('Fabricator.createFailed', [$table, $reason]));
    }
}
