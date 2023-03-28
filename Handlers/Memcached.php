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
use InvalidArgumentException;
use Memcached as BaseMemcached;
use RuntimeException;

/**
 * Moteur de stockage Memcached pour le cache. Memcached a certaines limitations dans la quantité de
 * le contrôle que vous avez sur les délais d'expiration lointains dans le futur. Voir MemcachedEngine::write() pour
 * Plus d'information.
 *
 * Le moteur Memcached prend en charge le protocole binaire et igbinary
 * sérialisation (si l'extension memcached est compilée avec --enable-igbinary).
 * Les touches compressées peuvent également être incrémentées/décrémentées.
 */
class Memcached extends BaseHandler
{
    /**
     * Wrapper Memcached.
     *
     * @var BaseMemcached
     */
    protected $_Memcached;

    /**
     * La configuration par défaut utilisée sauf si elle est remplacée par la configuration d'exécution
     *
     * - `compress` Indique s'il faut compresser les données
     * - `duration` Spécifiez combien de temps durent les éléments de cette configuration de cache.
     * - `groups` Liste des groupes ou 'tags' associés à chaque clé stockée dans cette configuration.
     * pratique pour supprimer un groupe complet du cache.
     * - `nom d'utilisateur` Connectez-vous pour accéder au serveur Memcache
     * - `password` Mot de passe pour accéder au serveur Memcache
     * - `persistent` Le nom de la connexion persistante. Toutes les configurations utilisant
     * la même valeur persistante partagera une seule connexion sous-jacente.
     * - `prefix` Préfixé à toutes les entrées. Bon pour quand vous avez besoin de partager un keyspace
     * avec une autre configuration de cache ou une autre application.
     * - `serialize` Le moteur de sérialisation utilisé pour sérialiser les données. Les moteurs disponibles sont 'php',
     * 'igbinaire' et 'json'. A côté de 'php', l'extension memcached doit être compilée avec le
     * Prise en charge appropriée du sérialiseur.
     * - `servers` Chaîne ou tableau de serveurs memcached. Si un tableau MemcacheEngine utilisera
     * eux comme une piscine.
     * - `options` - Options supplémentaires pour le client memcached. Doit être un tableau d'option => valeur.
     * Utilisez les constantes \Memcached::OPT_* comme clés.
     */
    protected array $_defaultConfig = [
        'compress'   => false,
        'duration'   => 3600,
        'groups'     => [],
        'host'       => null,
        'username'   => null,
        'password'   => null,
        'persistent' => null,
        'port'       => null,
        'prefix'     => 'blitz_',
        'serialize'  => 'php',
        'servers'    => ['127.0.0.1'],
        'options'    => [],
    ];

    /**
     * Liste des moteurs de sérialisation disponibles
     *
     * Memcached doit être compilé avec JSON et le support igbinary pour utiliser ces moteurs
     */
    protected array $_serializers = [];

    /**
     * @var string[]
     */
    protected array $_compiledGroupNames = [];

    /**
     * {@inheritDoc}
     */
    public function init(array $config = []): bool
    {
        if (! extension_loaded('memcached')) {
            throw new RuntimeException('L\'extension `memcached` doit être activée pour utiliser MemcachedHandler.');
        }

        $this->_serializers = [
            'igbinary' => BaseMemcached::SERIALIZER_IGBINARY,
            'json'     => BaseMemcached::SERIALIZER_JSON,
            'php'      => BaseMemcached::SERIALIZER_PHP,
        ];
        if (defined('Memcached::HAVE_MSGPACK')) {
            $this->_serializers['msgpack'] = BaseMemcached::SERIALIZER_MSGPACK;
        }

        parent::init($config);

        if (! empty($config['host'])) {
            if (empty($config['port'])) {
                $config['servers'] = [$config['host']];
            } else {
                $config['servers'] = [sprintf('%s:%d', $config['host'], $config['port'])];
            }
        }

        if (isset($config['servers'])) {
            $this->setConfig('servers', $config['servers'], false);
        }

        if (! is_array($this->_config['servers'])) {
            $this->_config['servers'] = [$this->_config['servers']];
        }

        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->_Memcached)) {
            return true;
        }

        if ($this->_config['persistent']) {
            $this->_Memcached = new BaseMemcached($this->_config['persistent']);
        } else {
            $this->_Memcached = new BaseMemcached();
        }
        $this->_setOptions();

        $serverList = $this->_Memcached->getServerList();
        if ($serverList) {
            if ($this->_Memcached->isPersistent()) {
                foreach ($serverList as $server) {
                    if (! in_array($server['host'] . ':' . $server['port'], $this->_config['servers'], true)) {
                        throw new InvalidArgumentException(
                            'Configuration du cache invalide. Plusieurs configurations de cache persistant sont détectées' .
                            ' avec des valeurs `servers` différentes. `valeurs` des serveurs pour les configurations de cache persistant' .
                            ' doit être le même lors de l\'utilisation du même identifiant de persistance.'
                        );
                    }
                }
            }

            return true;
        }

        $servers = [];

        foreach ($this->_config['servers'] as $server) {
            $servers[] = $this->parseServerString($server);
        }

        if (! $this->_Memcached->addServers($servers)) {
            return false;
        }

        if (is_array($this->_config['options'])) {
            foreach ($this->_config['options'] as $opt => $value) {
                $this->_Memcached->setOption($opt, $value);
            }
        }

        if (empty($this->_config['username']) && ! empty($this->_config['login'])) {
            throw new InvalidArgumentException(
                'Veuillez passer "nom d\'utilisateur" au lieu de "login" pour vous connecter à Memcached'
            );
        }

        if ($this->_config['username'] !== null && $this->_config['password'] !== null) {
            if (! method_exists($this->_Memcached, 'setSaslAuthData')) {
                throw new InvalidArgumentException(
                    "L'extension Memcached n'est pas construite avec le support SASL"
                );
            }
            $this->_Memcached->setOption(BaseMemcached::OPT_BINARY_PROTOCOL, true);
            $this->_Memcached->setSaslAuthData(
                $this->_config['username'],
                $this->_config['password']
            );
        }

        return true;
    }

    /**
     * Paramétrage de l'instance memcached
     *
     * @throws InvalidArgumentException Lorsque l'extension Memcached n'est pas construite avec le moteur de sérialisation souhaité.
     */
    protected function _setOptions(): void
    {
        $this->_Memcached->setOption(BaseMemcached::OPT_LIBKETAMA_COMPATIBLE, true);

        $serializer = strtolower($this->_config['serialize']);
        if (! isset($this->_serializers[$serializer])) {
            throw new InvalidArgumentException(
                sprintf('%s n\'est pas un moteur de sérialisation valide pour Memcached', $serializer)
            );
        }

        if (
            $serializer !== 'php'
            && ! constant('Memcached::HAVE_' . strtoupper($serializer))
        ) {
            throw new InvalidArgumentException(
                sprintf('L\'extension Memcached n\'est pas compilée avec la prise en charge de %s', $serializer)
            );
        }

        $this->_Memcached->setOption(
            BaseMemcached::OPT_SERIALIZER,
            $this->_serializers[$serializer]
        );

        // Check for Amazon ElastiCache instance
        if (
            defined('Memcached::OPT_CLIENT_MODE')
            && defined('Memcached::DYNAMIC_CLIENT_MODE')
        ) {
            $this->_Memcached->setOption(
                BaseMemcached::OPT_CLIENT_MODE,
                BaseMemcached::DYNAMIC_CLIENT_MODE
            );
        }

        $this->_Memcached->setOption(
            BaseMemcached::OPT_COMPRESSION,
            (bool) $this->_config['compress']
        );
    }

    /**
     * Analyse l'adresse du serveur dans l'hôte/port. Gère à la fois les adresses IPv6 et IPv4 et sockets Unix
     *
     * @param string $server La chaîne d'adresse du serveur.
     *
     * @return array Tableau contenant l'hôte, le port
     */
    public function parseServerString(string $server): array
    {
        $socketTransport = 'unix://';
        if (strpos($server, $socketTransport) === 0) {
            return [substr($server, strlen($socketTransport)), 0];
        }
        if (substr($server, 0, 1) === '[') {
            $position = strpos($server, ']:');
            if ($position !== false) {
                $position++;
            }
        } else {
            $position = strpos($server, ':');
        }
        $port = 11211;
        $host = $server;
        if ($position !== false) {
            $host = substr($server, 0, $position);
            $port = substr($server, $position + 1);
        }

        return [$host, (int) $port];
    }

    /**
     * Lire une valeur d'option à partir de la connexion memcached.
     *
     * @return bool|int|string|null
     *
     * @see https://secure.php.net/manual/en/memcached.getoption.php
     */
    public function getOption(int $name)
    {
        return $this->_Memcached->getOption($name);
    }

    /**
     * {@inheritDoc}
     *
     * @see https://www.php.net/manual/en/memcached.set.php
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $duration = $this->duration($ttl);

        return $this->_Memcached->set($this->_key($key), $value, $duration);
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $cacheData = [];

        foreach ($values as $key => $value) {
            $cacheData[$this->_key($key)] = $value;
        }
        $duration = $this->duration($ttl);

        return $this->_Memcached->setMulti($cacheData, $duration);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key   = $this->_key($key);
        $value = $this->_Memcached->get($key);
        if ($this->_Memcached->getResultCode() === BaseMemcached::RES_NOTFOUND) {
            return $default;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @return array Un tableau contenant, pour chacune des $keys données, les données mises en cache ou false si les données mises en cache n'ont pas pu être récupérées.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $cacheKeys = [];

        foreach ($keys as $key) {
            $cacheKeys[$key] = $this->_key($key);
        }

        $values = $this->_Memcached->getMulti($cacheKeys);
        $return = [];

        foreach ($cacheKeys as $original => $prefixed) {
            $return[$original] = $values[$prefixed] ?? $default;
        }

        return $return;
    }

    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $offset = 1)
    {
        return $this->_Memcached->increment($this->_key($key), $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function decrement(string $key, int $offset = 1)
    {
        return $this->_Memcached->decrement($this->_key($key), $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        return $this->_Memcached->delete($this->_key($key));
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $cacheKeys = [];

        foreach ($keys as $key) {
            $cacheKeys[] = $this->_key($key);
        }

        return (bool) $this->_Memcached->deleteMulti($cacheKeys);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $keys = $this->_Memcached->getAllKeys();
        if ($keys === false) {
            return false;
        }

        foreach ($keys as $key) {
            if (strpos($key, $this->_config['prefix']) === 0) {
                $this->_Memcached->delete($key);
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function add(string $key, mixed $value): bool
    {
        $duration = $this->_config['duration'];
        $key      = $this->_key($key);

        return $this->_Memcached->add($key, $value, $duration);
    }

	/**
	 * {@inheritDoc}
	 */
	public function info()
	{
		return $this->_Memcached->getStats();
	}

    /**
     * {@inheritDoc}
     */
    public function groups(): array
    {
        if (empty($this->_compiledGroupNames)) {
            foreach ($this->_config['groups'] as $group) {
                $this->_compiledGroupNames[] = $this->_config['prefix'] . $group;
            }
        }

        $groups = $this->_Memcached->getMulti($this->_compiledGroupNames) ?: [];
        if (count($groups) !== count($this->_config['groups'])) {
            foreach ($this->_compiledGroupNames as $group) {
                if (! isset($groups[$group])) {
                    $this->_Memcached->set($group, 1, 0);
                    $groups[$group] = 1;
                }
            }
            ksort($groups);
        }

        $result = [];
        $groups = array_values($groups);

        foreach ($this->_config['groups'] as $i => $group) {
            $result[] = $group . $groups[$i];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function clearGroup(string $group): bool
    {
        return (bool) $this->_Memcached->increment($this->_config['prefix'] . $group);
    }
}
