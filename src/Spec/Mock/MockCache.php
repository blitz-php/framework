<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Spec\Mock;

use BlitzPHP\Cache\CacheInterface;
use BlitzPHP\Cache\Handlers\BaseHandler;
use BlitzPHP\Utilities\Date;
use DateInterval;

class MockCache extends BaseHandler implements CacheInterface
{
    /**
     * stockage des mocks de cache.
     *
     * @var array<string, mixed>
     */
    protected array $cache = [];

    /**
     * Temps d'expiration.
     *
     * @var int[]
     */
    protected array $expirations = [];

    /**
     * Si true, nous ne mettons aucune donnees en cache.
     */
    protected bool $bypass = false;

    /**
     * {@inheritDoc}
     *
     * @return bool|null
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->_key($key);

        return array_key_exists($key, $this->cache) ? $this->cache[$key] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, null|DateInterval|int $ttl = null): bool
    {
        if ($this->bypass) {
            return false;
        }

        $key = $this->_key($key);
		$ttl = $ttl instanceof DateInterval ? $ttl->s : $ttl;

        $this->cache[$key]       = $value;
        $this->expirations[$key] = $ttl > 0 ? Date::now()->getTimestamp() + $ttl : null;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $key = $this->_key($key);

        if (! isset($this->cache[$key])) {
            return false;
        }

        unset($this->cache[$key], $this->expirations[$key]);

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function deleteMatching(string $pattern)
    {
        $count = 0;

        foreach (array_keys($this->cache) as $key) {
            if (fnmatch($pattern, $key)) {
                $count++;
                unset($this->cache[$key], $this->expirations[$key]);
            }
        }

        return $count;
    }

    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $offset = 1)
    {
        $key  = $this->_key($key);
        $data = $this->cache[$key] ?: null;

        if ($data === null) {
            $data = 0;
        } elseif (! is_int($data)) {
            return false;
        }

		if (false !== $this->set($key, $increment = $data + $offset)) {
			return $increment;
		}

		return false;
    }

    /**
     * {@inheritDoc}
     */
    public function decrement(string $key, int $offset = 1)
    {
        $key = $this->_key($key);

        $data = $this->cache[$key] ?: null;

        if ($data === null) {
            $data = 0;
        } elseif (! is_int($data)) {
            return false;
        }

        if (false !== $this->set($key, $decrement = $data - $offset)) {
			return $decrement;
		}

		return false;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $this->cache       = [];
        $this->expirations = [];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clearGroup(string $group): bool
    {
        return $this->deleteMatching($group) > 0;
    }

    /**
     * {@inheritDoc}
     *
     * @return string[] Keys currently present in the store
     */
    public function info(): array
    {
        return array_keys($this->cache);
    }

    /**
     * Renvoie des informations détaillées sur l'élément spécifique du cache.
     *
     * @return array|null Retourne null si l'élément n'existe pas, sinon array<string, mixed>
     *                    avec au moins la clé 'expire' pour une expiration absolue (ou null).
     */
    public function getMetaData(string $key): ?array
    {
        // n'existe pas, retourne null
        if (! array_key_exists($key, $this->expirations)) {
            return null;
        }

        //  Compter les elements périmés comme un manque
        if (is_int($this->expirations[$key]) && $this->expirations[$key] > Date::now()->getTimestamp()) {
            return null;
        }

        return ['expire' => $this->expirations[$key]];
    }

    // --------------------------------------------------------------------
    // Helpers de test
    // --------------------------------------------------------------------

    /**
     * Indique à la classe d'ignorer toutes les demandes de mise en cache d'un élément,
     * et de toujours "manquer" lorsqu'on vérifie la présence de données existantes.
     */
    public function bypass(bool $bypass = true): self
    {
        $this->clear();
        $this->bypass = $bypass;

        return $this;
    }

    // --------------------------------------------------------------------
    // Additional Assertions
    // --------------------------------------------------------------------

    /**
     * Affirme que le cache possède un élément nommé $key.
     * La valeur n'est pas vérifiée puisque le stockage de valeurs fausses ou nulles est valide.
     */
    public function assertHas(string $key): void
    {
        expect($this->get($key))->not->toBeNull();
        // Assert::assertNotNull($this->get($key), "Le cache n'a pas un élément nommé: `{$key}`");
    }

    /**
     * Affirme que le cache possède un élément nommé $key dont la valeur correspond à $value.
     */
    public function assertHasValue(string $key, mixed $value = null): void
    {
        $item = $this->get($key);

        // Laissez la fonction assertHas() gérer l'erreur de cohérence si la clé n'est pas trouvée.
        if ($item === null) {
            $this->assertHas($key);
        }

        expect($this->get($key))->toBe($value);
        // Assert::assertSame($value, $this->get($key), "L'élément `{$key}` du cache ne correspond pas à la valeur attendue. Trouvée: " . print_r($value, true));
    }

    /**
     * Affirme que le cache ne possède pas un élément nommé $key.
     */
    public function assertMissing(string $key): void
    {
        expect($this->cache)->not->toContainKey($key);
        // Assert::assertArrayNotHasKey($key, $this->cache, "L'élément en cache nommé `{$key}` existe.");
    }
}
