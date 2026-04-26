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

/**
 * Objet de configuration fluide pour les limites de rate limiting.
 *
 * Cette classe permet de définir des limites de manière expressive et lisible.
 *
 * Toutes les propriétés sont en lecture seule (readonly) pour garantir
 * l'immuabilité de la configuration une fois créée.
 *
 * @example
 * // 60 requêtes par minute
 * Limit::perMinute(60);
 *
 * // 5 tentatives en 15 minutes, identifié par l'IP
 * Limit::perMinute(5, 15)->by($request->getIpAddress());
 *
 * // 1000 requêtes par jour pour un utilisateur
 * Limit::perDay(1000)->by('user:' . $userId);
 */
class Limit
{
    /**
     * Constructeur.
     *
     * @param string $key          Clé d'identification optionnelle (peut être ajoutée via by())
     * @param int    $maxAttempts  Nombre maximum de tentatives autorisées (défaut: 60)
     * @param int    $decaySeconds Durée de la fenêtre en secondes (défaut: 60)
     */
    public function __construct(
        public readonly string $key = '',
        public readonly int $maxAttempts = 60,
        public readonly int $decaySeconds = 60,
    ) {
    }

    /**
     * Crée une limite par seconde.
     *
     * @param int $maxAttempts  Nombre maximum de requêtes
     * @param int $decaySeconds Durée en secondes (défaut: 1)
     *
     * @return static
     */
    public static function perSecond(int $maxAttempts, int $decaySeconds = 1): self
    {
        return new static('', $maxAttempts, $decaySeconds);
    }

    /**
     * Crée une limite par minute.
     *
     * @param int $maxAttempts  Nombre maximum de requêtes
     * @param int $decayMinutes Durée en minutes (défaut: 1)
     *
     * @return static
     *
     * @example
     * // 30 requêtes par minute
     * Limit::perMinute(30);
     *
     * // 100 requêtes en 5 minutes
     * Limit::perMinute(100, 5);
     */
    public static function perMinute(int $maxAttempts, int $decayMinutes = 1): self
    {
        return new static('', $maxAttempts, 60 * $decayMinutes);
    }

    /**
     * Crée une limite par heure.
     *
     * @param int $maxAttempts Nombre maximum de requêtes
     * @param int $decayHours  Durée en heures (défaut: 1)
     *
     * @return static
     *
     * @example
     * // 500 requêtes par heure
     * Limit::perHour(500);
     *
     * // 1000 requêtes en 6 heures
     * Limit::perHour(1000, 6);
     */
    public static function perHour(int $maxAttempts, int $decayHours = 1): self
    {
        return new static('', $maxAttempts, 3600 * $decayHours);
    }

    /**
     * Crée une limite par jour.
     *
     * @param int $maxAttempts Nombre maximum de requêtes
     * @param int $decayDays   Durée en jours (défaut: 1)
     *
     * @return static
     *
     * @example
     * // 1000 requêtes par jour
     * Limit::perDay(1000);
     */
    public static function perDay(int $maxAttempts, int $decayDays = 1): self
    {
        return new static('', $maxAttempts, 86400 * $decayDays);
    }

    /**
     * Crée une limite illimitée (pas de restriction).
     *
     * Pratique pour désactiver le rate limiting pour certains utilisateurs
     * ou certaines routes sans changer la logique applicative.
     *
     * @return static
     *
     * @example
     * // Admins illimités
     * if ($user->isAdmin()) {
     *     return Limit::unlimited();
     * }
     */
    public static function unlimited(): self
    {
        return new static('', PHP_INT_MAX, 1);
    }

    /**
     * Définit la clé d'identification pour cette limite.
     *
     * La méthode by() permet d'attacher une clé unique à la limite,
     * typiquement utilisée pour identifier un utilisateur, une IP, ou une route.
     *
     * @param string $key Clé d'identification unique
     *
     * @return self Nouvelle instance avec la clé définie (immuable)
     *
     * @example
     * Limit::perMinute(60)->by('user:' . $userId);
     * Limit::perHour(100)->by($request->getIpAddress());
     */
    public function by(string $key): self
    {
        return new self($key, $this->maxAttempts, $this->decaySeconds);
    }
}
