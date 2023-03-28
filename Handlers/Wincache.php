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
use RuntimeException;

/**
 * Moteur de stockage Wincache pour le cache
 *
 * Prend en charge Wincache 1.1.0 et supérieur.
 */
class Wincache extends BaseHandler
{
    /**
     * Contient les noms de groupe compilés
     * (préfixé par le préfixe de configuration global)
     */
    protected array $_compiledGroupNames = [];

    /**
     * {@inheritDoc}
     */
    public function init(array $config = []): bool
    {
        if (! extension_loaded('wincache')) {
            throw new RuntimeException('L\'extension `wincache` doit être activée pour utiliser WincacheHandler.');
        }

        parent::init($config);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $key      = $this->_key($key);
        $duration = $this->duration($ttl);

        return wincache_ucache_set($key, $value, $duration);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = wincache_ucache_get($this->_key($key), $success);
        if ($success === false) {
            return $default;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $offset = 1)
    {
        $key = $this->_key($key);

        return wincache_ucache_inc($key, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function decrement(string $key, int $offset = 1)
    {
        $key = $this->_key($key);

        return wincache_ucache_dec($key, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $key = $this->_key($key);

        return wincache_ucache_delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $info      = wincache_ucache_info();
        $cacheKeys = $info['ucache_entries'];
        unset($info);

        foreach ($cacheKeys as $key) {
            if (strpos($key['key_name'], $this->_config['prefix']) === 0) {
                wincache_ucache_delete($key['key_name']);
            }
        }

        return true;
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

        $groups = wincache_ucache_get($this->_compiledGroupNames);
        if (count($groups) !== count($this->_config['groups'])) {
            foreach ($this->_compiledGroupNames as $group) {
                if (! isset($groups[$group])) {
                    wincache_ucache_set($group, 1);
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
        $success = false;
        wincache_ucache_inc($this->_config['prefix'] . $group, 1, $success);

        return $success;
    }
}
