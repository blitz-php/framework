<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\RateLimiter;

use BlitzPHP\Contracts\RateLimiter\ResultInterface;

/**
 * Objet valeur immuable représentant le résultat d'une tentative de rate limiting.
 *
 * Cette classe est retournée par les stratégies après chaque appel à attempt().
 * Elle encapsule toutes les informations nécessaires pour :
 * - Déterminer si la requête est autorisée
 * - Informer le client de son quota via les headers HTTP
 * - Fournir des métadonnées supplémentaires pour le débogage ou l'extension
 *
 * Toutes les propriétés sont en lecture seule (readonly) pour garantir l'immuabilité.
 */
class RateLimitResult implements ResultInterface
{
    /**
     * Constructeur.
     *
     * @param bool  $allowed    Si la requête est autorisée (true) ou bloquée (false)
     * @param int   $limit      La limite maximale configurée pour cette fenêtre
     * @param int   $remaining  Le nombre de tokens/requêtes restants après cette opération
     * @param int   $reset      Timestamp Unix indiquant quand le compteur sera réinitialisé
     * @param int   $retryAfter Nombre de secondes à attendre avant de pouvoir réessayer
     *                          (0 si la requête est autorisée)
     * @param array $metadata   Données supplémentaires spécifiques à la stratégie utilisée
     *                          (ex: tokens consommés, fenêtre utilisée, etc.)
     */
    public function __construct(
        public readonly bool $allowed,
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $reset,
        public readonly int $retryAfter = 0,
        public readonly array $metadata = [],
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    /**
     * Génère les headers HTTP standards pour le rate limiting.
     *
     * Les headers produits sont conformes aux standards IETF et reconnus
     * par la plupart des clients HTTP, CDN et proxies.
     *
     * Headers générés :
     * - X-RateLimit-Limit     : La limite maximale
     * - X-RateLimit-Remaining : Le quota restant
     * - X-RateLimit-Reset     : Timestamp Unix du reset
     *
     * Note : Le header Retry-After est géré séparément par
     *        RateLimitExceededException car il n'est pertinent
     *        qu'en cas de dépassement.
     *
     * @return array<string, string> Tableau associatif header => valeur
     */
    public function toHeaders(): array
    {
        return [
            'X-RateLimit-Limit'     => (string) $this->limit,
            'X-RateLimit-Remaining' => (string) $this->remaining,
            'X-RateLimit-Reset'     => (string) $this->reset,
        ];
    }
}
