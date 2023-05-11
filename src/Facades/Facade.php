<?php 

namespace BlitzPHP\Facades;

abstract class Facade 
{
    abstract protected static function accessor(): object;

    public static function __callStatic(string $name, array $arguments = [])
    {
        return static::accessor()->$name(...$arguments);
    }

    public function __call(string $name, array $arguments = [])
    {
        return static::__callStatic($name, $arguments);
    }
}
