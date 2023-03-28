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

use APCUIterator;
use DateInterval;
use RuntimeException;

/**
 * Moteur de stockage APCu pour le cache
 */
class Apcu extends BaseHandler
{
    /**
     * Contient les noms de groupe compilés
     * (préfixé par le préfixe de configuration global)
     *
     * @var string[]
     */
    protected array $_compiledGroupNames = [];

    /**
     * {@inheritDoc}
     */
    public function init(array $config = []): bool
    {
        if (! extension_loaded('apcu')) {
            throw new RuntimeException('L\'extension `apcu` doit être activée pour utiliser ApcuHandler.');
        }

        return parent::init($config);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $key      = $this->_key($key);
        $duration = $this->duration($ttl);

        return apcu_store($key, $value, $duration);
    }

    /**
     * {@inheritDoc}
     *
     * @see https://secure.php.net/manual/en/function.apcu-fetch.php
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = apcu_fetch($this->_key($key), $success);
        if ($success === false) {
            return $default;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @see https://secure.php.net/manual/en/function.apcu-inc.php
     */
    public function increment(string $key, int $offset = 1)
    {
        $key = $this->_key($key);

        return apcu_inc($key, $offset);
    }

    /**
     * {@inheritDoc}
     *
     * @see https://secure.php.net/manual/en/function.apcu-dec.php
     */
    public function decrement(string $key, int $offset = 1)
    {
        $key = $this->_key($key);

        return apcu_dec($key, $offset);
    }

    /**
     * {@inheritDoc}
     *
     * @see https://secure.php.net/manual/en/function.apcu-delete.php
     */
    public function delete(string $key): bool
    {
        $key = $this->_key($key);

        return apcu_delete($key);
    }

    /**
     * {@inheritDoc}
     *
     * @see https://secure.php.net/manual/en/function.apcu-cache-info.php
     * @see https://secure.php.net/manual/en/function.apcu-delete.php
     */
    public function clear(): bool
    {
        if (class_exists(APCUIterator::class, false)) {
            $iterator = new APCUIterator(
                '/^' . preg_quote($this->_config['prefix'], '/') . '/',
                APC_ITER_NONE
            );
            apcu_delete($iterator);

            return true;
        }

        $cache = apcu_cache_info(); // Déclenche déjà un avertissement par lui-même

        foreach ($cache['cache_list'] as $key) {
            if (strpos($key['info'], $this->_config['prefix']) === 0) {
                apcu_delete($key['info']);
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @see https://secure.php.net/manual/en/function.apcu-add.php
     */
    public function add(string $key, mixed $value): bool
    {
        $key      = $this->_key($key);
        $duration = $this->_config['duration'];

        return apcu_add($key, $value, $duration);
    }

    /**
     * {@inheritDoc}
     *
     * @see https://secure.php.net/manual/en/function.apcu-fetch.php
     * @see https://secure.php.net/manual/en/function.apcu-store.php
     */
    public function groups(): array
    {
        if (empty($this->_compiledGroupNames)) {
            foreach ($this->_config['groups'] as $group) {
                $this->_compiledGroupNames[] = $this->_config['prefix'] . $group;
            }
        }

        $success = false;
        $groups  = apcu_fetch($this->_compiledGroupNames, $success);
        if ($success && count($groups) !== count($this->_config['groups'])) {
            foreach ($this->_compiledGroupNames as $group) {
                if (! isset($groups[$group])) {
                    $value = 1;
                    if (apcu_store($group, $value) === false) {
                        $this->warning(
                            sprintf('Impossible de stocker la clé "%s" avec la valeur "%s" dans le cache APCu.', $group, $value)
                        );
                    }
                    $groups[$group] = $value;
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
     *
     * @see https://secure.php.net/manual/en/function.apcu-inc.php
     */
    public function clearGroup(string $group): bool
    {
        $success = false;
        apcu_inc($this->_config['prefix'] . $group, 1, $success);

        return $success;
    }
}
