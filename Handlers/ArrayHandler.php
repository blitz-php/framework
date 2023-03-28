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

/**
 * Moteur de stockage de tableau pour le cache.
 *
 * Pas réellement un moteur de cache persistant. Toutes les données sont uniquement
 * stocké en mémoire pour la durée d'un seul processus. Bien que non
 * utile dans les paramètres de production ce moteur peut être utile dans les tests
 * ou des outils de console où vous ne voulez pas les frais généraux d'interaction
 * avec un serveur de cache, mais souhaitez que les propriétés d'enregistrement du travail soient un cache
 * fournit.
 */
class ArrayHandler extends BaseHandler
{
    /**
     * Données mises en cache.
     *
     * Structuré comme [clé => [exp => expiration, val => valeur]]
     */
    protected array $data = [];

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $key              = $this->_key($key);
        $expires          = time() + $this->duration($ttl);
        $this->data[$key] = ['exp' => $expires, 'val' => $value];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->_key($key);
        if (! isset($this->data[$key])) {
            return $default;
        }
        $data = $this->data[$key];

        // Verifie l'expiration
        $now = time();
        if ($data['exp'] <= $now) {
            unset($this->data[$key]);

            return $default;
        }

        return $data['val'];
    }

    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $offset = 1)
    {
        if ($this->get($key) === null) {
            $this->set($key, 0);
        }
        $key = $this->_key($key);
        $this->data[$key]['val'] += $offset;

        return $this->data[$key]['val'];
    }

    /**
     * {@inheritDoc}
     */
    public function decrement(string $key, int $offset = 1)
    {
        if ($this->get($key) === null) {
            $this->set($key, 0);
        }
        $key = $this->_key($key);
        $this->data[$key]['val'] -= $offset;

        return $this->data[$key]['val'];
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $key = $this->_key($key);
        unset($this->data[$key]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $this->data = [];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function groups(): array
    {
        $result = [];

        foreach ($this->_config['groups'] as $group) {
            $key = $this->_config['prefix'] . $group;
            if (! isset($this->data[$key])) {
                $this->data[$key] = ['exp' => PHP_INT_MAX, 'val' => 1];
            }
            $value    = $this->data[$key]['val'];
            $result[] = $group . $value;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function clearGroup(string $group): bool
    {
        $key = $this->_config['prefix'] . $group;
        if (isset($this->data[$key])) {
            $this->data[$key]['val']++;
        }

        return true;
    }
}
