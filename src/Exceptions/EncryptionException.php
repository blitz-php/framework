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
 * Encryption exception
 */
class EncryptionException extends RuntimeException implements ExceptionInterface
{
    use DebugTraceableTrait;

    /**
     * Lancée lorsqu'aucun pilote n'est présent dans la session de chiffrement active.
     *
     * @return static
     */
    public static function noDriverRequested()
    {
        return new static(lang('Encryption.noDriverRequested'));
    }

    /**
     * Lancé lorsque le gestionnaire demandé n'est pas disponible.
     *
     * @return static
     */
    public static function noHandlerAvailable(string $handler)
    {
        return new static(lang('Encryption.noHandlerAvailable', [$handler]));
    }

    /**
     * Lancé lorsque le gestionnaire demandé est inconnu.
     *
     * @return static
     */
    public static function unKnownHandler(?string $driver = null)
    {
        return new static(lang('Encryption.unKnownHandler', [$driver]));
    }

    /**
     * Lancée lorsqu'aucune clé de démarrage n'est fournie pour la session de chiffrement en cours.
     *
     * @return static
     */
    public static function needsStarterKey()
    {
        return new static(lang('Encryption.starterKeyNeeded'));
    }

    /**
     * Lancée lors du décryptage des données lorsqu'un problème ou une erreur s'est produite.
     *
     * @return static
     */
    public static function authenticationFailed()
    {
        return new static(lang('Encryption.authenticationFailed'));
    }

    /**
     * Lancée lors du cryptage des données lorsqu'un problème ou une erreur s'est produite.
     *
     * @return static
     */
    public static function encryptionFailed()
    {
        return new static(lang('Encryption.encryptionFailed'));
    }
}
