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

use Throwable;

/**
 * Exception levée lorsqu'une limite de rate limiting est dépassée.
 *
 * Cette exception est conçue pour être automatiquement convertie en réponse HTTP 429
 * par le gestionnaire d'erreurs du framework. Elle porte les headers nécessaires
 * pour informer le client du temps d'attente avant de pouvoir réessayer.
 *
 * Headers automatiquement ajoutés :
 * - Retry-After         : Nombre de secondes avant de pouvoir réessayer
 * - X-RateLimit-Limit   : La limite maximale (si fournie dans $headers)
 * - X-RateLimit-Remaining : Le quota restant (si fourni dans $headers)
 * - X-RateLimit-Reset   : Timestamp du reset (si fourni dans $headers)
 *
 * @example
 * throw new RateLimitExceededException(
 *     retryAfter: 60,
 *     headers: ['X-RateLimit-Limit' => '10', 'X-RateLimit-Remaining' => '0'],
 *     message: 'Trop de requêtes. Veuillez réessayer dans une minute.'
 * );
 */
class RateLimitExceededException extends HttpException
{
    /**
     * Constructeur.
     *
     * @param int            $retryAfter Nombre de secondes avant de pouvoir réessayer (défaut: 60)
     * @param array          $headers    Headers HTTP supplémentaires à ajouter à la réponse
     *                                   (généralement les headers de rate limiting)
     * @param string         $message    Message d'erreur (défaut: 'Too Many Requests')
     * @param Throwable|null $previous   Exception précédente (pour le chaînage)
     */
    public function __construct(
        int $retryAfter = 60,
        array $headers = [],
        string $message = 'Too Many Requests',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 429, $previous);

        // Fusionner le header Retry-After avec les autres headers
        $this->setHeaders(array_merge(
            ['Retry-After' => (string) $retryAfter],
            $headers,
        ));
    }
}
