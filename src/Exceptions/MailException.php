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
use Throwable;

/**
 * Exception pour les erreurs d'envoi de mail
 */
class MailException extends RuntimeException
{
    /**
     * Code d'erreur pour configuration invalide
     */
    public const CONFIG_ERROR = 1000;

    /**
     * Code d'erreur pour envoi échoué
     */
    public const SEND_ERROR = 1001;

    /**
     * Code d'erreur pour validation échouée
     */
    public const VALIDATION_ERROR = 1002;

    /**
     * Code d'erreur pour pièce jointe invalide
     */
    public const ATTACHMENT_ERROR = 1003;

    /**
     * Constructeur
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Crée une exception pour configuration invalide
     */
    public static function invalidConfig(string $message): static
    {
        return new static($message, self::CONFIG_ERROR);
    }

    /**
     * Crée une exception pour envoi échoué
     */
    public static function sendFailed(string $message, ?Throwable $previous = null): static
    {
        return new static($message, self::SEND_ERROR, $previous);
    }

    /**
     * Crée une exception pour validation échouée
     *
     * @param array<string> $errors Liste des erreurs
     */
    public static function validationFailed(array $errors): static
    {
        return new static(
            sprintf('Validation du mail échouée: %s', implode(', ', $errors)),
            self::VALIDATION_ERROR
        );
    }

    /**
     * Crée une exception pour pièce jointe invalide
     *
     * @param string $filePath Chemin du fichier
     * @param string $reason Raison de l'échec
     */
    public static function invalidAttachment(string $filePath, string $reason): static
    {
        return new static(
            sprintf('Pièce jointe invalide (%s): %s', $filePath, $reason),
            self::ATTACHMENT_ERROR
        );
    }
}
