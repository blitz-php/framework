<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Router\RouteBuilder;
use BlitzPHP\Spec\ReflectionHelper;

describe('RouteBuilder', function () {
    beforeEach(function () {
        $this->builder = new RouteBuilder(getCollector());
        $this->routes  = ReflectionHelper::getPrivateProperty($this->builder, 'collection');
    });

    describe('Ajout de route', function () {
        it('Test de base', function () {
            $this->builder->add('home', '\my\controller');

            expect($this->routes->getRoutes())->toBe([
                'home' => '\my\controller',
            ]);
        });
    });

    describe('Correspondance des verbes HTTP', function () {
        it('Match fonctionne avec la methode HTTP actuel', function () {
            setRequestMethod('GET');

            $this->builder->match(['get'], 'home', 'controller');

            expect($this->routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it('Match ignore les methodes HTTP invalide', function () {
            setRequestMethod('GET');

            $this->builder->match(['put'], 'home', 'controller');

            expect($this->routes->getRoutes())->toBe([]);
        });

        it('Add remplace les placeholders personnalisés avec les bons regex', function () {
            $this->builder->placeholder('smiley', ':-)')
                ->add('home/(:smiley)', 'controller');

            expect($this->routes->getRoutes())->toBe([
                'home/(:-))' => '\controller',
            ]);
        });
    });

    describe('Setters', function () {
        it('Modification du controleur par defaut', function () {
            $this->builder->setDefaultController('kishimoto');

            expect($this->routes->getDefaultController())->toBe('kishimotoController');
        });

        it('Modification de la methode par defaut', function () {
            $this->builder->setDefaultMethod('minatoNavigation');

            expect($this->routes->getDefaultMethod())->toBe('minatoNavigation');
        });
    });

    describe('Groupement', function () {
        it('Les regroupements de routes fonctionne', function () {
            $this->builder->prefix('admin')->group(function (): void {
                $this->builder->add('users/list', '\UsersController::list');
            });

            expect($this->routes->getRoutes())->toBe([
                'admin/users/list' => '\UsersController::list',
            ]);
        });

        it('Groupes imbriqués avec options externes et sans options internes', function () {
            $this->builder->prefix('admin')->namespace('Admin')->middlewares(['csrf'])->group(function ($routes) {
                $this->builder->get('dashboard', static function () {});

                $this->builder->prefix('profile')->group(function () {
                    $this->builder->get('/', static function () {});
                });
            });

            expect($this->routes->getRoutesOptions())->toBe([
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

        it('Groupes imbriqués avec option externe et interne', function () {
            $this->builder->prefix('admin')->middlewares('csrf')->group(function () {
                $this->builder->get('dashboard', static function () {});

                $this->builder->prefix('profile')->middlewares('honeypot')->group(static function ($routes) {
                    $routes->get('/', static function () {});
                });
            });

            expect($this->routes->getRoutesOptions())->toBe([
                'admin/dashboard' => [
                    'middlewares' => ['csrf'],
                ],
                'admin/profile' => [
                    'middlewares' => ['csrf', 'honeypot'],
                ],
            ]);
        });

        it('Groupes imbriqués sans option externe avec option interne', function () {
            $this->builder->prefix('admin')->middlewares('csrf')->group(function () {
                $this->builder->get('dashboard', static function () {});

                $this->builder->prefix('profile')->namespace('Admin')->group(static function ($routes) {
                    $routes->get('/', static function () {});
                });
            });

            expect($this->routes->getRoutesOptions())->toBe([
                'admin/dashboard' => [
                    'middlewares' => ['csrf'],
                ],
                'admin/profile' => [
                    'middlewares' => ['csrf'],
                    'namespace'   => 'Admin',
                ],
            ]);
        });

        it('Le regroupement fonctionne avec une chaîne de préfixe vide', function () {
            $this->builder->group(function () {
                $this->builder->add('users/list', '\UsersController::list');
            });

            expect($this->routes->getRoutes())->toBe([
                'users/list' => '\UsersController::list',
            ]);
        });
    });

    describe('Resource & presenter', function () {
        it('Échafaudages de ressources correctement', function () {
            $this->builder->resource('photos');

            expect($this->routes->getRoutes())->toBe([
                'photos'           => '\Photos::index',
                'photos/new'       => '\Photos::new',
                'photos/(.*)/edit' => '\Photos::edit/$1',
                'photos/(.*)'      => '\Photos::show/$1',
            ]);
        });

        it('Échafaudages de ressources d\'API correctement', function () {
            $this->builder->resource('api/photos', ['controller' => 'Photos']);

            expect($this->routes->getRoutes())->toBe([
                'api/photos'           => '\Photos::index',
                'api/photos/new'       => '\Photos::new',
                'api/photos/(.*)/edit' => '\Photos::edit/$1',
                'api/photos/(.*)'      => '\Photos::show/$1',
            ]);
        });

        it('Échafaudages correct de presenter', function () {
            $this->builder->presenter('photos');

            expect($this->routes->getRoutes())->toBe([
                'photos'             => '\Photos::index',
                'photos/new'         => '\Photos::new',
                'photos/edit/(.*)'   => '\Photos::edit/$1',
                'photos/remove/(.*)' => '\Photos::remove/$1',
                'photos/show/(.*)'   => '\Photos::show/$1',
                'photos/(.*)'        => '\Photos::show/$1',
            ]);
        });
    });

    describe('Creation a partir des verbes http appropries', function () {
        it('GET', function () {
            $this->builder->get('here', 'there');

            expect($this->routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('POST', function () {
            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));

            $this->builder->post('here', 'there');

            expect($this->routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('GET n\'autorise pas d\'autres methodes', function () {
            $this->builder->get('here', 'there');
            $this->builder->post('from', 'to');

            expect($this->routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('form', function () {
            $this->builder->form('here', 'there');
            expect($this->routes->getRoutes())->toBe(['here' => '\formThere']);

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));
            $this->builder->form('here', 'there');
            expect($this->routes->getRoutes())->toBe(['here' => '\processThere']);
        });

        it('form avec options', function () {
            $options = [
                'unique' => true,
            ];

            $this->builder->form('here', 'there', $options);
            expect($this->routes->getRoutes())->toBe(['here' => '\there']);

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));
            $this->builder->form('here', 'there', $options);
            expect($this->routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('form en definissant le controleur et la methode', function () {
            $this->builder->form('here', 'there::index');
            expect($this->routes->getRoutes())->toBe(['here' => '\there::formIndex']);

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));
            $this->builder->form('here', 'there::index');
            expect($this->routes->getRoutes())->toBe(['here' => '\there::processIndex']);
        });

        it('Route de vue', function () {
            $this->builder->view('here', 'hello');

            $route = $this->routes->getRoutes('get')['here'];
            expect($route)->toBeAnInstanceOf('closure');

            // Testez que la route n'est pas disponible dans aucun autre verbe
            expect($this->routes->getRoutes('*'))->not->toContainKey('here');
            expect($this->routes->getRoutes('options'))->not->toContainKey('here');
            expect($this->routes->getRoutes('head'))->not->toContainKey('here');
            expect($this->routes->getRoutes('post'))->not->toContainKey('here');
            expect($this->routes->getRoutes('put'))->not->toContainKey('here');
            expect($this->routes->getRoutes('delete'))->not->toContainKey('here');
            expect($this->routes->getRoutes('trace'))->not->toContainKey('here');
            expect($this->routes->getRoutes('connect'))->not->toContainKey('here');
            expect($this->routes->getRoutes('cli'))->not->toContainKey('here');
        });

        it('Restriction d\'environnement', function () {
            $this->builder->environment('testing', function (): void {
                $this->builder->get('here', 'there');
            });
            $this->builder->environment('badenvironment', function (): void {
                $this->builder->get('from', 'to');
            });

            expect($this->routes->getRoutes())->toBe(['here' => '\there']);
        });
    });

    describe('Routes nommées', function () {
        it('Route nommée', function () {
            $this->builder->as('namedRoute')->add('users', 'Users::index');
            $this->builder->name('namedRoute2')->add('profil', 'Users::index');

            expect($this->routes->reverseRoute('namedRoute'))->toBe('/users');
            expect($this->routes->reverseRoute('namedRoute2'))->toBe('/profil');
        });

        it('Route nommée avec la locale', function () {
            $this->builder->as('namedRoute')->add('{locale}/users', 'Users::index');

            expect($this->routes->reverseRoute('namedRoute'))->toBe('/en/users');
        });
    });

    describe('Redirection', function () {
        it('Ajout de redirection', function () {
            // Le deuxième paramètre est soit le nouvel URI vers lequel rediriger, soit le nom d'une route nommée.
            $this->builder->redirect('users', 'users/index', 307);

            $expected = [
                'users' => 'users/index',
            ];

            expect($this->routes->getRoutes())->toBe($expected);
            expect($this->routes->isRedirect('users'))->toBeTruthy();
            expect($this->routes->getRedirectCode('users'))->toBe(307);
            expect($this->routes->getRedirectCode('bosses'))->toBe(0);
        });

        it('Ajout de redirection avec une route nommee', function () {
            $this->builder->name('namedRoute')->add('zombies', 'Zombies::index');
            $this->builder->redirect('users', 'namedRoute', 307);

            $expected = [
                'zombies' => '\Zombies::index',
                'users'   => ['zombies' => '\Zombies::index'],
            ];

            expect($this->routes->getRoutes())->toBe($expected);
            expect($this->routes->isRedirect('users'))->toBeTruthy();
            expect($this->routes->getRedirectCode('users'))->toBe(307);
        });
    });

    describe('Sous domaines', function () {
        it('Hostname', function () {
            ReflectionHelper::setPrivateProperty($this->routes, 'httpHost', 'example.com');

            $this->builder->hostname('example.com')->add('from', 'to');
            $this->builder->hostname('foobar.com')->add('foo', 'bar');

            expect($this->routes->getRoutes())->toBe([
                'from' => '\to',
            ]);
        });

        it('Sous domaine', function () {
            ReflectionHelper::setPrivateProperty($this->routes, 'httpHost', 'adm.example.com');

            $this->builder->subdomain('adm')->add('/objects/(:alphanum)', 'Admin::objectsList/$1');
            $this->builder->add('/objects/(:alphanum)', 'App::objectsList/$1');

            expect($this->routes->getRoutes())->toBe([
                'objects/([a-zA-Z0-9]+)' => '\Admin::objectsList/$1',
            ]);
        });
    });

    describe('Fallback', function () {
        it('Fallback', function () {
            expect($this->routes->get404Override())->toBeNull();
        });

        it('Fallback sous forme de chaine', function () {
            $this->builder->fallback('Explode');
            expect($this->routes->get404Override())->toBe('Explode');
        });

        it('Fallback sous forme de callback', function () {
            $this->builder->fallback(static function (): void {
                echo 'Explode now';
            });
            expect($this->routes->get404Override())->toBeAnInstanceOf('closure');
        });
    });

    it('Exception levee en cas de methode non autorisee', function () {
        expect(function () {
            $this->builder->get404Override();
        })->toThrow(new BadMethodCallException());
    });
});
