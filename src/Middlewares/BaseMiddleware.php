<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Middlewares;

use BlitzPHP\Utilities\String\Text;

abstract class BaseMiddleware
{
    /**
     * Liste des arguments envoyes au middleware
     */
    protected array $arguments = [];

    /**
     * Liste des arguments que peut avoir le middleware
     */
    protected array $fillable = [];

    /**
     * Chemin url de la requette actuelle
     */
    protected string $path;

    public function init(string $path): static
    {
        $this->path = $path;

        foreach ($this->arguments as $argument => $value) {
            $this->applyArgument($argument, $value);
        }

        return $this;
    }

    /**
     * Applique un argument au middleware
     *
     * @param mixed $argument
     * @param mixed $value
     */
    protected function applyArgument($argument, $value): void
    {
        if (! is_string($argument)) {
            return;
        }

        $method = Text::camel('set_' . $argument);
        if (method_exists($this, $method)) {
            call_user_func([$this, $method], $value);
        } elseif (property_exists($this, $argument)) {
            $this->{$argument} = $value;
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    public function __get($name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * @internal
     */
    final public function fill(array $params): static
    {
        // Si c'est une liste (paramètres positionnels)
        if (array_values($params) === $params) {
            $this->fillPositional($params);
        } else {
            // Paramètres nommés
            $this->fillNamed($params);
        }

        return $this;
    }

    /**
     * Remplit avec des paramètres positionnels
     */
    protected function fillPositional(array $params): void
    {
        foreach ($this->fillable as $key) {
            if ($params === []) {
                break;
            }
            $this->arguments[$key] = array_shift($params);
            $this->applyArgument($key, $this->arguments[$key]);
        }

        // Paramètres supplémentaires
        foreach ($params as $index => $value) {
            $this->arguments[$index] = $value;
        }
    }

    /**
     * Remplit avec des paramètres nommés
     */
    protected function fillNamed(array $params): void
    {
        // D'abord, traiter les fillable
        foreach ($this->fillable as $key) {
            if (array_key_exists($key, $params)) {
                $this->arguments[$key] = $params[$key];
                $this->applyArgument($key, $params[$key]);
                unset($params[$key]);
            }
        }

        // Ensuite, les autres paramètres (pour compatibilité)
        foreach ($params as $key => $value) {
            $this->arguments[$key] = $value;
            $this->applyArgument($key, $value);
        }
    }

    /**
     * Vérifie si un argument existe
     *
     * @param mixed $name
     */
    public function has($name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    /**
     * Récupère tous les arguments
     */
    public function all(): array
    {
        return $this->arguments;
    }
}
