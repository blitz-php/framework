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
use Psr\Http\Server\MiddlewareInterface;

abstract class BaseMiddleware implements MiddlewareInterface
{
    /**
     * Liste des arguments envoyes au middleware
     */
    protected array $arguments = [];

    /**
     * Chemin url de la requette actuelle
     */
    protected string $path;

    public function init(array $arguments = []): self
    {
        $this->path = $arguments['path'] ?: '/';
        unset($arguments['path']);

        $this->arguments = $arguments;

        foreach ($this->arguments as $argument => $value) {
            $method = Text::camel('set_' . $argument);
            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }           
        }

        return $this;
    }
}