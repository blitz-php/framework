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

use BlitzPHP\Cache\Handlers\BaseHandler;
use BlitzPHP\Contracts\Cache\CacheInterface;
use BlitzPHP\Contracts\RateLimiter\Limiter;
use BlitzPHP\Contracts\RateLimiter\RateLimiterInterface;
use BlitzPHP\Http\Request;
use BlitzPHP\RateLimiter\Strategies\BaseStrategy;
use BlitzPHP\Traits\Support\InteractsWithTime;
use InvalidArgumentException;

/**
 * Service principal de rate limiting (Throttler).
 *
 * Le Throttler est la façade de haut niveau pour toutes les opérations de rate limiting.
 * Il délègue la logique algorithmique aux stratégies (Limiter) tout en fournissant :
 * - Une API riche et expressive (attempt, hit, clear, etc.)
 * - La gestion des limiteurs nommés
 * - Le nettoyage automatique des clés
 * - Des méthodes d'information (remaining, availableIn, info)
 *
 * Ce service est indépendant du HTTP et peut être utilisé dans n'importe quel contexte :
 * - Contrôleurs
 * - Middleware
 * - Workers CLI
 * - Tests unitaires
 */
class Throttler implements RateLimiterInterface
{
    use InteractsWithTime;

    /**
     * Les configurations de limiteurs nommés.
     *
     * Chaque limiteur associe un nom à une configuration qui peut être :
     * - Un callable recevant Request et retournant Limit|Limit[]
     * - Une instance de Limit directement
     *
     * @var array<string, (callable(Request): Limit|list<Limit>)|Limit>
     */
    protected array $limiters = [];

    /**
     * Stratégie de rate limiting actuellement utilisée.
     */
    protected Limiter $strategy;

    /**
     * Constructeur.
     *
     * @param CacheInterface $cache  Instance du cache pour le stockage des compteurs
     * @param array          $config Configuration optionnelle :
     *                               - strategy : BaseStrategy|string - Stratégie à utiliser
     *                               - prefix   : string              - Préfixe pour les clés
     *
     * @throws InvalidArgumentException Si la stratégie fournie est invalide
     */
    public function __construct(protected CacheInterface $cache, protected array $config = [])
    {
        if ($cache instanceof BaseHandler) {
            $cache->setReservedCharacters('{}()/\\@');
        }

        $this->strategy = $this->resolveStrategy($config['strategy'] ?? 'token_bucket');
    }

    /**
     * Enregistre un limiteur nommé.
     *
     * Les limiteurs nommés permettent de définir des configurations de rate limiting
     * réutilisables dans toute l'application. Une fois enregistré, un limiteur peut
     * être référencé par son nom dans le middleware ou les contrôleurs.
     *
     * @param string         $name     Nom unique du limiteur (ex: "api", "login", "premium")
     * @param callable|Limit $callback Configuration du limiteur :
     *                                 - Un callable recevant Request et retournant Limit|Limit[]
     *                                 - Une instance de Limit directement
     *
     * @return static Pour le chaînage de méthodes
     *
     * @example
     * // Avec un callable pour des limites dynamiques
     * $throttler->for('api', function (Request $request) {
     *     $user = auth()->user();
     *     return Limit::perMinute($user->isPremium() ? 300 : 60)
     *                 ->by('api:' . $user->id);
     * });
     *
     * // Avec une limite statique
     * $throttler->for('login', Limit::perMinute(5, 15)->by('login'));
     */
    public function for(string $name, callable|Limit $callback): static
    {
        $this->limiters[$name] = $callback;

        return $this;
    }

    /**
     * Récupère un limiteur nommé précédemment enregistré.
     *
     * Si le limiteur a été enregistré avec une instance de Limit directement,
     * cette méthode l'encapsule automatiquement dans un callable retournant un tableau.
     *
     * @param string $name Nom du limiteur à récupérer
     *
     * @return (callable(Request): Limit|list<Limit>)|null Le callable du limiteur, ou null si non trouvé
     */
    public function limiter(string $name): ?callable
    {
        if (! isset($this->limiters[$name])) {
            return null;
        }

        $limiter = $this->limiters[$name];

        if ($limiter instanceof Limit) {
            return static fn () => [$limiter];
        }

        return $limiter;
    }

    /**
     * Change la stratégie de rate limiting utilisée.
     *
     * Permet de basculer dynamiquement entre les différentes stratégies
     * (TokenBucket, SlidingWindow, FixedWindow, etc.) sans recréer le service.
     *
     * @param Limiter|string $strategy Instance de Limiter, alias ("token_bucket"),
     *                                 ou nom de classe complet
     *
     * @return static Pour le chaînage de méthodes
     *
     * @throws InvalidArgumentException Si la stratégie fournie est invalide
     */
    public function setStrategy(Limiter|string $strategy): static
    {
        $this->strategy = $this->resolveStrategy($strategy);

        return $this;
    }

    /**
     * Récupère la stratégie de rate limiting actuellement utilisée.
     *
     * @return Limiter L'instance de la stratégie active
     */
    public function getStrategy(): Limiter
    {
        return $this->strategy;
    }

    /**
     * {@inheritDoc}
     */
    public function attempt(string $key, int $maxAttempts, callable $callback, int $decaySeconds = 60): mixed
    {
        if ($this->tooManyAttempts($key, $maxAttempts, $decaySeconds)) {
            return false;
        }

        $result = $callback();

        $this->hit($key, $decaySeconds);

        return $result ?? true;
    }

    /**
     * {@inheritDoc}
     */
    public function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds = 60): bool
    {
        $key = $this->cleanKey($key);

        // cost=0 : vérifie sans consommer, mais peut réinitialiser la fenêtre si expirée
        $result = $this->strategy->attempt($key, $maxAttempts, $decaySeconds, 0);

        return ! $result->isAllowed();
    }

    /**
     * Incrémente le compteur d'un token (raccourci pour increment() avec amount=1).
     *
     * Équivalent sémantique à "consommer un token" ou "enregistrer une tentative".
     * C'est la méthode la plus couramment utilisée pour le comptage simple.
     *
     * @param string $key          Clé unique
     * @param int    $decaySeconds Durée de la fenêtre en secondes (défaut: 60)
     *
     * @return int Nombre total de tentatives après incrémentation
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        return $this->increment($key, $decaySeconds);
    }

    /**
     * Incrémente le compteur uniquement si une condition est remplie.
     *
     * Permet de ne compter que les tentatives "valides" selon un critère métier.
     * Exemples :
     * - Ne pas compter les requêtes d'admin
     * - Ne pas compter les erreurs réseau
     * - Ne compter que les soumissions de formulaire réussies
     *
     * @param string   $key          Clé unique
     * @param callable $condition    Condition à évaluer (retourne true pour compter)
     * @param int      $decaySeconds Durée de la fenêtre en secondes (défaut: 60)
     *
     * @return bool True si le compteur a été incrémenté, false si la condition n'est pas remplie
     *
     * @example
     * $throttler->hitIf('login:' . $ip,
     *     fn() => !$user->isAdmin(),
     *     900
     * );
     */
    public function hitIf(string $key, callable $condition, int $decaySeconds = 60): bool
    {
        if ($condition()) {
            $this->hit($key, $decaySeconds);

            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $decaySeconds = 60, int $amount = 1): int
    {
        return $this->strategy->increment($this->cleanKey($key), $decaySeconds, $amount);
    }

    /**
     * {@inheritDoc}
     */
    public function decrement(string $key, int $decaySeconds = 60, int $amount = 1): int
    {
        return $this->strategy->decrement($key, $decaySeconds, $amount);
    }

    /**
     * {@inheritDoc}
     */
    public function attempts(string $key): int
    {
        return $this->strategy->attempts($this->cleanKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function reset(string $key): bool
    {
        return $this->strategy->reset($this->cleanKey($key));
    }

    /**
     * Réinitialise complètement le compteur pour une clé.
     *
     * Alias explicite pour reset().
     *
     * @param string $key Clé unique
     *
     * @return bool True si la réinitialisation a réussi
     */
    public function resetAttempts(string $key): bool
    {
        return $this->reset($key);
    }

    /**
     * Réinitialise complètement le compteur pour une clé.
     *
     * Alias expressif pour reset().
     *
     * @param string $key Clé unique
     *
     * @return bool True si la réinitialisation a réussi
     */
    public function clear(string $key): bool
    {
        return $this->resetAttempts($key);
    }

    /**
     * {@inheritDoc}
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        return $this->strategy->remaining($this->cleanKey($key), $maxAttempts);
    }

    /**
     * Calcule le nombre de tentatives restantes avant d'atteindre la limite.
     *
     * Alias explicite pour remaining().
     *
     * @param string $key         Clé unique
     * @param int    $maxAttempts Maximum autorisé
     *
     * @return int Nombre de tentatives restantes
     */
    public function retriesLeft(string $key, int $maxAttempts): int
    {
        return $this->remaining($key, $maxAttempts);
    }

    /**
     * {@inheritDoc}
     */
    public function availableIn(string $key): int
    {
        return $this->strategy->availableIn($this->cleanKey($key));
    }

    /**
     * Vérifie si une clé a déjà été utilisée au moins une fois.
     *
     * @param string $key Clé unique
     *
     * @return bool True si la clé a déjà des tentatives enregistrées
     */
    public function hasAttempts(string $key): bool
    {
        return $this->attempts($key) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function info(string $key, int $maxAttempts): array
    {
        $key = $this->cleanKey($key);

        $attempts    = $this->attempts($key);
        $remaining   = $this->remaining($key, $maxAttempts);
        $availableIn = $this->availableIn($key);

        return [
            'attempts'     => $attempts,
            'remaining'    => $remaining,
            'limit'        => $maxAttempts,
            'available_in' => $availableIn,
            'is_limited'   => $remaining <= 0,
        ];
    }

    /**
     * Nettoie une clé pour éviter les problèmes de caractères spéciaux.
     *
     * Remplace tous les caractères non alphanumériques (sauf _, -, ., :)
     * par des underscores pour garantir la compatibilité avec tous les backends de cache.
     *
     * @param string $key Clé à nettoyer
     *
     * @return string Clé nettoyée
     */
    protected function cleanKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-\.:]/', '_', $key);
    }

    /**
     * Résout une stratégie à partir de différents formats.
     *
     * Accepte :
     * - Une instance de Limiter (retournée telle quelle)
     * - Un alias court ("token_bucket", "sliding_window", "fixed_window")
     * - Un nom de classe complet
     *
     * @param Limiter|string $strategy La stratégie à résoudre
     *
     * @return Limiter L'instance de la stratégie
     *
     * @throws InvalidArgumentException Si la stratégie ne peut pas être résolue
     */
    protected function resolveStrategy(Limiter|string $strategy): Limiter
    {
        if ($strategy instanceof Limiter) {
            return $strategy;
        }

        $strategy = BaseStrategy::named($strategy);

        if (! class_exists($strategy) || ! is_subclass_of($strategy, BaseStrategy::class, true)) {
            throw new InvalidArgumentException(
                "Stratégie de rate limiting invalide : {$strategy}",
            );
        }

        $config = $this->config;
        unset($config['strategy']);

        return new $strategy($this->cache, $config);
    }
}
