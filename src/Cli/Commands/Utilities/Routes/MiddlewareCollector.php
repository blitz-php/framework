<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Utilities\Routes;

use BlitzPHP\Http\Request;
use BlitzPHP\Loader\Services;
use BlitzPHP\Router\Router;

/**
 * Collecte les middlewares pour une route
 */
final class MiddlewareCollector
{
    /**
     * @param boolean $resetRoutes Indique s'il faut réinitialiser les routes définies. S'il est défini sur true, les middlewares de routage sont introuvables.
     */
    public function __construct(private bool $resetRoutes = false)
    {
    }

    /**
     * @param string $method Methode HTTP
     * @param string $uri    Chemin URI pour trouver des middlewares
     *
     * @return array{before: list<string>, after: list<string>} tableau d'alias de middleware ou de nom de classe
     */
    public function get(string $method, string $uri): array
    {
        if ($method === 'cli') {
            return [];
        }

        $request = Services::request(false)->withMethod($method);

        $router  = $this->createRouter($request);

        $finder = new MiddlewareFinder($router);

        return $finder->find($uri);
    }

    private function createRouter(Request $request): Router
    {
        $routes = Services::routes();

        if ($this->resetRoutes) {
            $routes->resetRoutes();
        }

        return new Router($routes, $request);
    }
}
