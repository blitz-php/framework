<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Router\DefinedRouteCollector;
use BlitzPHP\Router\RouteCollection;

describe('DefinedRouteCollector', function (): void {
    beforeAll(function (): void {
        $this->getCollector = static function (array $config = [], array $files = []): RouteCollection {
            $defaults = ['App' => APP_PATH];
            $config   = array_merge($config, $defaults);

            service('autoloader')->addNamespace($config);

            $loader = service('locator');

            $routing = (object) config('routing');

            return new RouteCollection($loader, $routing);
        };
    });

    it('Test de collection', function (): void {
        $routes = $this->getCollector();
        $routes->get('journals', 'Blogs');
        $routes->get('product/(:num)', 'Catalog::productLookupByID/$1');
        $routes->get('feed', static fn () => 'A Closure route.');
        $routes->view('about', 'pages/about');

        $collector = new DefinedRouteCollector($routes);

        $definedRoutes = [];

        foreach ($collector->collect() as $route) {
            $definedRoutes[] = $route;
        }

        $expected = [
            [
                'method'  => 'GET',
                'route'   => 'journals',
                'name'    => 'journals',
                'handler' => '\App\Controllers\Blogs',
            ],
            [
                'method'  => 'GET',
                'route'   => 'product/([0-9]+)',
                'name'    => 'product/([0-9]+)',
                'handler' => '\App\Controllers\Catalog::productLookupByID/$1',
            ],
            [
                'method'  => 'GET',
                'route'   => 'feed',
                'name'    => 'feed',
                'handler' => '(Closure)',
            ],
            [
                'method'  => 'GET',
                'route'   => 'about',
                'name'    => 'about',
                'handler' => '(View) pages/about',
            ],
        ];

        expect($definedRoutes)->toBe($expected);
    });

    it('Test de collection avec les verbes differents', function (): void {
        $routes = $this->getCollector();
        $routes->get('login', 'AuthController::showLogin', ['as' => 'loginShow']);
        $routes->post('login', 'AuthController::login', ['as' => 'login']);
        $routes->get('logout', 'AuthController::logout', ['as' => 'logout']);

        $collector = new DefinedRouteCollector($routes);

        $definedRoutes = [];

        foreach ($collector->collect() as $route) {
            $definedRoutes[] = $route;
        }

        $expected = [
            [
                'method'  => 'GET',
                'route'   => 'login',
                'name'    => 'loginShow',
                'handler' => '\\App\\Controllers\\AuthController::showLogin',
            ],
            [
                'method'  => 'GET',
                'route'   => 'logout',
                'name'    => 'logout',
                'handler' => '\\App\\Controllers\\AuthController::logout',
            ],
            [
                'method'  => 'POST',
                'route'   => 'login',
                'name'    => 'login',
                'handler' => '\\App\\Controllers\\AuthController::login',
            ],
        ];

        expect($expected)->toBe($definedRoutes);
    });

    it('Test de collection avec l\'option $reset = false', function (): void {
        $routes = $this->getCollector();
        $routes->get('journals', 'Blogs');
        $routes->get('product/(:num)', 'Catalog::productLookupByID/$1');
        $routes->get('feed', static fn () => 'A Closure route.');
        $routes->view('about', 'pages/about');

        $collector = new DefinedRouteCollector($routes);

		$expected = [
            [
                'method'  => 'GET',
                'route'   => 'journals',
                'name'    => 'journals',
                'handler' => '\App\Controllers\Blogs',
            ],
            [
                'method'  => 'GET',
                'route'   => 'product/([0-9]+)',
                'name'    => 'product/([0-9]+)',
                'handler' => '\App\Controllers\Catalog::productLookupByID/$1',
            ],
            [
                'method'  => 'GET',
                'route'   => 'feed',
                'name'    => 'feed',
                'handler' => '(Closure)',
            ],
            [
                'method'  => 'GET',
                'route'   => 'about',
                'name'    => 'about',
                'handler' => '(View) pages/about',
            ],
        ];

        $definedRoutes = [];

        foreach ($collector->collect() as $route) {
            $definedRoutes[] = $route;
        }

        expect($definedRoutes)->toBe($expected);

		$definedRoutes = [];

        foreach ($collector->collect(false) as $route) {
            $definedRoutes[] = $route;
        }

        expect($definedRoutes)->toBe($expected);
    });
});
