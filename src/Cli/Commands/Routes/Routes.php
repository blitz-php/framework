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

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Router\DefinedRouteCollector;
use BlitzPHP\Router\Router;
use BlitzPHP\Utilities\Helpers;

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
        '-h'     => 'Trier par gestionnaire.',
        '--host' => 'Spécifiez nom d\'hôte dans la demande URI.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $sortByHandler = $this->option('h', false);
        $host          = $this->option('host');

        if ($host) {
            putenv('HTTP_HOST=' . $host);
        }

        $collection = service('routes')->loadRoutes();
        $methods    = Router::HTTP_METHODS;

        $tbody                 = [];
        $uriGenerator          = new SampleURIGenerator($collection);
        $middlewareCollector   = new MiddlewareCollector();
        $definedRouteCollector = new DefinedRouteCollector($collection);

        foreach ($definedRouteCollector->collect() as $route) {
            $sampleUri = $uriGenerator->get($route['route']);
            $filters   = $middlewareCollector->get($route['method'], $sampleUri);

            $routeName = ($route['route'] === $route['name']) ? '»' : $route['name'];

            $tbody[] = [
                strtoupper($route['method']),
                $route['route'],
                $routeName,
                $route['handler'],
                implode(' ', array_map(Helpers::classBasename(...), $filters)),
            ];
        }

        if ($collection->shouldAutoRoute()) {
            $autoRouteCollector = new AutoRouteCollector(
                $collection->getDefaultNamespace(),
                $collection->getDefaultController(),
                $collection->getDefaultMethod(),
                $methods,
                $collection->getRegisteredControllers('*')
            );

            $autoRoutes = $autoRouteCollector->get();

            // Verification des routes de modules
            if ([] !== $routingConfig = config('routing')) {
                foreach ($routingConfig['module_routes'] as $uri => $namespace) {
                    $autoRouteCollector = new AutoRouteCollector(
                        $namespace,
                        $collection->getDefaultController(),
                        $collection->getDefaultMethod(),
                        $methods,
                        $collection->getRegisteredControllers('*'),
                        $uri
                    );

                    $autoRoutes = [...$autoRoutes, ...$autoRouteCollector->get()];
                }
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
                'Méthode'                                          => $route[0],
                'Route'                                            => $route[1],
                'Nom'                                              => $route[2],
                $sortByHandler ? 'Gestionnaire ↓' : 'Gestionnaire' => $route[3],
                'Middlewares'                                      => $route[4],
            ];
        }

        if ($host) {
            $this->write('Hôte: ' . $host);
        }

        $this->table($table);
    }
}
