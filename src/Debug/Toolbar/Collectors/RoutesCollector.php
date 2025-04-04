<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug\Toolbar\Collectors;

use BlitzPHP\Router\DefinedRouteCollector;
use BlitzPHP\Router\Router;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Collecteur de routes pour la barre d'outils de débogage
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\Routes</a>
 */
class RoutesCollector extends BaseCollector
{
    /**
     * {@inheritDoc}
     */
    protected bool $hasTimeline = false;

    /**
     * {@inheritDoc}
     */
    protected bool $hasTabContent = true;

    /**
     * {@inheritDoc}
     */
    protected string $title = 'Routes';

    private readonly DefinedRouteCollector $definedRouteCollector;
    private readonly Router $router;
    private bool $isAutoRoute = false;

    public function __construct()
    {
        $rawRoutes                   = service('routes');
        $this->router                = service('router', $rawRoutes, null);
        $this->definedRouteCollector = new DefinedRouteCollector($rawRoutes);
        $this->isAutoRoute           = $rawRoutes->shouldAutoRoute();
    }

    /**
     * {@inheritDoc}
     *
     * @return array{
     *      matchedRoute: array<array{
     *          directory: string,
     *          controller: string,
     *          method: string,
     *          paramCount: int,
     *          truePCount: int,
     *          params: list<array{
     *              name: string,
     *              value: mixed
     *          }>
     *      }>,
     *      routes: list<array{
     *          method: string,
     *          route: string,
     *          handler: string
     *      }>
     * }
     *
     * @throws ReflectionException
     */
    public function display(): array
    {
        // Récupère nos paramètres
        // Route sous forme de callback
        if (is_callable($this->router->controllerName())) {
            $method = new ReflectionFunction($this->router->controllerName());
        } else {
            try {
                $method = new ReflectionMethod($this->router->controllerName(), $this->router->methodName());
            } catch (ReflectionException) {
                // Si nous sommes ici, la méthode n'existe pas
                // et est probablement calculé dans _remap.
                $method = new ReflectionMethod($this->router->controllerName(), '_remap');
            }
        }

        $rawParams = $method->getParameters();

        $params = [];

        foreach ($rawParams as $key => $param) {
            $params[] = [
                'name'  => '$' . $param->getName() . ' = ',
                'value' => $this->router->params()[$key] ??
                    ' <empty> | default: '
                    . var_export(
                        $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                        true
                    ),
            ];
        }

        $matchedRoute = [
            [
                'directory'  => $this->router->directory(),
                'controller' => is_string($controller = $this->router->controllerName()) ? $controller : 'Non défini',
                'method'     => is_string($controller) ? $this->router->methodName() : 'Non définie',
                'paramCount' => count($this->router->params()),
                'truePCount' => count($params),
                'params'     => $params,
            ],
        ];

        // Routes définies
        $routes = [];

        foreach ($this->definedRouteCollector->collect(false) as $route) {
            // filtre pour les chaînes, car les rappels ne sont pas affichable
            if ($route['handler'] !== '(Closure)') {
                $routes[] = [
                    'method'  => strtoupper($route['method']),
                    'route'   => $route['route'],
                    'name'    => $route['name'],
                    'handler' => $route['handler'],
                ];
            }
        }

        return [
            'matchedRoute' => $matchedRoute,
            'routes'       => $routes,
            'autoRoute'    => $this->isAutoRoute ? 'Activé' : 'Désactivé',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getBadgeValue(): int
    {
        $count = 0;

        foreach ($this->definedRouteCollector->collect(false) as $route) {
            if ($route['handler'] !== '(Closure)') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * {@inheritDoc}
     *
     * Icon from https://icons8.com - 1em package
     */
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAFDSURBVEhL7ZRNSsNQFIUjVXSiOFEcuQIHDpzpxC0IGYeE/BEInbWlCHEDLsSiuANdhKDjgm6ggtSJ+l25ldrmmTwIgtgDh/t37r1J+16cX0dRFMtpmu5pWAkrvYjjOB7AETzStBFW+inxu3KUJMmhludQpoflS1zXban4LYqiO224h6VLTHr8Z+z8EpIHFF9gG78nDVmW7UgTHKjsCyY98QP+pcq+g8Ku2s8G8X3f3/I8b038WZTp+bO38zxfFd+I6YY6sNUvFlSDk9CRhiAI1jX1I9Cfw7GG1UB8LAuwbU0ZwQnbRDeEN5qqBxZMLtE1ti9LtbREnMIuOXnyIf5rGIb7Wq8HmlZgwYBH7ORTcKH5E4mpjeGt9fBZcHE2GCQ3Vt7oTNPNg+FXLHnSsHkw/FR+Gg2bB8Ptzrst/v6C/wrH+QB+duli6MYJdQAAAABJRU5ErkJggg==';
    }
}
