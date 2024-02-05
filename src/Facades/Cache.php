<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Facades;

use BlitzPHP\Container\Services;

/**
 * @method static bool write(string $key, mixed $value, DateInterval|int|null $ttl = null) Persiste les données dans le cache, référencées de manière unique par une clé avec un temps d'expiration TTL optionnel.
 * @method static bool set(string $key, mixed $value, DateInterval|int|null $ttl = null) Persiste les données dans le cache, référencées de manière unique par une clé avec un temps d'expiration TTL optionnel.
 * @method static bool writeMany(iterable $data, DateInterval|int|null $ttl = null) Écrire des données pour de nombreuses clés dans le cache.
 * @method static bool setMultiple(iterable $values, DateInterval|int|null $ttl = null) Persiste un ensemble de paires clé => valeur dans le cache, avec un TTL optionnel.
 * @method static bool setMultiple(iterable $values, DateInterval|int|null $ttl = null) Persiste un ensemble de paires clé => valeur dans le cache, avec un TTL optionnel.
 * @method static mixed read(string $key, mixed $default = null) Récupère une valeur dans le cache.
 * @method static mixed get(string $key, mixed $default = null) Récupère une valeur dans le cache.
 * @method static iterable readMany(iterable $keys, mixed $default = null) Permet d'obtenir plusieurs éléments de cache à partir de leurs clés uniques.
 * @method static iterable getMultiple(iterable $keys, mixed $default = null) Permet d'obtenir plusieurs éléments de cache à partir de leurs clés uniques.
 * @method static false|int increment(string $key, int $offset = 1) Incrémente un nombre sous la clé et renvoie la valeur incrémentée.
 * @method static false|int decrement(string $key, int $offset = 1) Décrémente un nombre sous la clé et renvoyer la valeur décrémentée.
 * @method static bool delete(string $key) Supprime un élément du cache à partir de sa clé.
 * @method static bool deleteMany(iterable $keys) Supprime plusieurs éléments du cache en une seule opération.
 * @method static bool deleteMultiple(iterable $keys) Supprime plusieurs éléments du cache en une seule opération.
 * @method static bool clear() Supprime toutes les clés du cache.
 * @method static bool clearGroup(string $group) Supprime toutes les clés du cache appartenant au même groupe.
 * @method static array|false|object|null info() Renvoie des informations sur l'ensemble du cache.
 * @method static mixed remember(string $key, callable|DateInterval|int|null $ttl, callable $callable) Fournit la possibilité de faire facilement la mise en cache de lecture.
 * @method static bool add(string $key, mixed $value) Écrit les données de la clé dans un moteur de cache si elles n'existent pas déjà.
 * @method static bool has(string $key) Détermine si un élément est présent dans le cache.
 *
 * @see \BlitzPHP\Cache\Cache
 */
final class Cache extends Facade
{
    protected static function accessor(): object
    {
        return Services::cache();
    }
}
