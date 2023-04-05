<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cache\Handlers;

use DateInterval;
use Redis;
use RedisException;
use RuntimeException;

/**
 * Moteur de stockage Redis pour le cache.
 */
class RedisHandler extends BaseHandler
{
    /**
     * Wrapper Redis.
     *
     * @var Redis
     */
    protected $_Redis;

    /**
     * La configuration par défaut utilisée sauf si elle est remplacée par la configuration d'exécution
     *
     * - Numéro de base de données `database` à utiliser pour la connexion.
     * - `duration` Spécifiez combien de temps durent les éléments de cette configuration de cache.
     * - `groups` Liste des groupes ou 'tags' associés à chaque clé stockée dans cette configuration.
     * pratique pour supprimer un groupe complet du cache.
     * - `password` Mot de passe du serveur Redis.
     * - `persistent` Connectez-vous au serveur Redis avec une connexion persistante
     * - `port` numéro de port vers le serveur Redis.
     * - `prefix` Préfixe ajouté à toutes les entrées. Bon pour quand vous avez besoin de partager un keyspace
     * avec une autre configuration de cache ou une autre application.
     * - URL ou IP `server` vers l'hôte du serveur Redis.
     * - Délai d'expiration de `timeout` en secondes (flottant).
     * - `unix_socket` Chemin vers le fichier socket unix (par défaut : false)
     */
    protected array $_defaultConfig = [
        'database'    => 0,
        'duration'    => 3600,
        'groups'      => [],
        'password'    => false,
        'persistent'  => true,
        'port'        => 6379,
        'prefix'      => 'blitz_',
        'host'        => null,
        'server'      => '127.0.0.1',
        'timeout'     => 0,
        'unix_socket' => false,
    ];

    /**
     * {@inheritDoc}
     */
    public function init(array $config = []): bool
    {
        if (! extension_loaded('redis')) {
            throw new RuntimeException('L\'extension `redis` doit être activée pour utiliser RedisHandler.');
        }

        if (! empty($config['host'])) {
            $config['server'] = $config['host'];
        }

        parent::init($config);

        return $this->_connect();
    }

    /**
     * Connection au serveur Redis
     *
     * @return bool Vrai si le serveur Redis était connecté
     */
    protected function _connect(): bool
    {
        try {
            $this->_Redis = new Redis();
            if (! empty($this->_config['unix_socket'])) {
                $return = $this->_Redis->connect($this->_config['unix_socket']);
            } elseif (empty($this->_config['persistent'])) {
                $return = $this->_Redis->connect(
                    $this->_config['server'],
                    (int) $this->_config['port'],
                    (int) $this->_config['timeout']
                );
            } else {
                $persistentId = $this->_config['port'] . $this->_config['timeout'] . $this->_config['database'];
                $return       = $this->_Redis->pconnect(
                    $this->_config['server'],
                    (int) $this->_config['port'],
                    (int) $this->_config['timeout'],
                    $persistentId
                );
            }
        } catch (RedisException $e) {
            if (function_exists('logger')) {
                $logger = logger();
                if (is_object($logger) && method_exists($logger, 'error')) {
                    $logger->error('RedisEngine n\'a pas pu se connecter. Erreur: ' . $e->getMessage());
                }
            }

            return false;
        }
        if ($return && $this->_config['password']) {
            $return = $this->_Redis->auth($this->_config['password']);
        }
        if ($return) {
            $return = $this->_Redis->select((int) $this->_config['database']);
        }

        return $return;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $key   = $this->_key($key);
        $value = $this->serialize($value);

        $duration = $this->duration($ttl);
        if ($duration === 0) {
            return $this->_Redis->set($key, $value);
        }

        return $this->_Redis->setEx($key, $duration, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->_Redis->get($this->_key($key));
        if ($value === false) {
            return $default;
        }

        return $this->unserialize($value);
    }

    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $offset = 1)
    {
        $duration = $this->_config['duration'];
        $key      = $this->_key($key);

        $value = $this->_Redis->incrBy($key, $offset);
        if ($duration > 0) {
            $this->_Redis->expire($key, $duration);
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function decrement(string $key, int $offset = 1)
    {
        $duration = $this->_config['duration'];
        $key      = $this->_key($key);

        $value = $this->_Redis->decrBy($key, $offset);
        if ($duration > 0) {
            $this->_Redis->expire($key, $duration);
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $key = $this->_key($key);

        return $this->_Redis->del($key) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $this->_Redis->setOption(Redis::OPT_SCAN, (string) Redis::SCAN_RETRY);

        $isAllDeleted = true;
        $iterator     = null;
        $pattern      = $this->_config['prefix'] . '*';

        while (true) {
            $keys = $this->_Redis->scan($iterator, $pattern);

            if ($keys === false) {
                break;
            }

            foreach ($keys as $key) {
                $isDeleted    = ($this->_Redis->del($key) > 0);
                $isAllDeleted = $isAllDeleted && $isDeleted;
            }
        }

        return $isAllDeleted;
    }

    /**
     * {@inheritDoc}
     *
     * @see https://github.com/phpredis/phpredis#set
     */
    public function add(string $key, mixed $value): bool
    {
        $duration = $this->_config['duration'];
        $key      = $this->_key($key);
        $value    = $this->serialize($value);

        return (bool) ($this->_Redis->set($key, $value, ['nx', 'ex' => $duration]));
    }

    /**
     * {@inheritDoc}
     */
    public function info()
    {
        return $this->_Redis->info();
    }

    /**
     * {@inheritDoc}
     */
    public function groups(): array
    {
        $result = [];

        foreach ($this->_config['groups'] as $group) {
            $value = $this->_Redis->get($this->_config['prefix'] . $group);
            if (! $value) {
                $value = $this->serialize(1);
                $this->_Redis->set($this->_config['prefix'] . $group, $value);
            }
            $result[] = $group . $value;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function clearGroup(string $group): bool
    {
        return (bool) $this->_Redis->incr($this->_config['prefix'] . $group);
    }

    /**
     * Sérialisez la valeur pour l'enregistrer dans Redis.
     *
     * Ceci est nécessaire au lieu d'utiliser la fonction de sérialisation intégrée de Redis
     * car cela crée des problèmes d'incrémentation/décrémentation de la valeur entière initialement définie.
     *
     * @see https://github.com/phpredis/phpredis/issues/81
     */
    protected function serialize(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        return serialize($value);
    }

    /**
     * Désérialiser la valeur de chaîne extraite de Redis.
     */
    protected function unserialize(string $value): mixed
    {
        if (preg_match('/^[-]?\d+$/', $value)) {
            return (int) $value;
        }

        return unserialize($value);
    }

    /**
     * Se déconnecte du serveur redis
     */
    public function __destruct()
    {
        if (empty($this->_config['persistent']) && $this->_Redis instanceof Redis) {
            $this->_Redis->close();
        }
    }
}
