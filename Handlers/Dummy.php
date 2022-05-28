<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cache\Handler;

/**
 * Moteur de cache nul, toutes les opérations semblent fonctionner, mais ne font rien.
 *
 * Ceci est utilisé en interne lorsque Cache::disable() a été appelé.
 */
class Dummy extends BaseHandler
{
    /**
     * {@inheritDoc}
     */
    public function isSupported(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function init(array $config = []): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $offset = 1)
    {
        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function decrement(string $key, int $offset = 1)
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($keys): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clearGroup(string $group): bool
    {
        return true;
    }
}
