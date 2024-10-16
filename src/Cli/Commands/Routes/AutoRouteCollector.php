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

use BlitzPHP\Utilities\Helpers;

/**
 * Collecte des données pour la liste des routes automatiques.
 */
final class AutoRouteCollector
{
    /**
     * @param string             $namespace            Namespace dans lequel on recherche
     * @param list<class-string> $protectedControllers Liste des contrôleurs dans les routes définis qui ne doivent pas être consultés via Auto-Routing.
     * @param string             $prefix               Préfixe URI pour Module Routing
     */
    public function __construct(
        private readonly string $namespace,
        private readonly string $defaultController,
        private readonly string $defaultMethod,
        private readonly array $httpMethods,
        private readonly array $protectedControllers,
        private readonly string $prefix = ''
    ) {
    }

    /**
     * @return         array<int, array<int, string>>
     * @phpstan-return list<list<string>>
     */
    public function get(): array
    {
        $finder = new ControllerFinder($this->namespace);
        $reader = new ControllerMethodReader($this->namespace, $this->httpMethods);

        $tbody = [];

        foreach ($finder->find() as $class) {
            // Exclure les contrôleurs dans les routes définies.
            if (in_array('\\' . $class, $this->protectedControllers, true)) {
                continue;
            }

            $routes = $reader->read(
                $class,
                $this->defaultController,
                $this->defaultMethod
            );

            if ($routes === []) {
                continue;
            }

            $routes = $this->addMiddlewares($routes);

            foreach ($routes as $item) {
                $route = $item['route'] . $item['route_params'];

                // pour le routing de module
                if ($this->prefix !== '' && $route === '/') {
                    $route = $this->prefix;
                } elseif ($this->prefix !== '') {
                    $route = $this->prefix . '/' . $route;
                }

                $tbody[] = [
                    strtoupper($item['method']) . '(auto)',
                    $route,
                    '',
                    $item['handler'],
                    '',
                ];
            }
        }

        return $tbody;
    }

    private function addMiddlewares($routes)
    {
        $middlewareCollector = new MiddlewareCollector(true);

        foreach ($routes as &$route) {
            $routePath = $route['route'];

            // Pour le routing de module
            if ($this->prefix !== '' && $route === '/') {
                $routePath = $this->prefix;
            } elseif ($this->prefix !== '') {
                $routePath = $this->prefix . '/' . $routePath;
            }

            // Rechercher des middlewares pour l'URI avec tous les params
            $sampleUri      = $this->generateSampleUri($route);
            $filtersLongest = $middlewareCollector->get($route['method'], $routePath . $sampleUri);

            // Rechercher des middlewares pour l'URI sans parames optionnels
            $sampleUri       = $this->generateSampleUri($route, false);
            $filtersShortest = $middlewareCollector->get($route['method'], $routePath . $sampleUri);

            // Recuperer les elements commun
            $middlewares = array_intersect($filtersLongest, $filtersShortest);

            $route['middlewares'] = implode(' ', array_map(Helpers::classBasename(...), $middlewares));
        }

        return $routes;
    }

    private function generateSampleUri(array $route, bool $longest = true): string
    {
        $sampleUri = '';

        if (isset($route['params'])) {
            $i = 1;

            foreach ($route['params'] as $required) {
                if ($longest && ! $required) {
                    $sampleUri .= '/' . $i++;
                }
            }
        }

        return $sampleUri;
    }
}
