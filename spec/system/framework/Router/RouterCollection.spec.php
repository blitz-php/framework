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

function getCollector(string $verb = 'get', array $config = [], array $files = []): RouteCollection
{
    $defaults = ['App' => APP_PATH];
    $config   = array_merge($config, $defaults);

    Services::autoloader()->addNamespace($config);

    $loader = Services::locator();

    $routing                    = (object) config('routing');
    $routing->default_namespace = '\\';

    return (new RouteCollection($loader, $routing))->setHTTPVerb($verb);
}

function setRequestMethod(string $method): void
{
    Services::set(Request::class, Services::request()->withMethod($method));
}

describe('RouteCollection', function () {
    beforeEach(function () {
        // Services::reset(false);
    });

    describe('Ajout de route', function () {
        it('Test de base', function () {
            $routes = getCollector();
            $routes->add('home', '\my\controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\my\controller',
            ]);
        });

        it('Test de base avec un callback', function () {
            $routes = getCollector();
            $routes->add('home', [HomeController::class, 'index']);

            expect($routes->getRoutes())->toBe([
                'home' => '\Spec\BlitzPHP\App\Controllers\HomeController::index',
            ]);
        });

        it('Test de base avec un callback et des parametres', function () {
            $routes = getCollector();
            $routes->add('product/(:num)/(:num)', [[HomeController::class, 'index'], '$2/$1']);

            expect($routes->getRoutes())->toBe([
                'product/([0-9]+)/([0-9]+)' => '\Spec\BlitzPHP\App\Controllers\HomeController::index/$2/$1',
            ]);
        });

        it('Test de base avec un callback avec des parametres sans la chaine de definition', function () {
            $routes = getCollector();
            $routes->add('product/(:num)/(:num)', [HomeController::class, 'index']);

            expect($routes->getRoutes())->toBe([
                'product/([0-9]+)/([0-9]+)' => '\Spec\BlitzPHP\App\Controllers\HomeController::index/$1/$2',
            ]);
        });

        it("Ajout du namespace par défaut quand il n'a pas été défini", function () {
            $routes = getCollector();
            $routes->add('home', 'controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it("Ignorer le namespace par défaut lorsqu'il existe", function () {
            $routes = getCollector();
            $routes->add('home', 'my\controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\my\controller',
            ]);
        });

        it('Ajout avec un slash en debut de chaine', function () {
            $routes = getCollector();
            $routes->add('/home', 'controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });
    });

    describe('Correspondance des verbes HTTP', function () {
        it('Match fonctionne avec la methode HTTP actuel', function () {
            setRequestMethod('GET');

            $routes = getCollector();
            $routes->match(['get'], 'home', 'controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it('Match ignore les methodes HTTP invalide', function () {
            setRequestMethod('GET');

            $routes = getCollector();
            $routes->match(['put'], 'home', 'controller');

            expect($routes->getRoutes())->toBe([]);
        });

        it('Match supporte plusieurs methodes', function () {
            setRequestMethod('GET');
            $routes = getCollector();

            $routes->match(['get', 'post'], 'here', 'there');
            expect($routes->getRoutes())->toBe(['here' => '\there']);

			setRequestMethod('POST');
            $routes = getCollector();

            $routes->match(['get', 'post'], 'here', 'there');
            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('Add fonctionne avec un tableau de verbes HTTP', function () {
            setRequestMethod('POST');

            $routes = getCollector();
            $routes->add('home', 'controller', ['get', 'post']);

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it('Add remplace les placeholders par defaut avec les bons regex', function () {
            $routes = getCollector();
            $routes->add('home/(:any)', 'controller');

            expect($routes->getRoutes())->toBe([
                'home/(.*)' => '\controller',
            ]);
        });

        it('Add remplace les placeholders personnalisés avec les bons regex', function () {
            $routes = getCollector();
            $routes->addPlaceholder('smiley', ':-)');
            $routes->add('home/(:smiley)', 'controller');

            expect($routes->getRoutes())->toBe([
                'home/(:-))' => '\controller',
            ]);
        });

        it('Add reconnait le namespace par défaut', function () {
            $routes = getCollector();
            $routes->setDefaultNamespace('\Spec\BlitzPHP\App\Controllers');
            $routes->add('home', 'HomeController');

            expect($routes->getRoutes())->toBe([
                'home' => '\\' . HomeController::class,
            ]);
        });
    });

    describe('Setters', function () {
        it('Modification du controleur par defaut', function () {
            $routes = getCollector();
            $routes->setDefaultController('kishimoto');

            expect($routes->getDefaultController())->toBe('kishimotoController');
        });

        it('Modification de la methode par defaut', function () {
            $routes = getCollector();
            $routes->setDefaultMethod('minatoNavigation');

            expect($routes->getDefaultMethod())->toBe('minatoNavigation');
        });

        it('TranslateURIDashes', function () {
            $routes = getCollector();
            $routes->setTranslateURIDashes(true);

            expect($routes->shouldTranslateURIDashes())->toBeTruthy();
        });

        it('AutoRoute', function () {
            $routes = getCollector();
            $routes->setAutoRoute(true);

            expect($routes->shouldAutoRoute())->toBeTruthy();
        });
    });

    describe('Groupement', function () {
        it('Les regroupements de routes fonctionne', function () {
            $routes = getCollector();
            $routes->group('admin', function ($routes): void {
                $routes->add('users/list', '\UsersController::list');
            });

            expect($routes->getRoutes())->toBe([
                'admin/users/list' => '\UsersController::list',
            ]);
        });

        it('Netoyage du nom de groupe', function () {
            $routes = getCollector();
            $routes->group('<script>admin', function ($routes): void {
                $routes->add('users/list', '\UsersController::list');
            });

            expect($routes->getRoutes())->toBe([
                'admin/users/list' => '\UsersController::list',
            ]);
        });

        it('Les groupes sont capable de modifier les options', function () {
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

        it('Groupes imbriqués avec options externes et sans options internes', function () {
            $routes = getCollector();
            $routes->group(
                'admin',
                ['namespace' => 'Admin', 'middlewares' => ['csrf']],
                static function ($routes) {
                    $routes->get('dashboard', function () {});

                    $routes->group('profile', function ($routes) {
                        $routes->get('/', function () {});
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

        it('Groupes imbriqués avec option externe et interne', function () {
            $routes = getCollector();
            $routes->group(
                'admin',
                ['middlewares' => ['csrf']],
                static function ($routes) {
                    $routes->get('dashboard', function () {});

                    $routes->group(
						'profile',
						['middlewares' => ['honeypot']],
						static function ($routes) {
                        	$routes->get('/', function () {});
                    	}
					);
                }
            );

            expect($routes->getRoutesOptions())->toBe([
                'admin/dashboard' => [
                    'middlewares' => ['csrf'],
                ],
                'admin/profile' => [
                    'middlewares' => ['csrf', 'honeypot'],
                ],
            ]);
        });

        it('Groupes imbriqués sans option externe avec option interne', function () {
            $routes = getCollector();
            $routes->group(
                'admin',
                ['middlewares' => ['csrf']],
                static function ($routes) {
                    $routes->get('dashboard', function () {});

                    $routes->group(
						'profile',
						['namespace' => 'Admin'],
						static function ($routes) {
                        	$routes->get('/', function () {});
                    	}
					);
                }
            );

            expect($routes->getRoutesOptions())->toBe([
                'admin/dashboard' => [
                    'middlewares' => ['csrf'],
                ],
                'admin/profile' => [
                    'middlewares' => ['csrf'],
					'namespace' => 'Admin',
                ],
            ]);
        });

        it('Le regroupement fonctionne avec une chaîne de préfixe vide', function () {
            $routes = getCollector();
            $routes->group(
                '',
                static function ($routes) {
                    $routes->add('users/list', '\UsersController::list');
                }
            );

            expect($routes->getRoutes())->toBe([
                'users/list' => '\UsersController::list',
            ]);
        });

        it('Le regroupement imbriqué fonctionne avec un préfixe vide', function () {
            $routes = getCollector();

			$routes->add('verify/begin', '\VerifyController::begin');

			$routes->group('admin', static function ($routes): void {
				$routes->group(
					'',
					static function ($routes): void {
						$routes->add('users/list', '\UsersController::list');

						$routes->group('delegate', static function ($routes): void {
							$routes->add('foo', '\UsersController::foo');
						});
					}
				);
			});

            expect($routes->getRoutes())->toBe([
				'verify/begin'       => '\VerifyController::begin',
				'admin/users/list'   => '\UsersController::list',
				'admin/delegate/foo' => '\UsersController::foo',
			]);
        });
    });

    describe('Options', function () {
        it('Hostname', function () {
			$_SERVER['HTTP_HOST'] = 'example.com';

			$routes = getCollector();

            $routes->add('from', 'to', ['hostname' => 'example.com']);
	        $routes->add('foo', 'bar', ['hostname' => 'foobar.com']);

            expect($routes->getRoutes())->toBe([
                'from' => '\to',
            ]);
        });
    });

    describe('Resource & presenter', function () {
        it('Échafaudages de ressources correctement', function () {
			$routes = getCollector();
			$routes->setHTTPVerb('get');
        	$routes->resource('photos');

            expect($routes->getRoutes())->toBe([
				'photos'           => '\Photos::index',
				'photos/new'       => '\Photos::new',
				'photos/(.*)/edit' => '\Photos::edit/$1',
				'photos/(.*)'      => '\Photos::show/$1',
			]);

			$routes = getCollector();
			$routes->setHTTPVerb('post');
        	$routes->resource('photos');

            expect($routes->getRoutes())->toBe([
				'photos' => '\Photos::create',
			]);

			$routes = getCollector();
			$routes->setHTTPVerb('put');
        	$routes->resource('photos');

            expect($routes->getRoutes())->toBe([
				'photos/(.*)' => '\Photos::update/$1',
			]);

			$routes = getCollector();
			$routes->setHTTPVerb('patch');
        	$routes->resource('photos');

            expect($routes->getRoutes())->toBe([
				'photos/(.*)' => '\Photos::update/$1',
			]);

			$routes = getCollector();
			$routes->setHTTPVerb('delete');
        	$routes->resource('photos');

            expect($routes->getRoutes())->toBe([
				'photos/(.*)' => '\Photos::delete/$1',
			]);
        });

        it('Échafaudages de ressources d\'API correctement', function () {
			$routes = getCollector();
			$routes->setHTTPVerb('get');
        	$routes->resource('api/photos', ['controller' => 'Photos']);

            expect($routes->getRoutes())->toBe([
				'api/photos'           => '\Photos::index',
				'api/photos/new'       => '\Photos::new',
				'api/photos/(.*)/edit' => '\Photos::edit/$1',
				'api/photos/(.*)'      => '\Photos::show/$1',
			]);

			$routes = getCollector();
			$routes->setHTTPVerb('post');
        	$routes->resource('api/photos', ['controller' => 'Photos']);

            expect($routes->getRoutes())->toBe([
				'api/photos' => '\Photos::create',
			]);

			$routes = getCollector();
			$routes->setHTTPVerb('put');
        	$routes->resource('api/photos', ['controller' => 'Photos']);

            expect($routes->getRoutes())->toBe([
				'api/photos/(.*)' => '\Photos::update/$1',
			]);

			$routes = getCollector();
			$routes->setHTTPVerb('patch');
        	$routes->resource('api/photos', ['controller' => 'Photos']);

            expect($routes->getRoutes())->toBe([
				'api/photos/(.*)' => '\Photos::update/$1',
			]);

			$routes = getCollector();
			$routes->setHTTPVerb('delete');
        	$routes->resource('api/photos', ['controller' => 'Photos']);

            expect($routes->getRoutes())->toBe([
				'api/photos/(.*)' => '\Photos::delete/$1',
			]);
        });

		it('Échafaudages correct de presenter', function () {
			$routes = getCollector();
			$routes->setHTTPVerb('get');
        	$routes->presenter('photos');

            expect($routes->getRoutes())->toBe([
				'photos'             => '\Photos::index',
				'photos/show/(.*)'   => '\Photos::show/$1',
				'photos/(.*)'        => '\Photos::show/$1',
				'photos/new'         => '\Photos::new',
				'photos/edit/(.*)'   => '\Photos::edit/$1',
				'photos/remove/(.*)' => '\Photos::remove/$1',
			]);

			$routes = getCollector();
			$routes->setHTTPVerb('post');
        	$routes->presenter('photos');

            expect($routes->getRoutes())->toBe([
				'photos/create'      => '\Photos::create',
				'photos'      => '\Photos::create',
				'photos/update/(.*)' => '\Photos::update/$1',
				'photos/delete/(.*)' => '\Photos::delete/$1',
			]);
        });

		it('Ressources avec un controleur personnalisé', function () {
			setRequestMethod('get');
			$routes = getCollector();
			$routes->resource('photos', ['controller' => '<script>gallery']);

            expect($routes->getRoutes())->toBe([
				'photos'           => '\Gallery::index',
				'photos/new'       => '\Gallery::new',
				'photos/(.*)/edit' => '\Gallery::edit/$1',
				'photos/(.*)'      => '\Gallery::show/$1',
			]);
        });

		it('Ressources avec un placeholder personnalisé', function () {
			setRequestMethod('get');
			$routes = getCollector();
			$routes->resource('photos', ['placeholder' => ':num']);

            expect($routes->getRoutes())->toBe([
				'photos'               => '\Photos::index',
				'photos/new'           => '\Photos::new',
				'photos/([0-9]+)/edit' => '\Photos::edit/$1',
				'photos/([0-9]+)'      => '\Photos::show/$1',
			]);
        });

		it('Ressources avec le placeholder par defaut', function () {
			setRequestMethod('get');
			$routes = getCollector();

			$routes->setDefaultConstraint('num');
			$routes->resource('photos');

            expect($routes->getRoutes())->toBe([
				'photos'               => '\Photos::index',
				'photos/new'           => '\Photos::new',
				'photos/([0-9]+)/edit' => '\Photos::edit/$1',
				'photos/([0-9]+)'      => '\Photos::show/$1',
			]);
        });

		it('Ressources avec un bug du placeholder par defaut', function () {
			setRequestMethod('get');
			$routes = getCollector();

			$routes->setDefaultConstraint(':num');
			$routes->resource('photos');

            expect($routes->getRoutes())->toBe([
				'photos'           => '\Photos::index',
				'photos/new'       => '\Photos::new',
				'photos/(.*)/edit' => '\Photos::edit/$1',
				'photos/(.*)'      => '\Photos::show/$1',
			]);
        });

		it('Ressources avec l\'option <only>', function () {
			setRequestMethod('get');
			$routes = getCollector();

			$routes->resource('photos', ['only' => 'index']);

            expect($routes->getRoutes())->toBe([
				'photos'           => '\Photos::index',
			]);
        });

		it('Ressources avec l\'option <except>', function () {
			setRequestMethod('get');
			$routes = getCollector();

			$routes->resource('photos', ['except' => 'edit,new']);

            expect($routes->getRoutes())->toBe([
				'photos'      => '\Photos::index',
				'photos/(.*)' => '\Photos::show/$1',
			]);
        });

		xit('Ressources avec l\'option <websafe>', function () {
			setRequestMethod('get');
			$routes = getCollector();

			$routes->resource('photos', ['websafe' => true]);

            expect($routes->getRoutes())->toBe([
				'photos'             => '\Photos::create',
				'photos/(.*)/delete' => '\Photos::delete/$1',
				'photos/(.*)'        => '\Photos::update/$1',
			]);
        });
    });

	describe('Creation a partir des verbes http appropries', function () {
		it('GET', function () {
			setRequestMethod('get');
			$routes = getCollector();

			$routes->get('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
		});

		it('POST', function () {
			$routes = getCollector('post');

			$routes->post('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
		});

		it('GET n\'autorise pas d\'autres methodes', function () {
			$routes = getCollector();
			$routes->setHTTPVerb('GET');

			$routes->get('here', 'there');
			$routes->post('from', 'to');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
		});

		it('PUT', function () {
			$routes = getCollector('put');

			$routes->put('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
		});

		it('DELETE', function () {
			$routes = getCollector('delete');

			$routes->delete('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
		});

		it('HEAD', function () {
			$routes = getCollector('head');

			$routes->head('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
		});

		it('PATCH', function () {
			$routes = getCollector('patch');

			$routes->patch('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
		});

		it('OPTIONS', function () {
			$routes = getCollector('options');

			$routes->options('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
		});

		it('Route de vue', function () {
			$routes = getCollector();

			$routes->view('here', 'hello');

			$route = $routes->getRoutes('get')['here'];
			expect($route)->toBeAnInstanceOf('closure');

			// Testez que la route n'est pas disponible dans aucun autre verbe
			expect($routes->getRoutes('*'))->not->toContainKey('here');
			expect($routes->getRoutes('options'))->not->toContainKey('here');
			expect($routes->getRoutes('head'))->not->toContainKey('here');
			expect($routes->getRoutes('post'))->not->toContainKey('here');
			expect($routes->getRoutes('put'))->not->toContainKey('here');
			expect($routes->getRoutes('delete'))->not->toContainKey('here');
			expect($routes->getRoutes('trace'))->not->toContainKey('here');
			expect($routes->getRoutes('connect'))->not->toContainKey('here');
			expect($routes->getRoutes('cli'))->not->toContainKey('here');
		});

		it('Restriction d\'environnement', function () {
			setRequestMethod('get');
			$routes = getCollector();

			$routes->environment(
				'testing',
				static function ($routes): void {
					$routes->get('here', 'there');
				}
			);
			$routes->environment(
				'badenvironment',
				static function ($routes): void {
					$routes->get('from', 'to');
				}
			);

            expect($routes->getRoutes())->toBe(['here' => '\there']);
		});
	});
});