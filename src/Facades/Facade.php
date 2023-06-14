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

abstract class Facade
{
    abstract protected static function accessor(): object;

    public static function __callStatic(string $name, array $arguments = [])
    {
        return static::accessor()->{$name}(...$arguments);
    }

    public function __call(string $name, array $arguments = [])
    {
        return static::__callStatic($name, $arguments);
    }
}
