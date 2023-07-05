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
 * Publisher Exception Class
 *
 * Gère les exceptions liées aux actions entreprises par un Publisher.
 */
class PublisherException extends FrameworkException
{
    /**
     * Lève lorsqu'un fichier doit être écrasé mais ne le peut pas.
     *
     * @param string $from Le fichier source
     * @param string $to   The destination file
     */
    public static function collision(string $from, string $to)
    {
        return new static(lang('Publisher.collision', [filetype($to), $from, $to]));
    }

    /**
     * Lève une fois donnée une destination qui ne figure pas dans la liste des répertoires autorisés.
     */
    public static function destinationNotAllowed(string $destination)
    {
        return new static(lang('Publisher.destinationNotAllowed', [$destination]));
    }

    /**
     * Lève lorsqu'un fichier ne correspond pas au modèle autorisé pour sa destination.
     */
    public static function fileNotAllowed(string $file, string $directory, string $pattern)
    {
        return new static(lang('Publisher.fileNotAllowed', [$file, $directory, $pattern]));
    }
}
