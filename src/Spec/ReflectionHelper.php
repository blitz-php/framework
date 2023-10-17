<?php

namespace BlitzPHP\Spec;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;

/**
 * Testing helper.
 */
class ReflectionHelper
{
    /**
     * Recherchez un invocateur de méthode privée.
     *
     * @param object|string $obj objet ou nom de classe
     * @param string $method nom de la méthode
     *
     * @throws ReflectionException
     */
    public static function getPrivateMethodInvoker(object|string $obj, string $method): Closure
    {
        $refMethod = new ReflectionMethod($obj, $method);
        $refMethod->setAccessible(true);
        $obj = (gettype($obj) === 'object') ? $obj : null;

        return static fn (...$args) => $refMethod->invokeArgs($obj, $args);
    }

    /**
     * Trouvez une propriété accessible.
     *
     * @throws ReflectionException
     */
    public static function getAccessibleRefProperty(object|string $obj, string $property): ReflectionProperty
    {
        $refClass = is_object($obj) ? new ReflectionObject($obj) : new ReflectionClass($obj);

        $refProperty = $refClass->getProperty($property);
        $refProperty->setAccessible(true);

        return $refProperty;
    }

    /**
     * Définir une propriété privée.
     *
     * @param object|string $obj objet ou nom de classe
     * @param string $property nom de la propriété
     *
     * @throws ReflectionException
     */
    public static function setPrivateProperty(object|string $obj, string $property, mixed $value): void
    {
        $refProperty = self::getAccessibleRefProperty($obj, $property);

        if (is_object($obj)) {
            $refProperty->setValue($obj, $value);
        } else {
            $refProperty->setValue(null, $value);
        }
    }

    /**
     * Récupérer une propriété privée.
     *
     * @param object|string $obj objet ou nom de classe
     * @param string $property nom de la propriété
     *
     * @throws ReflectionException
     */
    public static function getPrivateProperty(object|string $obj, string $property): mixed
    {
        $refProperty = self::getAccessibleRefProperty($obj, $property);

        return is_string($obj) ? $refProperty->getValue() : $refProperty->getValue($obj);
    }
}
