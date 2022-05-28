<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cache;

use BlitzPHP\Cache\Handler\BaseHandler;
use BlitzPHP\Cache\Handler\Dummy;
use DateInterval;
use RuntimeException;

/**
 * Cache fournit une interface cohérente à la mise en cache dans votre application. Il vous permet
 * d'utiliser plusieurs moteurs de Cache différents, sans coupler votre application à un moteur spécifique
 * la mise en oeuvre. Il vous permet également de modifier le stockage ou la configuration du cache sans affecter
 * le reste de votre candidature.
 *
 * Cela configurerait un moteur de cache APCu sur l'alias "shared". Vous pourrez alors lire et écrire
 * à cet alias de cache en l'utilisant pour le paramètre `$config` dans les différentes méthodes Cache.
 *
 * En général, toutes les opérations de cache sont prises en charge par tous les moteurs de cache.
 * Cependant, Cache::increment() et Cache::decrement() ne sont pas pris en charge par la mise en cache des fichiers.
 *
 * Il existe 7 moteurs de mise en cache intégrés :
 *
 * - `Apcu` - Utilise le cache d'objets APCu, l'un des moteurs de mise en cache les plus rapides.
 * - `Array` - Utilise uniquement la mémoire pour stocker toutes les données, pas réellement un moteur persistant.
 * 			Peut être utile dans un environnement de test ou CLI.
 * - `File` - Utilise des fichiers simples pour stocker le contenu. Mauvaises performances, mais bonnes pour
 * 			stocker de gros objets ou des choses qui ne sont pas sensibles aux E/S. Bien adapté au développement
 * 			car il s'agit d'un cache facile à inspecter et à vider manuellement.
 * - `Memcache` - Utilise l'extension PECL::Memcache et Memcached pour le stockage.
 * 			Lectures/écritures rapides et avantages de la distribution de Memcache.
 * - `Redis` - Utilise l'extension redis et php-redis pour stocker les données de cache.
 * - `Wincache` - Utilise l'extension de cache Windows pour PHP. Prend en charge Wincache 1.1.0 et supérieur.
 * 			Ce moteur est recommandé aux personnes déployant sur Windows avec IIS.
 * - `Xcache` - Utilise l'extension Xcache, une alternative à APCu.
 *
 * Voir la documentation du moteur de cache pour les clés de configuration attendues.
 */
class Cache
{
    /**
     * Un tableau mappant les schémas d'URL aux noms de classe de moteur de mise en cache complets.
     *
     * @var array<string, string>
     * @psalm-var array<string, class-string>
     */
    protected static $validHandlers = [
        'dummy' => Engine\Dummy::class,
        'file'  => Engine\File::class,
    ];

    /**
     * Flag for tracking whether caching is enabled.
     *
     * @var bool
     */
    protected static $_enabled = true;

    /**
     * Configuration des caches
     *
     * @var array
     */
    protected $config = [];

    /**
     * @var CacheInterface
     */
    private $adapter;

    /**
     * Constructeur
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Modifie lles configuration du cache pour la fabrique actuelle
     */
    public function setConfig(array $config): self
    {
        $this->config  = $config;
        $this->adapter = null;

        return $this;
    }

    /**
     * Tente de créer le gestionnaire de cache souhaité
     */
    protected function factory(): CacheInterface
    {
        if (! static::$_enabled) {
            return new Dummy();
        }
        if (! empty($this->adapter)) {
            return $this->adapter;
        }

        $validHandlers = $this->config['valid_handlers'] ?? self::$validHandlers;

        if (empty($validHandlers) || ! is_array($validHandlers)) {
            throw new InvalidArgumentException('Cache config must have an array of $valid_handlers.');
        }

        $handler  = $this->config['handler'] ?? null;
        $fallback = $this->config['fallback_handler'] ?? null;

        if (empty($handler)) {
            throw new InvalidArgumentException('Cache config must have a handler set.');
        }

        if (! array_key_exists($handler, $validHandlers)) {
            throw new InvalidArgumentException('Cache config has an invalid handler specified.');
        }

        $adapter = new $validHandlers[$handler]($this->config);
        if (! ($adapter instanceof BaseHandler)) {
            if (empty($fallback)) {
                $adapter = new Dummy();
            } elseif (! array_key_exists($fallback, $validHandlers)) {
                throw new InvalidArgumentException('Cache config has an invalid fallback handler specified.');
            } else {
                $adapter = new $validHandlers[$fallback]($this->config);
            }
        }

        if (! ($adapter instanceof BaseHandler)) {
            throw new InvalidArgumentException(
                'Cache handler must use BlitzPHP\Cache\Handlers\BaseHandler as a base class.'
            );
        }

        if (! $adapter->isSupported()) {
            $adapter = new Dummy();
        }

        if (! $adapter->init($this->config)) {
            throw new RuntimeException(
                sprintf(
                    'Cache engine %s is not properly configured. Check error log for additional information.',
                    get_class($adapter)
                )
            );
        }

        return $this->adapter = $adapter;
    }

    /**
     * Écrivez les données de la clé dans le cache.
     *
     * ### Utilisation :
     *
     * Écriture dans la configuration de cache active :
     *
     * ```
     * $cache->write('cached_data', $data);
     * ```
     *
     * @param mixed                 $value Données à mettre en cache - tout sauf une ressource
     * @param DateInterval|int|null $ttl   Facultatif. La valeur TTL de cet élément. Si aucune valeur n'est envoyée et
     *                                     le pilote prend en charge TTL, la bibliothèque peut définir une valeur par défaut
     *                                     pour cela ou laissez le conducteur s'en occuper.
     *
     * @return bool Vrai si les données ont été mises en cache avec succès, faux en cas d'échec
     */
    public function write(string $key, $value, $ttl = null): bool
    {
        if (is_resource($value)) {
            return false;
        }

        $backend = $this->factory();
        $success = $backend->set($key, $value, $ttl);
        if ($success === false && $value !== '') {
            trigger_error(
                sprintf(
                    "Unable to write '%s' to %s cache",
                    $key,
                    get_class($backend)
                ),
                E_USER_WARNING
            );
        }

        return $success;
    }

    /**
     * Écrire des données pour de nombreuses clés dans le cache.
     *
     * ### Utilisation :
     *
     * Écriture dans la configuration de cache active :
     *
     * ```
     * $cache->writeMany(['cached_data_1' => 'data 1', 'cached_data_2' => 'data 2']);
     * ```
     *
     * @param iterable              $data Un tableau ou Traversable de données à stocker dans le cache
     * @param DateInterval|int|null $ttl  Facultatif. La valeur TTL de cet élément. Si aucune valeur n'est envoyée et
     *                                    le pilote prend en charge TTL, la bibliothèque peut définir une valeur par défaut
     *                                    pour cela ou laissez le conducteur s'en occuper.
     *
     * @throws InvalidArgumentException
     *
     * @return bool Vrai en cas de succès, faux en cas d'échec
     */
    public function writeMany(iterable $data, $ttl = null): bool
    {
        return $this->factory()->setMultiple($data, $ttl);
    }

    /**
     * Lire une clé du cache.
     *
     * ### Utilisation :
     *
     * Lecture à partir de la configuration du cache actif.
     *
     * ```
     * $cache->read('my_data');
     *
     * @param mixed|null $default
     */
    public function read(string $key, $default = null)
    {
        return $this->factory()->get($key, $default);
    }

    /**
     * Lire plusieurs clés du cache.
     *
     * ### Utilisation :
     *
     * Lecture de plusieurs clés à partir de la configuration de cache active.
     *
     * ```
     * $cache->readMany(['my_data_1', 'my_data_2]);
     *
     * @param mixed|null $default
     */
    public function readMany(iterable $keys, $default = null): iterable
    {
        return $this->factory()->getMultiple($keys, $default);
    }

    /**
     * Incrémente un nombre sous la clé et renvoie la valeur incrémentée.
     *
     * @param int $offset Combien ajouter
     *
     * @throws InvalidArgumentException Lorsque décalage < 0
     *
     * @return false|int Nouvelle valeur, ou false si la donnée n'existe pas, n'est pas un entier,
     *                   ou si une erreur s'est produite lors de sa récupération.
     */
    public function increment(string $key, int $offset = 1)
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset cannot be less than 0.');
        }

        return $this->factory()->increment($key, $offset);
    }

    /**
     * Décrémenter un nombre sous la clé et renvoyer la valeur décrémentée.
     *
     * @param int $offset Combien soustraire
     *
     * @throws InvalidArgumentException lorsque décalage < 0
     *
     * @return false|int Nouvelle valeur, ou false si la donnée n'existe pas, n'est pas un entier,
     *                   ou s'il y a eu une erreur lors de sa récupération
     */
    public function decrement(string $key, int $offset = 1)
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset cannot be less than 0.');
        }

        return $this->factory()->decrement($key, $offset);
    }

    /**
     * Supprimer une clé du cache.
     *
     * ### Utilisation :
     *
     * Suppression de la configuration du cache actif.
     *
     * ```
     * $cache->delete('my_data');
     * ```
     */
    public function delete(string $key): bool
    {
        return $this->factory()->delete($key);
    }

    /**
     * Supprimez de nombreuses clés du cache.
     *
     * ### Utilisation :
     *
     * Suppression de plusieurs clés de la configuration du cache actif.
     *
     * ```
     * $cache->deleteMany(['my_data_1', 'my_data_2']);
     * ```
     *
     * @param iterable $keys Array ou Traversable de clés de cache à supprimer
     *
     * @throws InvalidArgumentException
     */
    public function deleteMany(iterable $keys): bool
    {
        return $this->factory()->deleteMultiple($keys);
    }

    /**
     * Supprimez toutes les clés du cache.
     */
    public function clear(): bool
    {
        return $this->factory()->clear();
    }

    /**
     * Supprimez toutes les clés du cache appartenant au même groupe.
     */
    public function clearGroup(string $group): bool
    {
        return $this->factory()->clearGroup($group);
    }

    /**
     * Réactivez la mise en cache.
     *
     * Si la mise en cache a été désactivée avec Cache::disable() cette méthode inversera cet effet.
     */
    public static function enable(): void
    {
        static::$_enabled = true;
    }

    /**
     * Désactivez la mise en cache.
     *
     * Lorsqu'il est désactivé, toutes les opérations de cache renverront null.
     */
    public static function disable(): void
    {
        static::$_enabled = false;
    }

    /**
     * Vérifiez si la mise en cache est activée.
     */
    public static function enabled(): bool
    {
        return static::$_enabled;
    }

    /**
     * Fournit la possibilité de faire facilement la mise en cache de lecture.
     *
     * Lorsqu'elle est appelée si la clé $ n'est pas définie dans $config, la fonction $callable
     * sera invoqué. Les résultats seront ensuite stockés dans la configuration du cache
     * à la clé.
     *
     * Exemples:
     *
     * En utilisant une Closure pour fournir des données, supposez que `$this` est un objet Table :
     *
     * ```
     * $resultats = $cache->remember('all_articles', function() {
     * 		return $this->find('all')->toArray();
     * });
     * ```
     *
     * @param string   $key      La clé de cache sur laquelle lire/stocker les données.
     * @param callable $callable Le callback qui fournit des données dans le cas où
     *                           la clé de cache est vide. Peut être n'importe quel type appelable pris en charge par votre PHP.
     *
     * @return mixed Si la clé est trouvée : les données en cache.
     *               Si la clé n'est pas trouvée, la valeur renvoyée par le callable.
     */
    public function remember(string $key, callable $callable)
    {
        $existing = $this->read($key);
        if ($existing !== null) {
            return $existing;
        }
        $results = $callable();
        $this->write($key, $results);

        return $results;
    }

    /**
     * Écrivez les données de la clé dans un moteur de cache si elles n'existent pas déjà.
     *
     * ### Utilisation :
     *
     * Écriture dans la configuration de cache active :
     *
     * ```
     * $cache->add('cached_data', $data);
     * ```
     *
     * @param mixed $value Données à mettre en cache - tout sauf une ressource.
     */
    public function add(string $key, $value): bool
    {
        if (is_resource($value)) {
            return false;
        }

        return $this->factory()->add($key, $value);
    }
}
