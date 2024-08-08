<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Enums\Method;
use BlitzPHP\Router\RouteBuilder;
use BlitzPHP\Spec\ReflectionHelper;
use Spec\BlitzPHP\App\Controllers\HomeController;

describe('RouteBuilder', function (): void {
    beforeEach(function (): void {
        $this->builder = new RouteBuilder(getCollector());
        $this->routes  = ReflectionHelper::getPrivateProperty($this->builder, 'collection');
    });

    describe('Ajout de route', function (): void {
        it('Test de base', function (): void {
            $this->builder->add('home', '\my\controller');

            expect($this->routes->getRoutes())->toBe([
                'home' => '\my\controller',
            ]);
        });

        it('map', function (): void {
            $this->builder->map([
				'product/(:num)'      => 'CatalogController::productLookupById',
				'product/(:alphanum)' => 'CatalogController::productLookupByName',
			]);

            expect($this->routes->getRoutes())->toBe([
				"product/([0-9]+)" => '\CatalogController::productLookupById',
				"product/([a-zA-Z0-9]+)" => '\CatalogController::productLookupByName'
			]);
        });
    });

    describe('Correspondance des verbes HTTP', function (): void {
        it('Match fonctionne avec la methode HTTP actuel', function (): void {
            setRequestMethod('GET');

            $this->builder->match(['get'], 'home', 'controller');

            expect($this->routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it('Match ignore les methodes HTTP invalide', function (): void {
            setRequestMethod('GET');

            $this->builder->match(['put'], 'home', 'controller');

            expect($this->routes->getRoutes())->toBe([]);
        });

        it('Add remplace les placeholders personnalisés avec les bons regex', function (): void {
            $this->builder->placeholder('smiley', ':-)')
                ->add('home/(:smiley)', 'controller');

            expect($this->routes->getRoutes())->toBe([
                'home/(:-))' => '\controller',
            ]);
        });
    });

    describe('Setters', function (): void {
        it('Modification du controleur par defaut', function (): void {
            $this->builder->setDefaultController('kishimoto');

            expect($this->routes->getDefaultController())->toBe('kishimotoController');
        });

        it('Modification de la methode par defaut', function (): void {
            $this->builder->setDefaultMethod('minatoNavigation');

            expect($this->routes->getDefaultMethod())->toBe('minatoNavigation');
        });
    });

    describe('Groupement', function (): void {
        it('Les regroupements de routes fonctionne', function (): void {
            $this->builder->prefix('admin')->group(function (): void {
                $this->builder->add('users/list', '\UsersController::list');
            });

            expect($this->routes->getRoutes())->toBe([
                'admin/users/list' => '\UsersController::list',
            ]);
        });

        it('Groupes imbriqués avec options externes et sans options internes', function (): void {
            $this->builder->prefix('admin')->namespace('Admin')->middlewares(['csrf'])->group(function ($routes): void {
                $this->builder->get('dashboard', static function (): void {});

                $this->builder->prefix('profile')->group(function (): void {
                    $this->builder->get('/', static function (): void {});
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

        it('Groupes imbriqués avec option externe et interne', function (): void {
            $this->builder->prefix('admin')->middlewares('csrf')->group(function (): void {
                $this->builder->get('dashboard', static function (): void {});

                $this->builder->prefix('profile')->middlewares('honeypot')->group(static function ($routes): void {
                    $routes->get('/', static function (): void {});
                });
            });
			$this->builder->prefix('users')->middlewares('group:admin')->group(function (): void {
				$this->builder->get('dashboard', static function (): void {});

				$this->builder->prefix('profile')->middlewares('can:view-profile')->group(static function ($routes): void {
                    $routes->get('/', static function (): void {});
                });
			});

            expect($this->routes->getRoutesOptions())->toBe([
                'admin/dashboard' => [
                    'middlewares' => ['csrf'],
                ],
                'admin/profile' => [
                    'middlewares' => ['csrf', 'honeypot'],
                ],
				'users/dashboard' => [
                    'middlewares' => ['group:admin'],
                ],
                'users/profile' => [
                    'middlewares' => ['group:admin', 'can:view-profile'],
                ],
            ]);
        });

        it('Groupes imbriqués sans option externe avec option interne', function (): void {
            $this->builder->prefix('admin')->middlewares('csrf')->group(function (): void {
                $this->builder->get('dashboard', static function (): void {});

                $this->builder->prefix('profile')->namespace('Admin')->group(static function ($routes): void {
                    $routes->get('/', static function (): void {});
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

        it('Le regroupement fonctionne avec une chaîne de préfixe vide', function (): void {
            $this->builder->group(function (): void {
                $this->builder->add('users/list', '\UsersController::list');
            });

            expect($this->routes->getRoutes())->toBe([
                'users/list' => '\UsersController::list',
            ]);
        });
    });

    describe('Resource & presenter', function (): void {
        it('Échafaudages de ressources correctement', function (): void {
            $this->builder->resource('photos');

            expect($this->routes->getRoutes())->toBe([
                'photos'           => '\Photos::index',
                'photos/new'       => '\Photos::new',
                'photos/(.*)/edit' => '\Photos::edit/$1',
                'photos/(.*)'      => '\Photos::show/$1',
            ]);
        });

        it('Échafaudages de ressources d\'API correctement', function (): void {
            $this->builder->resource('api/photos', ['controller' => 'Photos']);

            expect($this->routes->getRoutes())->toBe([
                'api/photos'           => '\Photos::index',
                'api/photos/new'       => '\Photos::new',
                'api/photos/(.*)/edit' => '\Photos::edit/$1',
                'api/photos/(.*)'      => '\Photos::show/$1',
            ]);
        });

        it('Échafaudages correct de presenter', function (): void {
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

    describe('Creation a partir des verbes http appropries', function (): void {
        it('GET', function (): void {
            $this->builder->get('here', 'there');

            expect($this->routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('POST', function (): void {
            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));

            $this->builder->post('here', 'there');

            expect($this->routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('GET n\'autorise pas d\'autres methodes', function (): void {
            $this->builder->get('here', 'there');
            $this->builder->post('from', 'to');

            expect($this->routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('form', function (): void {
            $this->builder->form('here', 'there');
            expect($this->routes->getRoutes())->toBe(['here' => '\formThere']);

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));
            $this->builder->form('here', 'there');
            expect($this->routes->getRoutes())->toBe(['here' => '\processThere']);
        });

        it('form avec options', function (): void {
            $options = [
                'unique' => true,
            ];

            $this->builder->form('here', 'there', $options);
            expect($this->routes->getRoutes())->toBe(['here' => '\there']);

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));
            $this->builder->form('here', 'there', $options);
            expect($this->routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('form en definissant le controleur et la methode', function (): void {
            $this->builder->form('here', 'there::index');
            expect($this->routes->getRoutes())->toBe(['here' => '\there::formIndex']);

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));

			expect($this->routes->getRoutes())->toBe(['here' => '\there::processIndex']);
        });

		it('form en definissant uniquement la methode', function (): void {
			$this->builder->controller('there')->group(function(): void {
				$this->builder->form('here', 'index');
			});

            expect($this->routes->getRoutes())->toBe(['here' => '\there::formIndex']);

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));

			expect($this->routes->getRoutes())->toBe(['here' => '\there::processIndex']);
        });

        it('form en utilisant un tableau', function (): void {
            $this->builder->form('here', [HomeController::class, 'index']);
            expect($this->routes->getRoutes())->toBe(['here' => '\Spec\BlitzPHP\App\Controllers\HomeController::formIndex']);

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));

			expect($this->routes->getRoutes())->toBe(['here' => '\Spec\BlitzPHP\App\Controllers\HomeController::processIndex']);
        });

        it('form en utilisant un tableau à un element', function (): void {
            $this->builder->form('here', [HomeController::class]);
            expect($this->routes->getRoutes())->toBe(['here' => '\Spec\BlitzPHP\App\Controllers\HomeController::formIndex']);

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));

			expect($this->routes->getRoutes())->toBe(['here' => '\Spec\BlitzPHP\App\Controllers\HomeController::processIndex']);
        });

        it('form en utilisant une closure', function (): void {
            $this->builder->form('here', static fn() => 'Hello World');

			$match = $this->routes->getRoutes();

			expect($match)->toContainKey('here');
			expect($match['here'])->toBeAnInstanceOf('Closure');

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));

			$match = $this->routes->getRoutes();

			expect($match)->toContainKey('here');
			expect($match['here'])->toBeAnInstanceOf('Closure');
		});

        it('form en utilisant une closure et l\'option unique', function (): void {
            $this->builder->form('here', static fn() => 'Hello World', ['unique' => true]);

			$match = $this->routes->getRoutes();

			expect($match)->toContainKey('here');
			expect($match['here'])->toBeAnInstanceOf('Closure');

            ReflectionHelper::setPrivateProperty($this->builder, 'collection', $this->routes->setHTTPVerb('POST'));

			$match = $this->routes->getRoutes();

			expect($match)->toContainKey('here');
			expect($match['here'])->toBeAnInstanceOf('Closure');
		});

        it('Route de vue', function (): void {
            $this->builder->view('here', 'hello');

            $route = $this->routes->getRoutes(Method::GET)['here'];
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

        it('Restriction d\'environnement', function (): void {
            $this->builder->environment('testing', function (): void {
                $this->builder->get('here', 'there');
            });
            $this->builder->environment('badenvironment', function (): void {
                $this->builder->get('from', 'to');
            });

            expect($this->routes->getRoutes())->toBe(['here' => '\there']);
        });
    });

    describe('Routes nommées', function (): void {
        it('Route nommée', function (): void {
            $this->builder->as('namedRoute')->add('users', 'Users::index');
            $this->builder->name('namedRoute2')->add('profil', 'Users::index');

            expect($this->routes->reverseRoute('namedRoute'))->toBe('/users');
            expect($this->routes->reverseRoute('namedRoute2'))->toBe('/profil');
        });

        it('Route nommée avec la locale', function (): void {
            $this->builder->as('namedRoute')->add('{locale}/users', 'Users::index');

            expect($this->routes->reverseRoute('namedRoute'))->toBe('/en/users');
        });
    });

    describe('Redirection', function (): void {
        it('Ajout de redirection', function (): void {
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

        it('Ajout de redirection avec une route nommee', function (): void {
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

    describe('Sous domaines', function (): void {
        it('Hostname', function (): void {
            ReflectionHelper::setPrivateProperty($this->routes, 'httpHost', 'example.com');

            $this->builder->hostname('example.com')->add('from', 'to');
            $this->builder->hostname('foobar.com')->add('foo', 'bar');

            expect($this->routes->getRoutes())->toBe([
                'from' => '\to',
            ]);
        });

        it('Sous domaine', function (): void {
            ReflectionHelper::setPrivateProperty($this->routes, 'httpHost', 'adm.example.com');

            $this->builder->subdomain('adm')->add('/objects/(:alphanum)', 'Admin::objectsList/$1');
            $this->builder->add('/objects/(:alphanum)', 'App::objectsList/$1');

            expect($this->routes->getRoutes())->toBe([
                'objects/([a-zA-Z0-9]+)' => '\Admin::objectsList/$1',
            ]);
        });
    });

    describe('Fallback', function (): void {
        it('Fallback', function (): void {
            expect($this->routes->get404Override())->toBeNull();
        });

        it('Fallback sous forme de chaine', function (): void {
            $this->builder->fallback('Explode');
            expect($this->routes->get404Override())->toBe('Explode');
        });

        it('Fallback sous forme de callback', function (): void {
            $this->builder->fallback(static function (): void {
                echo 'Explode now';
            });
            expect($this->routes->get404Override())->toBeAnInstanceOf('closure');
        });
    });

    it('Exception levee en cas de methode non autorisee', function (): void {
        expect(function (): void {
            $this->builder->get404Override();
        })->toThrow(new BadMethodCallException());
    });
});
