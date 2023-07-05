<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Routes;

use BlitzPHP\Container\Services;
use BlitzPHP\Router\Router;

/**
 * Trouve des middlewares.
 */
final class MiddlewareFinder
{
    private Router $router;

    public function __construct(?Router $router = null)
    {
        $this->router  = $router ?? Services::router();
    }

    /**
     * @param string $uri Chemin URI pour trouver des middlewares
     *
     * @return array Tableau d'alias de middleware ou de nom de classe
     */
    public function find(string $uri): array
    {
        $this->router->handle($uri);

        return $this->router->getMiddlewares();
    }
}
