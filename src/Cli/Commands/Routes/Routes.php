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

use Ahc\Cli\Output\Color;
use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Container\Services;
use BlitzPHP\Http\Request;
use BlitzPHP\Router\DefinedRouteCollector;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Router\Router;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\Iterable\Arr;
use BlitzPHP\Utilities\Iterable\Collection;
use BlitzPHP\Utilities\String\Text;
use Closure;
use ReflectionClass;
use ReflectionFunction;

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
        '--host'          => 'Spécifiez nom d\'hôte dans la demande URI.',
        '--domain'        => 'Filtrer les routes par le domaine',
        '--handler'       => 'Filtrer les routes par le gestionnaire',
        '--method'        => 'Filtrer les routes par la méthode',
        '--name'          => 'Filtrer les routes par le nom',
        '--json'          => 'Produire la liste des routes au format JSON',
        '--show-stats'    => 'Afficher les statistiques de collecte de routes',
        '-r|--reverse'    => "Inverser l'ordre des routes",
        '--sort'          => ['La colonne (domain, method, uri, name, handler, middleware, definition) à trier', 'uri'],
        '--path'          => 'Afficher uniquement les routes correspondant au modèle de chemin donné',
        '--except-path'   => 'Ne pas afficher les routes correspondant au modèle de chemin donné',
        '--except-vendor' => 'Ne pas afficher les routes définis par les paquets des fournisseurs',
        '--only-vendor'   => 'Afficher uniquement les routes définis par les paquets des fournisseurs',
    ];

    /**
     * Les en-têtes du tableau pour la commande.
     *
     * @var list<string>
     */
    protected array $headers = ['Domain', 'Method', 'Route', 'Name', 'Handler', 'Middleware'];

    /**
     * @var array<string,string>
     */
    protected array $verbColors = [
        'GET'     => Color::BLUE,
        'HEAD'    => Color::CYAN,
        'OPTIONS' => Color::CYAN,
        'POST'    => Color::YELLOW,
        'PUT'     => Color::YELLOW,
        'PATCH'   => Color::YELLOW,
        'DELETE'  => Color::RED,
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        if (null !== $host = $this->option('host')) {
            Services::set(Request::class, service('request')->withHeader('HTTP_HOST', $host));
        }

        if ([] === $routes = $this->collectRoutes($collection = service('routes')->loadRoutes())) {
            $this->error("Votre application n'a pas de routes.");

            return;
        }

        $total = count($routes);

        if ([] === $routes = $this->getRoutes($routes, new SampleURIGenerator($collection))) {
            $this->error("Votre application n'a pas de routes correspondant aux critères donnés.");

            return;
        }

        $this->displayRoutes($routes, $total);
    }

    /**
     * Collecte les routes et les routes découvertes automatiquement.
     */
    protected function collectRoutes(RouteCollection $collection): array
    {
        $definedRouteCollector = new DefinedRouteCollector($collection);
        $routes                = $definedRouteCollector->collect();

        if ($collection->shouldAutoRoute()) {
            $methods = $this->option('method') ? [$this->option('method')] : Router::HTTP_METHODS;

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

            foreach ($autoRoutes as $route) {
                $routes[] = [
                    'method'     => $route[0],
                    'route'      => $route[1],
                    'name'       => $route[2],
                    'handler'    => $route[3],
                    'middleware' => $route[4],
                ];
            }
        }

        return $routes;
    }

    /**
     * Compiler les routes dans un format affichable.
     */
    protected function getRoutes(array $routes, SampleURIGenerator $uriGenerator): array
    {
        $routes = collect($routes)
            ->map(fn ($route) => $this->getRouteInformation($route, $uriGenerator, new MiddlewareCollector()))
            ->filter()
            ->all();

        if (($sort = $this->option('sort')) !== null) {
            $routes = $this->sortRoutes($sort, $routes);
        } else {
            $routes = $this->sortRoutes('route', $routes);
        }

        if ($this->option('reverse')) {
            $routes = array_reverse($routes);
        }

        return $this->pluckColumns($routes);
    }

    /**
     * Obtenir les informations relatives à une route donnée.
     */
    protected function getRouteInformation(array $route, SampleURIGenerator $uriGenerator, MiddlewareCollector $middlewareCollector): ?array
    {
        if (! isset($route['middleware'])) {
            $sampleUri           = $uriGenerator->get($route['route']);
            $middlewares         = $middlewareCollector->get($route['method'], $sampleUri);
            $route['middleware'] = implode(' ', array_map(Helpers::classBasename(...), $middlewares));
        }

        return $this->filterRoute([
            'domain'     => $route['domain'] ?? '',
            'method'     => $route['method'],
            'route'      => $route['route'],
            'uri'        => $sampleUri,
            'name'       => $route['name'],
            'handler'    => ltrim($route['handler'], '\\'),
            'middleware' => $route['middleware'],
            'vendor'     => $this->isVendorRoute($route),
        ]);
    }

    /**
     * Déterminer si la route a été définie en dehors de l'application.
     */
    protected function isVendorRoute(array $route): bool
    {
        if ($route['handler'] instanceof Closure) {
            $path = (new ReflectionFunction($route['handler']))->getFileName();
        } elseif (is_string($route['handler']) && ! (str_contains($route['handler'], '(View) ') || str_contains($route['handler'], '(Closure) '))) {
            if (! class_exists($classname = explode('::', $route['handler'])[0])) {
                return false;
            }
            $path = (new ReflectionClass($classname))->getFileName();
        } else {
            return false;
        }

        return str_starts_with($path, base_path('vendor'));
    }

    /**
     * Filtrer la route par URI et/ou nom.
     */
    protected function filterRoute(array $route): ?array
    {
        if (($this->option('name') && ! Text::contains((string) $route['name'], $this->option('name')))
            || ($this->option('handler') && isset($route['handler']) && is_string($route['handler']) && ! Text::contains($route['handler'], $this->option('handler')))
            || ($this->option('path') && ! Text::contains($route['uri'], $this->option('path')))
            || ($this->option('method') && ! Text::contains($route['method'], strtoupper($this->option('method'))))
            || ($this->option('domain') && ! Text::contains((string) $route['domain'], $this->option('domain')))
            || ($this->option('except-vendor') && $route['vendor'])
            || ($this->option('only-vendor') && ! $route['vendor'])) {
            return null;
        }

        if ($this->option('except-path')) {
            foreach (explode(',', $this->option('except-path')) as $path) {
                if (str_contains($route['uri'], $path)) {
                    return null;
                }
            }
        }

        return $route;
    }

    /**
     * Trier les routes en fonction d'un élément donné.
     */
    protected function sortRoutes(string $sort, array $routes): array
    {
        if ($sort === 'definition') {
            return $routes;
        }

        if (Text::contains($sort, ',')) {
            $sort = explode(',', $sort);
        }

        return collect($routes)->sortBy($sort)->toArray();
    }

    /**
     * Supprimer les colonnes inutiles des routes.
     */
    protected function pluckColumns(array $routes): array
    {
        return array_map(fn ($route) => Arr::only($route, $this->getColumns()), $routes);
    }

    /**
     * Obtenir les en-têtes de tableau pour les colonnes visibles.
     */
    protected function getHeaders(): array
    {
        return Arr::only($this->headers, array_keys($this->getColumns()));
    }

    /**
     * Obtenir les noms de colonnes à afficher (en-têtes de tableaux en minuscules).
     */
    protected function getColumns(): array
    {
        return array_map('strtolower', $this->headers);
    }

    /**
     * Convertir les routes donnees en JSON.
     */
    protected function asJson(Collection $routes)
    {
        $this->json(
            $routes->map(static function ($route) {
                $route['middleware'] = empty($route['middleware']) ? [] : explode(' ', $route['middleware']);

                return $route;
            })
                ->values()
                ->toArray()
        );
    }

    /**
     * Affiche les informations relatives à la route sur la console.
     *
     * @param int $total Nombre de route total collecté, indépendement des filtres appliqués
     */
    protected function displayRoutes(array $routes, int $total): void
    {
        $routes = collect($routes)->map(static fn ($route) => array_merge($route, [
            'route' => $route['domain'] ? ($route['domain'] . '/' . ltrim($route['route'], '/')) : $route['route'],
            'name'  => $route['route'] === $route['name'] ? null : $route['name'],
        ]));

        if ($this->option('json')) {
            $this->asJson($routes);

            return;
        }

        $maxMethodLength = $routes->map(static fn ($route) => strlen($route['method']))->max();

        foreach ($routes->values()->toArray() as $route) {
            $left = implode('', [
                $this->color->line(str_pad($route['method'], $maxMethodLength), ['fg' => $this->verbColors[$route['method']]]),
                ' ',
                $route['route'],
            ]);
            $right = implode(' > ', array_filter([$route['name'], $route['handler']]));

            $this->justify($left, $right, [
                'second' => ['fg' => Color::fg256(6), 'bold' => 1],
            ]);
        }

        if ($this->option('show-stats')) {
            $this->displayStats($routes, $total);
        }
    }

    /**
     * Affichage des stats de collecte des routes
     */
    protected function displayStats(Collection $routes, int $total): void
    {
        $this->eol()->border(char: '*');

        $options = ['sep' => '-', 'second' => ['fg' => Color::GREEN]];
        $this->justify('Nombre total de routes définies', (string) $total, $options);
        $this->justify('Nombre de routes affichées', (string) $routes->count(), $options);
        if (! $this->option('method')) {
            $this->border(char: '.');
            $methods = $routes->map(static fn ($route) => $route['method'])->unique()->sort()->all();

            foreach ($methods as $method) {
                $this->justify(
                    $method,
                    (string) $routes->where('method', $method)->count(),
                    $options
                );
            }
        }
        $this->border(char: '*');
    }
}
