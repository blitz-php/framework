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
use DI\NotFoundException;

abstract class Facade
{
    /**
     * Cache des instances résolues
     */
    protected static array $resolvedInstances = [];

    abstract protected static function accessor(): object|string;

    /**
     * Récupère l'instance résolue
     */
    protected static function getResolvedInstance(): object
    {
        $class = static::class;

        if (!isset(static::$resolvedInstances[$class])) {
            static::$resolvedInstances[$class] = static::resolveFacadeInstance();
        }

        return static::$resolvedInstances[$class];
    }

    /**
     * Résout l'instance de la facade
     */
    protected static function resolveFacadeInstance(): object
    {
        $accessor = static::accessor();

        if (is_string($accessor)) {
            try {
                $accessor = service($accessor);
            } catch (NotFoundException $e) {
                throw new InvalidArgumentException(sprintf(
                    'Impossible de résoudre le service "%s" pour la facade %s. %s',
                    $accessor,
                    static::class,
                    $e->getMessage()
                ), 0, $e);
            }
        }

        if (! is_object($accessor)) {
            throw new InvalidArgumentException(sprintf(
                'La méthode `%s::accessor` doit retourner un object ou le nom d\'un service. Type "%s" reçu.',
                static::class,
                gettype($accessor)
            ));
        }

        return $accessor;
    }

    /**
     * Vérifie si l'instance est déjà résolue
     */
    public static function isResolved(): bool
    {
        return isset(static::$resolvedInstances[static::class]);
    }

    /**
     * Efface l'instance résolue du cache
     */
    public static function clearResolvedInstance(): void
    {
        unset(static::$resolvedInstances[static::class]);
    }

    /**
     * Efface toutes les instances résolues
     */
    public static function clearResolvedInstances(): void
    {
        static::$resolvedInstances = [];
    }

    public static function __callStatic(string $name, array $arguments = [])
    {
        $instance = static::getResolvedInstance();

        return $instance->{$name}(...$arguments);
    }

    public function __call(string $name, array $arguments = [])
    {
        return static::__callStatic($name, $arguments);
    }
}
