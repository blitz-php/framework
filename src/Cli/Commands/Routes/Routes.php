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

use BlitzPHP\Cli\Commands\Utilities\Routes\AutoRouteCollector;
use BlitzPHP\Cli\Commands\Utilities\Routes\MiddlewareCollector;
use BlitzPHP\Cli\Commands\Utilities\Routes\SampleURIGenerator;
use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Loader\Services;
use BlitzPHP\Utilities\Helpers;
use Closure;

/**
 * Répertorie toutes les routes.
 * Cela inclura tous les fichiers Routes qui peuvent être découverts, et inclura les routes qui ne sont pas définies
 * dans les fichiers de routes, mais sont plutôt découverts via le routage automatique.
 */
class Routes extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    /**
     * @var string Nom
     */
    protected $name = 'route:list';

    /**
     * @var string Description
     */
    protected $description = 'Affiche toutes les routes.';

    /**
     * @var string
     */
    protected $service = 'Service de routing';

    /**
     * Les options de la commande.
     *
     * @var array<string, string>
     */
    protected $options = [
        '-h' => 'Trier par gestionnaire..',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $sortByHandler = $this->option('h', false);

        $collection = Services::routes()->loadRoutes();
        $methods    = [
            'get',
            'head',
            'post',
            'patch',
            'put',
            'delete',
            'options',
            'trace',
            'connect',
            'cli',
        ];

        $tbody               = [];
        $uriGenerator        = new SampleURIGenerator($collection);
        $middlewareCollector = new MiddlewareCollector();

        foreach ($methods as $method) {
            $routes = $collection->getRoutes($method);

            foreach ($routes as $route => $handler) {
                if (is_string($handler) || $handler instanceof Closure) {
                    $sampleUri = $uriGenerator->get($route);
                    $filters   = $middlewareCollector->get($method, $sampleUri);

                    if ($handler instanceof Closure) {
                        $handler = '(Closure)';
                    }

                    $routeName = $collection->getRoutesOptions($route)['as'] ?? '»';

                    $tbody[] = [
                        strtoupper($method),
                        $route,
                        $routeName,
                        $handler,
                        implode(' ', array_map([Helpers::class, 'classBasename'], $filters)),
                    ];
                }
            }
        }

        if ($collection->shouldAutoRoute()) {
            $autoRouteCollector = new AutoRouteCollector(
                $collection->getDefaultNamespace(),
                $collection->getDefaultController(),
                $collection->getDefaultMethod()
            );

            $autoRoutes = $autoRouteCollector->get();

            foreach ($autoRoutes as &$routes) {
                // Il n'y a pas de méthode "auto", mais il est intentionnel de ne pas obtenir de middlewares de route.
                $filters = $middlewareCollector->get('auto', $uriGenerator->get($routes[1]));

                $routes[] = implode(' ', array_map([Helpers::class, 'classBasename'], $filters));
            }

            $tbody = [...$tbody, ...$autoRoutes];
        }

        // Trier par gestionnaire.
        if ($sortByHandler) {
            usort($tbody, static fn ($handler1, $handler2) => strcmp($handler1[3], $handler2[3]));
        }

        $table = [];

        foreach ($tbody as $route) {
            $table[] = [
                'Methode'                                          => $route[0],
                'Route'                                            => $route[1],
                'Nom'                                              => $route[2],
                $sortByHandler ? 'Gestionnaire ↓' : 'Gestionnaire' => $route[3],
                'Middlewares'                                      => $route[4],
            ];
        }

        $this->table($table);
    }
}
