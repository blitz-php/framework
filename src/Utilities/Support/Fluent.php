<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Utilities\Support;

use ArrayAccess;
use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Contracts\Support\Jsonable;
use Closure;
use JsonSerializable;

/**
 * Definition des colonnes de la struture de migrations
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @credit 		<a href="https://laravel.com">Laravel - Illuminate\Support\Fluent</a>
 */
class Fluent implements Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    /**
     * Tous les attributs de l'instance fluent.
     *
     * @var array<TKey, TValue>
     */
    protected $attributes = [];

    /**
     * @param iterable<TKey, TValue> $attributes
     *
     * @return void
     */
    public function __construct($attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Recupere un attribut de l'instance.
     *
     * @template TGetDefault
     *
     * @param TKey $key
     * @param  TGetDefault|(\Closure(): TGetDefault)  $default
     *
     * @return TGetDefault|TValue
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * Recupere les attributs de l'instance
     *
     * @return array<TKey, TValue>
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Converti l'instance en tableeau.
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Converti l'instance en JSON serialisable.
     *
     * @return array<TKey, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convertie l'instance en json.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Verifie si une valeur existe a la position donnee
     *
     * @param TKey $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Recupere une valeur a la position donnee.
     *
     * @param TKey $offset
     *
     * @return TValue|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Modifie une valeur a la position donnee.
     *
     * @param TKey   $offset
     * @param TValue $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Supprime une valeur a la position donnee.
     *
     * @param TKey $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Appel dynamique d'une methode pour modifier un attribut.
     *
     * @param TKey              $method
     * @param array{0: ?TValue} $parameters
     *
     * @return $this
     */
    public function __call($method, $parameters)
    {
        $this->attributes[$method] = count($parameters) > 0 ? reset($parameters) : true;

        return $this;
    }

    /**
     * Recuperation dynamique d'un attribut
     *
     * @param TKey $key
     *
     * @return TValue|null
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Modification dynamique d'une valeur de l'attribue.
     *
     * @param TKey   $key
     * @param TValue $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Verifie dynamiquement si l'attribut existe
     *
     * @param TKey $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Suppression dynamique d'attributs
     *
     * @param TKey $key
     *
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
}
