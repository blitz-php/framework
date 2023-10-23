<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\Services;
use BlitzPHP\Http\Request;
use BlitzPHP\Router\RouteCollection;
use Spec\BlitzPHP\App\Controllers\HomeController;

function getCollector(array $config = [], array $files = []): RouteCollection
{
    $defaults = ['App' => APP_PATH];
    $config   = array_merge($config, $defaults);

    Services::autoloader()->addNamespace($config);

    $loader = Services::locator();

    $routing                    = (object) config('routing');
    $routing->default_namespace = '\\';

    return (new RouteCollection($loader, $routing))->setHTTPVerb('get');
}

function setRequestMethod(string $method): void
{
    Services::set(Request::class, Services::request()->withMethod($method));
}

describe('RouteCollection', static function () {
    beforeEach(static function () {
        // Services::reset(false);
    });

    describe('Ajout de route', static function () {
        it('Test de base', static function () {
            $routes = getCollector();
            $routes->add('home', '\my\controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\my\controller',
            ]);
        });

        it('Test de base avec un callback', static function () {
            $routes = getCollector();
            $routes->add('home', [HomeController::class, 'index']);

            expect($routes->getRoutes())->toBe([
                'home' => '\Spec\BlitzPHP\App\Controllers\HomeController::index',
            ]);
        });

        it('Test de base avec un callback et des parametres', static function () {
            $routes = getCollector();
            $routes->add('product/(:num)/(:num)', [[HomeController::class, 'index'], '$2/$1']);

            expect($routes->getRoutes())->toBe([
                'product/([0-9]+)/([0-9]+)' => '\Spec\BlitzPHP\App\Controllers\HomeController::index/$2/$1',
            ]);
        });

        it('Test de base avec un callback avec des parametres sans la chaine de definition', static function () {
            $routes = getCollector();
            $routes->add('product/(:num)/(:num)', [HomeController::class, 'index']);

            expect($routes->getRoutes())->toBe([
                'product/([0-9]+)/([0-9]+)' => '\Spec\BlitzPHP\App\Controllers\HomeController::index/$1/$2',
            ]);
        });

        it("Ajout du namespace par défaut quand il n'a pas été défini", static function () {
            $routes = getCollector();
            $routes->add('home', 'controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it("Ignorer le namespace par défaut lorsqu'il existe", static function () {
            $routes = getCollector();
            $routes->add('home', 'my\controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\my\controller',
            ]);
        });

        it('Ajout avec un slash en debut de chaine', static function () {
            $routes = getCollector();
            $routes->add('/home', 'controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });
    });

    describe('Correspondance des verbes HTTP', static function () {
        it('Match fonctionne avec la methode HTTP actuel', static function () {
            setRequestMethod('GET');

            $routes = getCollector();
            $routes->match(['get'], 'home', 'controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it('Match ignore les methodes HTTP invalide', static function () {
            setRequestMethod('GET');

            $routes = getCollector();
            $routes->match(['put'], 'home', 'controller');

            expect($routes->getRoutes())->toBe([]);
        });

        it('Add fonctionne avec un tableau de verbes HTTP', static function () {
            setRequestMethod('POST');

            $routes = getCollector();
            $routes->add('home', 'controller', ['get', 'post']);

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it('Add remplace les placeholders par defaut avec les bons regex', static function () {
            $routes = getCollector();
            $routes->add('home/(:any)', 'controller');

            expect($routes->getRoutes())->toBe([
                'home/(.*)' => '\controller',
            ]);
        });

        it('Add remplace les placeholders personnalisés avec les bons regex', static function () {
            $routes = getCollector();
            $routes->addPlaceholder('smiley', ':-)');
            $routes->add('home/(:smiley)', 'controller');

            expect($routes->getRoutes())->toBe([
                'home/(:-))' => '\controller',
            ]);
        });

        it('Add reconnait le namespace par défaut', static function () {
            $routes = getCollector();
            $routes->setDefaultNamespace('\Spec\BlitzPHP\App\Controllers');
            $routes->add('home', 'HomeController');

            expect($routes->getRoutes())->toBe([
                'home' => '\\' . HomeController::class,
            ]);
        });
    });

    describe('Setters', static function () {
        it('Modification du controleur par defaut', static function () {
            $routes = getCollector();
            $routes->setDefaultController('kishimoto');

            expect($routes->getDefaultController())->toBe('kishimotoController');
        });

        it('Modification de la methode par defaut', static function () {
            $routes = getCollector();
            $routes->setDefaultMethod('minatoNavigation');

            expect($routes->getDefaultMethod())->toBe('minatoNavigation');
        });

        it('TranslateURIDashes', static function () {
            $routes = getCollector();
            $routes->setTranslateURIDashes(true);

            expect($routes->shouldTranslateURIDashes())->toBeTruthy();
        });

        it('AutoRoute', static function () {
            $routes = getCollector();
            $routes->setAutoRoute(true);

            expect($routes->shouldAutoRoute())->toBeTruthy();
        });
    });

    describe('Groupement', static function () {
        it('Les regroupements de routes fonctionne', static function () {
            $routes = getCollector();
            $routes->group('admin', static function ($routes): void {
                $routes->add('users/list', '\UsersController::list');
            });

            expect($routes->getRoutes())->toBe([
                'admin/users/list' => '\UsersController::list',
            ]);
        });

        it('Netoyage du nom de groupe', static function () {
            $routes = getCollector();
            $routes->group('<script>admin', static function ($routes): void {
                $routes->add('users/list', '\UsersController::list');
            });

            expect($routes->getRoutes())->toBe([
                'admin/users/list' => '\UsersController::list',
            ]);
        });

        it('Les groupes sont capable de modifier les options', static function () {
            $routes = getCollector();
            $routes->group(
                'admin',
                ['namespace' => 'Admin'],
                static function ($routes): void {
                    $routes->add('users/list', '\UsersController::list');
                }
            );

            expect($routes->getRoutes())->toBe([
                'admin/users/list' => '\UsersController::list',
            ]);
        });

        it('Groupes imbriqués', static function () {
            $routes = getCollector();
            $routes->group(
                'admin',
                ['namespace' => 'Admin', 'middlewares' => ['csrf']],
                static function ($routes) {
                    $routes->get('dashboard', static function () {});

                    $routes->group('profile', static function ($routes) {
                        $routes->get('/', static function () {});
                    });
                }
            );

            expect($routes->getRoutesOptions())->toBe([
                'admin/dashboard' => [
                    'namespace'   => 'Admin',
                    'middlewares' => ['csrf'],
                ],
                'admin/profile' => [
                    'namespace'   => 'Admin',
                    'middlewares' => ['csrf'],
                ],
            ]);
        });

        it('Groupes imbriqués', static function () {
            $routes = getCollector();
            $routes->group(
                'admin',
                ['namespace' => 'Admin', 'middlewares' => ['csrf']],
                static function ($routes) {
                    $routes->get('dashboard', static function () {});

                    $routes->group('profile', static function ($routes) {
                        $routes->get('/', static function () {});
                    });
                }
            );

            expect($routes->getRoutesOptions())->toBe([
                'admin/dashboard' => [
                    'namespace'   => 'Admin',
                    'middlewares' => ['csrf'],
                ],
                'admin/profile' => [
                    'namespace'   => 'Admin',
                    'middlewares' => ['csrf'],
                ],
            ]);
        });
    });
});
