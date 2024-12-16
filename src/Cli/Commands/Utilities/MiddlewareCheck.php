<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Utilities;

use BlitzPHP\Cli\Commands\Routes\MiddlewareCollector;
use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Container\Services;

/**
 * verifie les middleware d'une route.
 */
class MiddlewareCheck extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    /**
     * @var string Nom
     */
    protected $name = 'middleware:check';

    /**
     * @var string Description
     */
    protected $description = 'Vérifiez les middleware d\'une route.';

    protected $service = 'Service de configuration';

    /**
     * Arguments de la commande
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'method' => 'La methode HTTP. get, post, put, etc.',
        'route'  => 'La route (chemin d\'URI) pour vérifier les middlewares.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $method = strtolower($this->argument('method', $params[0] ?? ''));
        $route  = $this->argument('route', $params[1] ?? '');

        if (empty($route) || $method === '') {
            $this->fail('Vous devez spécifier un verbe HTTP et une route.')->eol();
            $this->write('Exemple: middleware:check GET /')->eol();
            $this->write('         middleware:check PUT products/1');

            return EXIT_ERROR;
        }

        // Chargement des routes
        Services::routes()->loadRoutes();

        $middlewareCollector = new MiddlewareCollector();

        $middlewares = $middlewareCollector->get($method, $route);

        // PageNotFoundException
        if ($middlewares === ['<unknown>']) {
            $this->fail('Impossible de trouver une route: ');
            $this->colorize('"' . strtoupper($method) . ' ' . $route . '"', 'black');

            return EXIT_ERROR;
        }

        $this->table([
            [
                'Méthode'     => strtoupper($method),
                'Route'       => $route,
                'Middlewares' => implode(' ', $middlewares),
            ],
        ]);

        return EXIT_SUCCESS;
    }
}
