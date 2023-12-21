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

use InvalidArgumentException;

abstract class Facade
{
    abstract protected static function accessor(): object|string;

    public static function __callStatic(string $name, array $arguments = [])
    {
        if (is_string($accessor = static::accessor())) {
            $accessor = service($accessor);
        }

        if (! is_object($accessor)) {
            throw new InvalidArgumentException(sprintf('La methode `%s::accessor` doit retourner un object ou le nom d\'un service.', static::class));
        }

        return $accessor->{$name}(...$arguments);
    }

    public function __call(string $name, array $arguments = [])
    {
        return static::__callStatic($name, $arguments);
    }
}
