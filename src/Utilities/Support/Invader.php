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

use ReflectionClass;

/**
 * Cette classe offre une fonction d'envahissement qui vous permettra de lire/écrire les propriétés privées d'un objet.
 * Elle vous permettra également de définir, d'obtenir et d'appeler des méthodes privées.
 *
 * @credit <a href="https://github.com/spatie/invade/blob/main/src/Invader.php">Spatie - Invade</a>
 *
 * @template T of object
 *
 * @mixin T
 */
class Invader
{
    /**
     * @var T
     */
    public object $obj;

    public ReflectionClass $reflected;

    /**
     * @param T $obj
     */
    public function __construct(object $obj)
    {
        $this->obj       = $obj;
        $this->reflected = new ReflectionClass($obj);
    }

    /**
     * @param T $obj
     *
     * @return T
     */
    public static function make(object $obj)
    {
        return new self($obj);
    }

    public function __get(string $name): mixed
    {
        $property = $this->reflected->getProperty($name);

        $property->setAccessible(true);

        return $property->getValue($this->obj);
    }

    public function __set(string $name, mixed $value): void
    {
        $property = $this->reflected->getProperty($name);

        $property->setAccessible(true);

        $property->setValue($this->obj, $value);
    }

    public function __call(string $name, array $params = []): mixed
    {
        $method = $this->reflected->getMethod($name);

        $method->setAccessible(true);

        return $method->invoke($this->obj, ...$params);
    }
}
