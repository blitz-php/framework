<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\RateLimiter\Strategies;

use BlitzPHP\Contracts\RateLimiter\Limiter;
use BlitzPHP\Contracts\RateLimiter\ResultInterface;
use BlitzPHP\RateLimiter\RateLimitResult;

/**
 * Stratégie Token Bucket
 *
 * C'est ICI que toute la logique de rate limiting réside.
 */
class TokenBucket extends BaseStrategy implements Limiter
{
    /**
     * {@inheritDoc}
     */
    public function attempt(string $key, int $limit, int $window, int $cost = 1): ResultInterface
    {
        $now = microtime(true);

        // Récupérer l'état du bucket
        $data = $this->get($key, [
            'tokens'      => $limit,
            'last_update' => $now,
            'max_tokens'  => $limit,   // Stocker le max pour pouvoir calculer les attempts
        ]);

        // Calculer le nombre de tokens à ajouter depuis la dernière mise à jour
        $elapsed     = $now - $data['last_update'];
        $refillRate  = $limit / $window;
        $tokensToAdd = $elapsed * $refillRate;

        // Recharger le bucket sans dépasser la limite
        $data['tokens']      = min($limit, $data['tokens'] + $tokensToAdd);
        $data['last_update'] = $now;
        $data['max_tokens']  = $limit;
        $data['window']      = $window;

        if ($cost > 0) {
            // Vérifier si assez de tokens pour le coût demandé
            $allowed = $data['tokens'] >= $cost;

            if ($allowed) {
                $data['tokens'] -= $cost;
            }
        } else {
            // cost=0 : vérification pure, comparer à la limite
            $attempts = max(0, $limit - (int) $data['tokens']);
            $allowed  = $attempts < $limit;
        }

        // Sauvegarder l'état
        $this->set($key, $data, $window);

        // Calculer quand le bucket sera plein (reset time)
        $tokensNeeded  = $limit - $data['tokens'];
        $secondsToFull = $tokensNeeded / $refillRate;
        $reset         = (int) ($now + $secondsToFull);

        // Calculer le retry after si non autorisé
        $retryAfter = 0;
        if (! $allowed) {
            $tokensMissing = $cost - $data['tokens'];
            $retryAfter    = max(1, (int) ceil($tokensMissing / $refillRate));
        }

        return new RateLimitResult(
            allowed   : $allowed,
            limit     : $limit,
            remaining : max(0, (int) $data['tokens']),
            reset     : $reset,
            retryAfter: $retryAfter,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function attempts(string $key): int
    {
        $data = $this->get($key);

        if (! $data) {
            return 0;
        }

        $limit  = $data['max_tokens'] ?? 0;
        $tokens = $data['tokens'] ?? 0;

        return max(0, $limit - (int) $tokens);
    }

    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $window, int $amount = 1): int
    {
        $now = microtime(true);

        $data = $this->get($key, [
            'tokens'      => 0,
            'last_update' => $now,
            'max_tokens'  => 0,
        ]);

        /*
        // Recalculer le refill avant d'incrémenter
        $elapsed     = $now - $data['last_update'];
        $maxTokens   = $data['max_tokens'] ?: PHP_INT_MAX; // Si pas de max défini, on prend le montant comme capacité
        $refillRate  = $maxTokens / $window;
        $tokensToAdd = $elapsed * $refillRate;

        // Appliquer le refill
        $data['tokens'] = min($maxTokens, $data['tokens'] + $tokensToAdd);
        */

        // Consommer les tokens (même si ça rend négatif, c'est manuel)
        $data['tokens'] -= $amount;
        $data['last_update'] = $now;

        $this->set($key, $data, $window);

        // return max(0, (int) ($maxTokens - $data['tokens']));
        return max(0, (int) ($data['max_tokens'] - $data['tokens']));
    }

    /**
     * {@inheritDoc}
     */
    public function availableIn(string $key): int
    {
        $data = $this->get($key);

        if (! $data || ($data['tokens'] ?? 0) > 0) {
            return 0;
        }

        $now        = microtime(true);
        $limit      = $data['max_tokens'] ?? 60;
        $window     = $data['window'] ?? 60;
        $refillRate = $limit / $window;

        // Temps nécessaire pour obtenir 1 token
        $tokensNeeded  = 1 - $data['tokens'];
        $elapsed       = $now - ($data['last_update'] ?? $now);
        $secondsToWait = ($tokensNeeded / $refillRate) - $elapsed;

        return max(1, (int) ceil($secondsToWait));
    }
}
