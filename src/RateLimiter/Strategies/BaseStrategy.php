<?php

namespace BlitzPHP\RateLimiter\Strategies;

use BlitzPHP\Contracts\Cache\CacheInterface;
use BlitzPHP\Contracts\RateLimiter\Limiter;
use InvalidArgumentException;

/**
 * Classe abstraite de base pour toutes les stratégies de rate limiting.
 *
 * Fournit les fonctionnalités communes à toutes les stratégies :
 * - Gestion du préfixe de cache pour éviter les collisions
 * - Méthodes helper get()/set() pour interagir avec le cache
 * - Implémentation par défaut de decrement() via increment()
 * - Implémentation par défaut de remaining() via attempts()
 * - Registre des stratégies disponibles et extensibilité
 *
 * Pour créer une nouvelle stratégie, il suffit d'étendre cette classe
 * et d'implémenter les méthodes abstraites de l'interface Limiter.
 */
abstract class BaseStrategy implements Limiter
{
    /**
     * Registre des stratégies disponibles.
     *
     * Associe un alias court (ex: "token_bucket") à une classe de stratégie.
     * Peut être étendu via la méthode statique extends().
     *
     * @var array<string, class-string<BaseStrategy>>
     */
    protected static array $strategies = [
        'token_bucket'   => TokenBucket::class,
        'sliding_window' => SlidingWindow::class,
        'fixed_window'   => FixedWindow::class,
    ];

    /**
     * Préfixe appliqué à toutes les clés de cache.
     *
     * Évite les collisions avec d'autres données en cache.
     * Modifiable via la configuration.
     *
     * @var string
     */
    protected string $prefix;

    /**
     * Constructeur.
     *
     * @param CacheInterface $cache  Instance du cache à utiliser pour le stockage
     * @param array          $config Configuration optionnelle :
     *                               - prefix : string - Préfixe pour les clés de cache (défaut: "throttler:")
     */
    public function __construct(protected CacheInterface $cache, protected array $config = [])
{
        $this->prefix = $config['prefix'] ?? 'throttler:';
    }

    /**
     * {@inheritDoc}
     *
     * Implémentation par défaut : calcule la différence entre la limite et le nombre de tentatives.
     */
    public function remaining(string $key, int $limit): int
    {
        $attempts = $this->attempts($key);

        return max(0, $limit - $attempts);
    }

    /**
     * {@inheritDoc}
     */
    public function decrement(string $key, int $window, int $amount = 1): int
    {
        return $this->increment($key, $window, $amount * -1);
    }

    /**
     * {@inheritDoc}
     */
    public function reset(string $key): bool
    {
        return $this->cache->delete($this->prefixKey($key));
    }

    /**
     * Résout un alias de stratégie en nom de classe complet.
     *
     * Si l'alias n'est pas reconnu, retourne la valeur telle quelle
     * (peut être déjà un nom de classe complet).
     *
     * @param string $alias Alias court ("token_bucket", "sliding_window", "fixed_window")
     *                      ou nom de classe complet
     *
     * @return class-string<BaseStrategy> Le nom de classe complet de la stratégie
     *
     * @example
     * BaseStrategy::named('token_bucket');   // → TokenBucket::class
     * BaseStrategy::named('MyStrategy');     // → MyStrategy (tel quel)
     */
    public static function named(string $alias): string
    {
        return static::$strategies[strtolower($alias)] ?? $alias;
    }

    /**
     * Enregistre une ou plusieurs nouvelles stratégies.
     *
     * Permet d'étendre le système avec des stratégies personnalisées
     * sans modifier le code source du framework.
     *
     * @param array|string                          $alias Un alias ou un tableau [alias => class]
     * @param class-string<BaseStrategy>|null       $class La classe (obligatoire si $alias est une string)
     *
     * @throws InvalidArgumentException Si la classe ne peut pas être résolue
     *
     * @example
     * // Ajouter une seule stratégie
     * BaseStrategy::extends('leaky_bucket', LeakyBucketStrategy::class);
     *
     * // Ajouter plusieurs stratégies
     * BaseStrategy::extends([
     *     'leaky_bucket' => LeakyBucketStrategy::class,
     *     'redis_lua'    => RedisLuaStrategy::class,
     * ]);
     */
    public static function extends(array|string $alias, ?string $class = null): void
    {
        if (is_string($alias)) {
            if ($class === null) {
                throw new InvalidArgumentException(
                    "La classe doit être fournie lorsque l'alias est une chaîne de caractères."
                );
            }
            $alias = [$alias => $class];
        }

        foreach ($alias as $name => $class) {
            if (! is_subclass_of($class, self::class, true)) {
                throw new InvalidArgumentException(
                    "La classe {$class} doit étendre " . self::class
                );
            }

            static::$strategies[strtolower($name)] = $class;
        }
    }

    /**
     * Récupère une valeur depuis le cache avec le préfixe automatique.
     *
     * Méthode helper qui applique automatiquement le préfixe configuré
     * à la clé avant de la chercher dans le cache.
     *
     * @param string $key     Clé relative (sans préfixe)
     * @param mixed  $default Valeur par défaut si la clé n'existe pas
     *
     * @return mixed La valeur stockée ou la valeur par défaut
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($this->prefixKey($key), $default);
    }

    /**
     * Stocke une valeur dans le cache avec le préfixe automatique.
     *
     * Méthode helper qui applique automatiquement le préfixe configuré
     * à la clé avant de la stocker dans le cache.
     *
     * @param string   $key   Clé relative (sans préfixe)
     * @param mixed    $value Valeur à stocker
     * @param int|null $ttl   Durée de vie en secondes (null = pas d'expiration)
     *
     * @return bool True si le stockage a réussi
     */
    protected function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->cache->set($this->prefixKey($key), $value, $ttl);
    }

	/**
	 * Vérifie si une clé existe dans le cache avec le préfixe automatique.
	 *
	 * Méthode helper qui applique automatiquement le préfixe configuré
	 * à la clé avant de vérifier son existence dans le cache.
	 * Utile pour les stratégies qui ont besoin de vérifier l'existence d'une clé
	 * sans nécessairement récupérer sa valeur.
	 *
	 * @param string $key Clé relative (sans préfixe)
	 *
	 * @return bool True si la clé existe dans le cache, false sinon
	 */
	protected function has(string $key): bool
	{
		return $this->cache->has($this->prefixKey($key));
	}

	/**
	 * Applique le préfixe à une clé pour éviter les collisions.
	 *
	 * Méthode helper qui transforme une clé relative en clé complète
	 * en appliquant le préfixe configuré. Utile pour les opérations de cache
	 * directes ou pour générer des clés uniques basées sur des paramètres.
	 *
	 * @param string $key Clé relative (sans préfixe)
	 *
	 * @return string Clé complète avec préfixe
	 */
	protected function prefixKey(string $key): string
	{
		return $this->prefix . $key;
	}
}
