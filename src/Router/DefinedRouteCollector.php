<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Router;

use Closure;

/**
 * Collecter tous les itinéraires définis pour affichage.
 */
final class DefinedRouteCollector
{
    private RouteCollection $routeCollection;

    /**
     * Routes deja collectees (pour eviter de faire la meme chose plusieurs fois)
     */
    private array $cachedRoutes = [];

    public function __construct(RouteCollection $routes)
    {
        $this->routeCollection = $routes;
    }

    /**
     * Collecte les routes enregistrees
     */
    public function collect(bool $reset = true): array
    {
        if (! $reset && $this->cachedRoutes !== []) {
            return $this->cachedRoutes;
        }

        $methods = Router::HTTP_METHODS;

        $definedRoutes = [];

        foreach ($methods as $method) {
            $routes = $this->routeCollection->getRoutes($method);

            foreach ($routes as $route => $handler) {
                if (is_string($handler) || $handler instanceof Closure) {
                    if ($handler instanceof Closure) {
                        $view = $this->routeCollection->getRoutesOptions($route, $method)['view'] ?? false;

                        $handler = $view ? '(View) ' . $view : '(Closure)';
                    }

                    $routeName = $this->routeCollection->getRoutesOptions($route, $method)['as'] ?? $route;

                    $definedRoutes[] = [
                        'method'  => $method,
                        'route'   => $route,
                        'name'    => $routeName,
                        'handler' => $handler,
                    ];
                }
            }
        }

        return $this->cachedRoutes = $definedRoutes;
    }
}
