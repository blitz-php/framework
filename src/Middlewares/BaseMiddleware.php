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
            if (! is_string($argument)) {
                continue;
            }

            $method = Text::camel('set_' . $argument);
            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            } elseif (property_exists($this, $argument)) {
                $this->{$argument} = $value;
            }
        }

        return $this;
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
        foreach ($this->fillable as $key) {
            if (empty($params)) {
                break;
            }
            $this->arguments[$key] = array_shift($params);
        }

        $this->arguments += $params;

        return $this;
    }
}
