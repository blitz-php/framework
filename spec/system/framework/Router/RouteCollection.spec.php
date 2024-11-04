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
use BlitzPHP\Enums\Method;
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Http\Request;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Router\Router;
use BlitzPHP\Spec\ReflectionHelper;
use Spec\BlitzPHP\App\Controllers\HomeController;
use Spec\BlitzPHP\App\Controllers\ProductController;

use function Kahlan\expect;

function getCollector(string $verb = Method::GET, array $config = [], array $files = []): RouteCollection
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

describe('RouteCollection', function (): void {
    beforeEach(function (): void {
        // Services::reset(false);
    });

    describe('Ajout de route', function (): void {
        it('Test de base', function (): void {
            $routes = getCollector();
            $routes->add('home', '\my\controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\my\controller',
            ]);
        });

        it('Test de base avec un callback', function (): void {
            $routes = getCollector();
            $routes->add('home', [HomeController::class, 'index']);

            expect($routes->getRoutes())->toBe([
                'home' => '\Spec\BlitzPHP\App\Controllers\HomeController::index',
            ]);
        });

        it('Test de base avec un callback et des parametres', function (): void {
            $routes = getCollector();
            $routes->add('product/(:num)/(:num)', [[HomeController::class, 'index'], '$2/$1']);

            expect($routes->getRoutes())->toBe([
                'product/([0-9]+)/([0-9]+)' => '\Spec\BlitzPHP\App\Controllers\HomeController::index/$2/$1',
            ]);
        });

        it('Test de base avec un callback avec des parametres sans la chaine de definition', function (): void {
            $routes = getCollector();
            $routes->add('product/(:num)/(:num)', [HomeController::class, 'index']);

            expect($routes->getRoutes())->toBe([
                'product/([0-9]+)/([0-9]+)' => '\Spec\BlitzPHP\App\Controllers\HomeController::index/$1/$2',
            ]);
        });

        it("Ajout du namespace par défaut quand il n'a pas été défini", function (): void {
            $routes = getCollector();
            $routes->add('home', 'controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it("Ignorer le namespace par défaut lorsqu'il existe", function (): void {
            $routes = getCollector();
            $routes->add('home', 'my\controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\my\controller',
            ]);
        });

        it('Ajout avec un slash en debut de chaine', function (): void {
            $routes = getCollector();
            $routes->add('/home', 'controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });
    });

    describe('Correspondance des verbes HTTP', function (): void {
        it('Match fonctionne avec la methode HTTP actuel', function (): void {
            setRequestMethod('GET');

            $routes = getCollector();
            $routes->match(['GET'], 'home', 'controller');

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it('Match ignore les methodes HTTP invalide', function (): void {
            setRequestMethod('GET');

            $routes = getCollector();
            $routes->match(['PUT'], 'home', 'controller');

            expect($routes->getRoutes())->toBe([]);
        });

        it('Match supporte plusieurs methodes', function (): void {
            setRequestMethod('GET');
            $routes = getCollector();

            $routes->match(['GET', 'POST'], 'here', 'there');
            expect($routes->getRoutes())->toBe(['here' => '\there']);

            setRequestMethod('POST');
            $routes = getCollector();

            $routes->match(['GET', 'POST'], 'here', 'there');
            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('Add fonctionne avec un tableau de verbes HTTP', function (): void {
            setRequestMethod('POST');

            $routes = getCollector();
            $routes->add('home', 'controller', ['GET', 'POST']);

            expect($routes->getRoutes())->toBe([
                'home' => '\controller',
            ]);
        });

        it('Add remplace les placeholders par defaut avec les bons regex', function (): void {
            $routes = getCollector();
            $routes->add('home/(:any)', 'controller');

            expect($routes->getRoutes())->toBe([
                'home/(.*)' => '\controller',
            ]);
        });

        it('Add remplace les placeholders personnalisés avec les bons regex', function (): void {
            $routes = getCollector();
            $routes->addPlaceholder('smiley', ':-)');
            $routes->add('home/(:smiley)', 'controller');

            expect($routes->getRoutes())->toBe([
                'home/(:-))' => '\controller',
            ]);
        });

        it('Add reconnait le namespace par défaut', function (): void {
            $routes = getCollector();
            $routes->setDefaultNamespace('\Spec\BlitzPHP\App\Controllers');
            $routes->add('home', 'HomeController');

            expect($routes->getRoutes())->toBe([
                'home' => '\\' . HomeController::class,
            ]);
        });
    });

    describe('Setters', function (): void {
        it('Modification du namespace par defaut', function (): void {
            $routes = getCollector();
            $routes->setDefaultNamespace('Apps');

            expect($routes->getDefaultNamespace())->toBe('Apps\\');

			setRequestMethod(Method::GET);
        	$routes = getCollector();
        	$router = new Router($routes, Services::request());

        	$routes->setDefaultNamespace('App\Controllers');
        	$routes->get('/', 'Core\Home::index');

        	$expects = 'App\Controllers\Core\HomeController';

        	expect($router->handle('/'))->toBe($expects);
        });

        it('Modification du controleur par defaut', function (): void {
            $routes = getCollector();
            $routes->setDefaultController('kishimoto');

            expect($routes->getDefaultController())->toBe('kishimotoController');
        });

        it('Modification de la methode par defaut', function (): void {
            $routes = getCollector();
            $routes->setDefaultMethod('minatoNavigation');

            expect($routes->getDefaultMethod())->toBe('minatoNavigation');
        });

        it('TranslateURIDashes', function (): void {
            $routes = getCollector();
            $routes->setTranslateURIDashes(true);

            expect($routes->shouldTranslateURIDashes())->toBeTruthy();
        });

        it('AutoRoute', function (): void {
            $routes = getCollector();
            $routes->setAutoRoute(true);

            expect($routes->shouldAutoRoute())->toBeTruthy();
        });

		it('useSupportedLocalesOnly', function (): void {
			config()->set('app.supported_locales', ['en']);
			setRequestMethod(Method::GET);

            $routes = getCollector();

			expect($routes->shouldUseSupportedLocalesOnly())->toBeFalsy();

			$routes->useSupportedLocalesOnly(true);
			expect($routes->shouldUseSupportedLocalesOnly())->toBeTruthy();

			$routes->get('{locale}/products', 'Products::list');
			$router = new Router($routes, Services::request());

			expect(fn() => $router->handle('fr/products'))
				->toThrow(new PageNotFoundException());

			$routes->useSupportedLocalesOnly(false);
			expect($routes->shouldUseSupportedLocalesOnly())->toBeFalsy();

			expect($router->handle('fr/products'))->toBe('ProductsController');
        });
    });

    describe('Groupement', function (): void {
        it('Les regroupements de routes fonctionne', function (): void {
            $routes = getCollector();
            $routes->group('admin', static function ($routes): void {
                $routes->add('users/list', '\UsersController::list');
            });

            expect($routes->getRoutes())->toBe([
                'admin/users/list' => '\UsersController::list',
            ]);
        });

        it('Netoyage du nom de groupe', function (): void {
            $routes = getCollector();
            $routes->group('<script>admin', static function ($routes): void {
                $routes->add('users/list', '\UsersController::list');
            });

            expect($routes->getRoutes())->toBe([
                'admin/users/list' => '\UsersController::list',
            ]);
        });

        it('Les groupes sont capable de modifier les options', function (): void {
            $routes = getCollector();
            $routes->group(
                'admin',
                ['namespace' => 'Admin'],
                static function ($routes): void {
                    $routes->add('users/list', 'UsersController::list');
                }
            );

            expect($routes->getRoutes())->toBe([
                'admin/users/list' => '\Admin\UsersController::list',
            ]);
        });

        it('Groupes imbriqués avec options externes et sans options internes', function (): void {
            $routes = getCollector();
            $routes->group(
                'admin',
                ['namespace' => 'Admin', 'middlewares' => ['csrf']],
                static function ($routes): void {
                    $routes->get('dashboard', static function (): void {});

                    $routes->group('profile', static function ($routes): void {
                        $routes->get('/', static function (): void {});
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

        it('Groupes imbriqués avec option externe et interne', function (): void {
            $routes = getCollector();

            $routes->group(
                'admin',
                ['middlewares' => ['csrf']],
                static function ($routes): void {
                    $routes->get('dashboard', static function (): void {});

                    $routes->group(
                        'profile',
                        ['middlewares' => ['honeypot']],
                        static function ($routes): void {
                            $routes->get('/', static function (): void {});
                        }
                    );
                }
            );

			$routes->group(
                'users',
                ['middlewares' => ['group:admin']],
                static function ($routes): void {
                    $routes->get('dashboard', static function (): void {});

                    $routes->group(
                        'profile',
                        ['middlewares' => ['can:view-profile']],
                        static function ($routes): void {
                            $routes->get('/', static function (): void {});
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
                'users/dashboard' => [
                    'middlewares' => ['group:admin'],
                ],
                'users/profile' => [
                    'middlewares' => ['group:admin', 'can:view-profile'],
                ],
            ]);
        });

        it('Groupes imbriqués sans option externe avec option interne', function (): void {
            $routes = getCollector();
            $routes->group(
                'admin',
                ['middlewares' => ['csrf']],
                static function ($routes): void {
                    $routes->get('dashboard', static function (): void {});

                    $routes->group(
                        'profile',
                        ['namespace' => 'Admin'],
                        static function ($routes): void {
                            $routes->get('/', static function (): void {});
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
                    'namespace'   => 'Admin',
                ],
            ]);
        });

        it('Le regroupement fonctionne avec une chaîne de préfixe vide', function (): void {
            $routes = getCollector();
            $routes->group(
                '',
                static function ($routes): void {
                    $routes->add('users/list', '\UsersController::list');
                }
            );

            expect($routes->getRoutes())->toBe([
                'users/list' => '\UsersController::list',
            ]);
        });

        it('Le regroupement imbriqué fonctionne avec un préfixe vide', function (): void {
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

        it('Le regroupement imbriqué sans préfixe racine', function (): void {
            $collections = [
				['admin', '/', [
					'admin/users/list'   => '\UsersController::list',
					'admin/delegate/foo' => '\UsersController::foo',
				]],
				['/', '', [
					'users/list'   => '\UsersController::list',
					'delegate/foo' => '\UsersController::foo',
				]],
				['', '', [
					'users/list'   => '\UsersController::list',
					'delegate/foo' => '\UsersController::foo',
				]],
				['', '/', [
					'users/list'   => '\UsersController::list',
					'delegate/foo' => '\UsersController::foo',
				]],
			];

			foreach ($collections as $collection) {
				[$group, $subgroup, $expected] = $collection;
				$routes                        = getCollector();

				$routes->group($group, static function ($routes) use ($subgroup): void {
					$routes->group(
						$subgroup,
						static function ($routes): void {
							$routes->add('users/list', '\UsersController::list');

							$routes->group('delegate', static function ($routes): void {
								$routes->add('foo', '\UsersController::foo');
							});
						}
					);
				});

				expect($routes->getRoutes())->toBe($expected);
			}
        });
    });

    describe('Options', function (): void {
        it('Options', function (): void {
            $routes = getCollector();

            // les options doivent être déclarées séparément, pour ne pas confondre PHPCBF
            $options = [
                'as'  => 'admin',
                'foo' => 'baz',
            ];
            $routes->add(
                'administrator',
                static function (): void {},
                $options
            );

            expect($routes->getRoutesOptions('administrator'))->toBe($options);
        });

        it('Options pour les different verbes', function (): void {
            $routes = getCollector();

            // les options doivent être déclarées séparément, pour ne pas confondre PHPCBF
            $options1 = [
                'as'  => 'admin1',
                'foo' => 'baz1',
            ];
            $options2 = [
                'as'  => 'admin2',
                'foo' => 'baz2',
            ];
            $options3 = [
                'bar' => 'baz',
            ];
            $routes->get(
                'administrator',
                static function (): void {},
                $options1
            );
            $routes->post(
                'administrator',
                static function (): void {},
                $options2
            );
            $routes->add(
                'administrator',
                static function (): void {},
                $options3
            );

            $options = $routes->getRoutesOptions('administrator');
            expect($options)->toBe(['as' => 'admin1', 'foo' => 'baz1', 'bar' => 'baz']);

            $options = $routes->setHTTPVerb('post')->getRoutesOptions('administrator');
            expect($options)->toBe(['as' => 'admin2', 'foo' => 'baz2', 'bar' => 'baz']);

            $options = $routes->setHTTPVerb('get')->getRoutesOptions('administrator', 'POST');
            expect($options)->toBe(['as' => 'admin2', 'foo' => 'baz2', 'bar' => 'baz']);
        });

        it('Options de groupes avec des middlewares simple', function (): void {
            setRequestMethod('GET');
            $routes = getCollector();

            $routes->group(
                'admin',
                ['middleware' => 'role'],
                static function ($routes): void {
                    $routes->add('users', '\Users::list');
                }
            );

            expect($routes->isFiltered('admin/users'))->toBeTruthy();
            expect($routes->isFiltered('admin/franky'))->toBeFalsy();
            expect($routes->getFiltersForRoute('admin/users'))->toBe(['role']);
            expect($routes->getFiltersForRoute('admin/bosses'))->toBe([]);
        });

        it('Options de groupes avec des middlewares et les parametres', function (): void {
            setRequestMethod('GET');
            $routes = getCollector();

            $routes->group(
                'admin',
                ['middleware' => 'role:admin,manager'],
                static function ($routes): void {
                    $routes->add('users', '\Users::list');
                }
            );

            expect($routes->isFiltered('admin/users'))->toBeTruthy();
            expect($routes->isFiltered('admin/franky'))->toBeFalsy();
            expect($routes->getFiltersForRoute('admin/users'))->toBe(['role:admin,manager']);
        });

        it('Options de decalage', function (): void {
            setRequestMethod('GET');
            $routes = getCollector();

            $routes->get('users/(:num)', 'users/show/$1', ['offset' => 1]);
            $expected = ['users/([0-9]+)' => '\users/show/$2'];
            expect($routes->getRoutes())->toBe($expected);
        });

        it('Options de routes identiques pour deux routes', function (): void {
           $collections = [
				[
					[
						'foo' => 'options1',
					],
					[
						'foo' => 'options2',
					],
				],
				[
					[
						'as'  => 'admin',
						'foo' => 'options1',
					],
					[
						'foo' => 'options2',
					],
				],
				[
					[
						'foo' => 'options1',
					],
					[
						'as'  => 'admin',
						'foo' => 'options2',
					],
				],
				[
					[
						'as'  => 'admin',
						'foo' => 'options1',
					],
					[
						'as'  => 'admin',
						'foo' => 'options2',
					],
				],
			];

			foreach ($collections as $o) {
				$routes = getCollector();

				// Il s'agit de la première route pour `administrator`.
				$routes->get(
					'administrator',
					static function (): void {},
					$o[0]
				);
				// La deuxième route pour `administrator` doit être ignorée.
				$routes->get(
					'administrator',
					static function (): void {},
					$o[1]
				);

				$options = $routes->getRoutesOptions('administrator');

       			expect($options)->toBe($o[0]);
			}
		});
    });

    describe('Resource & presenter', function (): void {
        it('Échafaudages de ressources correctement', function (): void {
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

        it('Échafaudages de ressources d\'API correctement', function (): void {
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

        it('Échafaudages correct de presenter', function (): void {
            $routes = getCollector();
            $routes->setHTTPVerb('get');
            $routes->presenter('photos');

            expect($routes->getRoutes())->toBe([
                'photos'             => '\Photos::index',
                'photos/new'         => '\Photos::new',
                'photos/edit/(.*)'   => '\Photos::edit/$1',
                'photos/remove/(.*)' => '\Photos::remove/$1',
                'photos/show/(.*)'   => '\Photos::show/$1',
                'photos/(.*)'        => '\Photos::show/$1',
            ]);

            $routes = getCollector();
            $routes->setHTTPVerb('post');
            $routes->presenter('photos');

            expect($routes->getRoutes())->toBe([
                'photos/update/(.*)' => '\Photos::update/$1',
                'photos/delete/(.*)' => '\Photos::delete/$1',
                'photos/create'      => '\Photos::create',
                'photos'             => '\Photos::create',
            ]);
        });

        it('Ressources avec un controleur personnalisé', function (): void {
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

        it('Ressources avec un placeholder personnalisé', function (): void {
            setRequestMethod('GET');
            $routes = getCollector();
            $routes->resource('photos', ['placeholder' => ':num']);

            expect($routes->getRoutes())->toBe([
                'photos'               => '\Photos::index',
                'photos/new'           => '\Photos::new',
                'photos/([0-9]+)/edit' => '\Photos::edit/$1',
                'photos/([0-9]+)'      => '\Photos::show/$1',
            ]);
        });

        it('Ressources avec le placeholder par defaut', function (): void {
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

        it('Ressources avec un bug du placeholder par defaut', function (): void {
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

        it('Ressources avec l\'option <only>', function (): void {
            setRequestMethod('get');
            $routes = getCollector();

            $routes->resource('photos', ['only' => 'index']);

            expect($routes->getRoutes())->toBe([
                'photos' => '\Photos::index',
            ]);
        });

        it('Ressources avec l\'option <except>', function (): void {
            setRequestMethod('get');
            $routes = getCollector();

            $routes->resource('photos', ['except' => 'edit,new']);

            expect($routes->getRoutes())->toBe([
                'photos'      => '\Photos::index',
                'photos/(.*)' => '\Photos::show/$1',
            ]);
        });

        it('Ressources avec l\'option <websafe>', function (): void {
            $routes = getCollector()->setHTTPVerb(Method::POST);

            $routes->resource('photos', ['websafe' => true]);

            expect($routes->getRoutes())->toBe([
                'photos'             => '\Photos::create',
                'photos/(.*)/delete' => '\Photos::delete/$1',
                'photos/(.*)'        => '\Photos::update/$1',
            ]);
        });
    });

    describe('Creation a partir des verbes http appropries', function (): void {
        it('GET', function (): void {
            setRequestMethod('GET');
            $routes = getCollector();

            $routes->get('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('POST', function (): void {
            $routes = getCollector(Method::POST);

            $routes->post('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('GET n\'autorise pas d\'autres methodes', function (): void {
            $routes = getCollector();
            $routes->setHTTPVerb('GET');

            $routes->get('here', 'there');
            $routes->post('from', 'to');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('PUT', function (): void {
            $routes = getCollector(Method::PUT);

            $routes->put('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('DELETE', function (): void {
            $routes = getCollector(Method::DELETE);

            $routes->delete('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('HEAD', function (): void {
            $routes = getCollector(Method::HEAD);

            $routes->head('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('PATCH', function (): void {
            $routes = getCollector(Method::PATCH);

            $routes->patch('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('OPTIONS', function (): void {
            $routes = getCollector(Method::OPTIONS);

            $routes->options('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('CLI', function (): void {
            $routes = getCollector('CLI');

            $routes->cli('here', 'there');

            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });

        it('Route de vue', function (): void {
            $routes = getCollector();

            $routes->view('here', 'hello');

            $route = $routes->getRoutes(Method::GET)['here'];
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

        it('Restriction d\'environnement', function (): void {
            setRequestMethod(Method::GET);
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

		it('Form', function (): void {
            setRequestMethod('GET');
            $routes = getCollector();

            $routes->form('here', 'there');
            expect($routes->getRoutes())->toBe(['here' => '\there']);

            setRequestMethod('POST');
            $routes = getCollector();

            $routes->form('here', 'there');
            expect($routes->getRoutes())->toBe(['here' => '\there']);
        });
    });

    describe('Routes nommées', function (): void {
        it('Route nommée', function (): void {
            $routes = getCollector();

            $routes->add('users', 'Users::index', ['as' => 'namedRoute']);
            $routes->add('profil', 'Users::index', ['name' => 'namedRoute2']);

            expect($routes->reverseRoute('namedRoute'))->toBe('/users');
            expect($routes->reverseRoute('namedRoute2'))->toBe('/profil');
        });

        it('Route nommée avec la locale', function (): void {
            $routes = getCollector();

            $routes->add('{locale}/users', 'Users::index', ['as' => 'namedRoute']);

            expect($routes->reverseRoute('namedRoute'))->toBe('/en/users');
        });

        it('Route nommée avec les parametres', function (): void {
            $routes = getCollector();

            // @TODO Ne mettez aucun espace réservé après (:any).
            // 		 Parce que le nombre de paramètres transmis à la méthode du contrôleur peut changer.
            $routes->add('path/(:any)/to/(:num)', 'myController::goto/$1/$2', ['as' => 'namedRoute']);

            $match = $routes->reverseRoute('namedRoute', 'string', 13);

            expect($match)->toBe('/path/string/to/13');
        });

        it('Route nommée avec les parametres et la locale', function (): void {
            $routes = getCollector();

            // @TODO Ne mettez aucun espace réservé après (:any).
            // 		 Parce que le nombre de paramètres transmis à la méthode du contrôleur peut changer.
            $routes->add('{locale}/path/(:any)/to/(:num)', 'myController::goto/$1/$2', ['as' => 'namedRoute']);

            $match = $routes->reverseRoute('namedRoute', 'string', 13);

            expect($match)->toBe('/en/path/string/to/13');
        });

        it('Route nommée avec la meme URI mais differentes methodes', function (): void {
            $routes = getCollector();

            $routes->get('user/insert', 'myController::goto/$1/$2', ['as' => 'namedRoute1']);
            $routes->post(
                'user/insert',
                static function (): void {},
                ['as' => 'namedRoute2']
            );
            $routes->put(
                'user/insert',
                static function (): void {},
                ['as' => 'namedRoute3']
            );

            $match1 = $routes->reverseRoute('namedRoute1');
            $match2 = $routes->reverseRoute('namedRoute2');
            $match3 = $routes->reverseRoute('namedRoute3');

            expect('/user/insert')->toBe($match1);
            expect('/user/insert')->toBe($match2);
            expect('/user/insert')->toBe($match3);
        });

        it('Route nommée avec la locale, la meme URI mais differentes methodes', function (): void {
            $routes = getCollector();

            $routes->get('{locale}/user/insert', 'myController::goto/$1/$2', ['as' => 'namedRoute1']);
            $routes->post(
                '{locale}/user/insert',
                static function (): void {},
                ['as' => 'namedRoute2']
            );
            $routes->put(
                '{locale}/user/insert',
                static function (): void {},
                ['as' => 'namedRoute3']
            );

            $match1 = $routes->reverseRoute('namedRoute1');
            $match2 = $routes->reverseRoute('namedRoute2');
            $match3 = $routes->reverseRoute('namedRoute3');

            expect('/en/user/insert')->toBe($match1);
            expect('/en/user/insert')->toBe($match2);
            expect('/en/user/insert')->toBe($match3);
        });

        it('Route nommée avec un pipe dans la regex', function (): void {
            $routes = getCollector();

            $routes->get('/system/(this|that)', 'myController::system/$1', ['as' => 'pipedRoute']);

            expect('/system/this')->toBe($routes->reverseRoute('pipedRoute', 'this'));
            expect('/system/that')->toBe($routes->reverseRoute('pipedRoute', 'that'));
        });
    });

    describe('Redirection', function (): void {
        it('Ajout de redirection', function (): void {
            $routes = getCollector();

            // Le deuxième paramètre est soit le nouvel URI vers lequel rediriger, soit le nom d'une route nommée.
            $routes->redirect('users', 'users/index', 307);

            $expected = [
                'users' => 'users/index',
            ];

            expect($routes->getRoutes())->toBe($expected);
            expect($routes->isRedirect('users'))->toBeTruthy();
            expect($routes->getRedirectCode('users'))->toBe(307);
            expect($routes->getRedirectCode('bosses'))->toBe(0);
        });

        it('Ajout de redirection avec une route nommee', function (): void {
            $routes = getCollector();

            $routes->add('zombies', 'Zombies::index', ['as' => 'namedRoute']);
            $routes->redirect('users', 'namedRoute', 307);

            $expected = [
                'zombies' => '\Zombies::index',
                'users'   => ['zombies' => '\Zombies::index'],
            ];

            expect($routes->getRoutes())->toBe($expected);
            expect($routes->isRedirect('users'))->toBeTruthy();
            expect($routes->getRedirectCode('users'))->toBe(307);
        });

        it('Ajout de redirection avec la methode GET', function (): void {
            $routes = getCollector();

            $routes->get('zombies', 'Zombies::index', ['as' => 'namedRoute']);
            $routes->redirect('users', 'namedRoute', 307);

            $expected = [
                'zombies' => '\Zombies::index',
                'users'   => ['zombies' => '\Zombies::index'],
            ];

            expect($routes->getRoutes())->toBe($expected);
            expect($routes->isRedirect('users'))->toBeTruthy();
            expect($routes->getRedirectCode('users'))->toBe(307);
        });

		it('Ajout de redirection permanante', function (): void {
            $routes = getCollector();

            // Le deuxième paramètre est soit le nouvel URI vers lequel rediriger, soit le nom d'une route nommée.
            $routes->permanentRedirect('users', 'users/index');

            $expected = [
                'users' => 'users/index',
            ];

            expect($routes->getRoutes())->toBe($expected);
            expect($routes->isRedirect('users'))->toBeTruthy();
            expect($routes->getRedirectCode('users'))->toBe(301);
        });
    });

    describe('Sous domaines', function (): void {
        it('Hostname', function (): void {
            $_SERVER['HTTP_HOST'] = 'example.com';

            $routes = getCollector();

            $routes->add('from', 'to', ['hostname' => 'example.com']);
            $routes->add('foo', 'bar', ['hostname' => 'foobar.com']);

            expect($routes->getRoutes())->toBe([
                'from' => '\to',
            ]);
        });

        it('Sous domaine', function (): void {
            $_SERVER['HTTP_HOST'] = 'adm.example.com';

            $routes = getCollector();

            $routes->add('/objects/(:alphanum)', 'Admin::objectsList/$1', ['subdomain' => 'adm']);
            $routes->add('/objects/(:alphanum)', 'App::objectsList/$1');

            expect($routes->getRoutes())->toBe([
                'objects/([a-zA-Z0-9]+)' => '\Admin::objectsList/$1',
            ]);
        });

        it('Sous domaine absent', function (): void {
            $_SERVER['HTTP_HOST'] = 'www.example.com';

            $routes = getCollector();

            $routes->add('/objects/(:alphanum)', 'Admin::objectsList/$1', ['subdomain' => 'adm']);
            $routes->add('/objects/(:alphanum)', 'App::objectsList/$1');

            expect($routes->getRoutes())->toBe([
                'objects/([a-zA-Z0-9]+)' => '\App::objectsList/$1',
            ]);
        });

        it('Test avec des sous domaines differents', function (): void {
            $_SERVER['HTTP_HOST'] = 'adm.example.com';

            $routes = getCollector();

            $routes->add('/objects/(:alphanum)', 'Admin::objectsList/$1', ['subdomain' => 'sales']);
            $routes->add('/objects/(:alphanum)', 'App::objectsList/$1');

            expect($routes->getRoutes())->toBe([
                'objects/([a-zA-Z0-9]+)' => '\App::objectsList/$1',
            ]);
        });

        it('Test avec le sous domaine www', function (): void {
            $routes = getCollector();

            $_SERVER['HTTP_HOST'] = 'www.example.com';

            $routes->add('/objects/(:alphanum)', 'Admin::objectsList/$1', ['subdomain' => 'sales']);
            $routes->add('/objects/(:alphanum)', 'App::objectsList/$1');

            expect($routes->getRoutes())->toBe([
                'objects/([a-zA-Z0-9]+)' => '\App::objectsList/$1',
            ]);
        });

        it('Test avec le sous domaine .co', function (): void {
            $routes = getCollector();

            $_SERVER['HTTP_HOST'] = 'example.co.uk';

            $routes->add('/objects/(:alphanum)', 'Admin::objectsList/$1', ['subdomain' => 'sales']);
            $routes->add('/objects/(:alphanum)', 'App::objectsList/$1');

            expect($routes->getRoutes())->toBe([
                'objects/([a-zA-Z0-9]+)' => '\App::objectsList/$1',
            ]);
        });

        it('Test avec de differents sous domaine absent', function (): void {
            $_SERVER['HTTP_HOST'] = 'adm.example.com';

            $routes = getCollector();

            $routes->add('/objects/(:alphanum)', 'Admin::objectsList/$1', ['subdomain' => 'nothere']);
            $routes->add('/objects/(:alphanum)', 'App::objectsList/$1', ['subdomain' => '*']);

            expect($routes->getRoutes())->toBe([
                'objects/([a-zA-Z0-9]+)' => '\App::objectsList/$1',
            ]);
        });

        it('Test sans sous domaine et le point', function (): void {
            $_SERVER['HTTP_HOST'] = 'example.com';

            $routes = getCollector();

            $routes->add('/objects/(:alphanum)', 'App::objectsList/$1', ['subdomain' => '*']);

            expect($routes->getRoutes())->toBe([]);
        });

        it('Test avec les sous domaine en ordre', function (): void {
            $_SERVER['HTTP_HOST'] = 'adm.example.com';

            $routes = getCollector();

            $routes->add('/objects/(:alphanum)', 'App::objectsList/$1');
            $routes->add('/objects/(:alphanum)', 'Admin::objectsList/$1', ['subdomain' => 'adm']);

            expect($routes->getRoutes())->toBe([
                'objects/([a-zA-Z0-9]+)' => '\Admin::objectsList/$1',
            ]);
        });
    });

	describe('Multiples nom d\'hotes', function (): void {
		it('La surchage d\'une route accepte plusieurs hotes', function (): void {
			$_SERVER['HTTP_HOST'] = 'doc.domain.com';

			setRequestMethod(Method::GET);
            $router = Services::router(routes: $routes = getCollector(), shared: false);

			$routes->setDefaultNamespace('App\Controllers');
			$routes->setDefaultController('Home');
			$routes->setDefaultMethod('index');

			$routes->get('/', 'Home::index', ['as' => 'ddd']);
			$routes->get('/', '\App\Controllers\Site\CDoc::index', ['hostname' => 'doc.domain.com', 'as' => 'doc_index']);

            expect($router->handle('/'))->toBe('App\\Controllers\\Site\\CDocController');
		});

		it('Le routeur fonctionne normalement avec une route qui accepte plusieurs hotes', function (): void {
			$_SERVER['HTTP_HOST'] = 'two.domain.com';

            $router = Services::router(routes: $routes = getCollector(), shared: false);

			$routes->setDefaultNamespace('App\Controllers');
			$routes->setDefaultController('Home');
			$routes->setDefaultMethod('index');

			$routes->get('/', 'Home::index', ['as' => 'home']);
			$routes->get('/', '\App\Controllers\Site\CDoc::index', ['hostname' => ['one.domain.com', 'two.domain.com', 'three.domain.com']]);

            expect($router->handle('/'))->toBe('App\\Controllers\\Site\\CDocController');
		});

		it('Echec lorsqu\'aucun hote ne correspond a ceux specifiés par la route', function (): void {
			$_SERVER['HTTP_HOST'] = 'dpc.domain.com';

            $router = Services::router(routes: $routes = getCollector(), shared: false);

			$routes->setDefaultNamespace('App\Controllers');
			$routes->setDefaultController('Home');
			$routes->setDefaultMethod('index');

			$routes->get('/', 'Home::index', ['as' => 'home']);
			$routes->get('/', '\App\Controllers\Site\CDoc::index', ['hostname' => ['one.domain.com', 'two.domain.com', 'three.domain.com']]);

            expect($router->handle('/'))->toBe('App\\Controllers\\HomeController');
		});
	});

	describe('Modules', function (): void {
		it('Decouverte des routes de modules', function (): void {
			$config = ['SampleSpace' => TEST_PATH . 'support/module'];

			setRequestMethod(Method::GET);
        	$routes = getCollector(Method::GET, $config);

        	$match = $routes->getRoutes();

			skipIf($match === []);

			expect($match)->toContainKey('testing');
	        expect($match['testing'])->toBe('\TestController::index');
		});

		it('La decouverte des routes de modules autorise l\'application a modifier une route', function (): void {
			$config = ['SampleSpace' => TEST_PATH . 'support/module'];

        	$routes = getCollector(Method::GET, $config);

			$routes->add('testing', 'MainRoutes::index', ['as' => 'testing-index']);

        	$match = $routes->getRoutes();

			expect($match)->toContainKey('testing');
	        expect($match['testing'])->toBe('\MainRoutes::index');
		});
	});

    describe('Fallback', function (): void {
        it('Fallback', function (): void {
            setRequestMethod('GET');
            $routes = getCollector();

            expect($routes->get404Override())->toBeNull();
        });

        it('Fallback sous forme de chaine', function (): void {
            setRequestMethod('GET');
            $routes = getCollector();

            $routes->fallback('Explode');
            expect($routes->get404Override())->toBe('Explode');
        });

        it('Fallback sous forme de callback', function (): void {
            setRequestMethod('GET');
            $routes = getCollector();

            $routes->fallback(static function (): void {
                echo 'Explode now';
            });
            expect($routes->get404Override())->toBeAnInstanceOf('closure');
        });
    });

    describe('Routage inversé', function (): void {
        it('Reverse route avec une chaine vide', function (): void {
            $routes = getCollector();
            $routes->add('/', 'Home::index');

            expect($routes->reverseRoute(''))->toBeFalsy();
        });

        it('Reverse route simple', function (): void {
            $routes = getCollector();

            // @TODO Ne mettez aucun espace réservé après (:any).
            // 		 Parce que le nombre de paramètres transmis à la méthode du contrôleur peut changer.
            $routes->add('path/(:any)/to/(:num)', 'myController::goto/$1/$2');

            $match = $routes->reverseRoute('myController::goto', 'string', 13);

            expect($match)->toBe('/path/string/to/13');
        });

        it('Reverse route avec la locale', function (): void {
            $routes = getCollector();

            // @TODO Ne mettez aucun espace réservé après (:any).
            // 		 Parce que le nombre de paramètres transmis à la méthode du contrôleur peut changer.
            $routes->add('{locale}/path/(:any)/to/(:num)', 'myController::goto/$1/$2');

            $match = $routes->reverseRoute('myController::goto', 'string', 13);

            expect($match)->toBe('/en/path/string/to/13');
        });

        it('Reverse route retourne false lorsque le nombre de parametres est incorrect', function (): void {
            $routes = getCollector();

            // @TODO Ne mettez aucun espace réservé après (:any).
            // 		 Parce que le nombre de paramètres transmis à la méthode du contrôleur peut changer.
            $routes->add('path/(:any)/to/(:num)', 'myController::goto/$1');

            $match = $routes->reverseRoute('myController::goto', 'string', 13);

            expect($match)->toBeFalsy();
        });

        it('Reverse route retourne false lorsqu\'il y\'a pas de correspondance', function (): void {
            $routes = getCollector();

            // @TODO Ne mettez aucun espace réservé après (:any).
            // 		 Parce que le nombre de paramètres transmis à la méthode du contrôleur peut changer.
            $routes->add('path/(:any)/to/(:num)', 'myController::goto/$1/$2');

            $match = $routes->reverseRoute('myBadController::goto', 'string', 13);

            expect($match)->toBeFalsy();
        });

        it('Reverse route leve une exception en cas de mauvais types de parametres', function (): void {
            $routes = getCollector();

            // @TODO Ne mettez aucun espace réservé après (:any).
            // 		 Parce que le nombre de paramètres transmis à la méthode du contrôleur peut changer.
            $routes->add('path/(:any)/to/(:num)', 'myController::goto/$1/$2');

            expect(static function () use ($routes): void {
                $routes->reverseRoute('myController::goto', 13, 'string');
            })->toThrow(new RouterException());
        });

        it('Reverse route simple avec la locale', function (): void {
            $routes = getCollector();

            $routes->add('{locale}/contact', 'myController::goto');

            $match = $routes->reverseRoute('myController::goto');

            expect($match)->toBe('/en/contact');
        });

        it('Reverse route avec le namespace par defaut', function (): void {
            $routes = getCollector();
            $routes->setDefaultNamespace('App\Controllers');

            $routes->get('admin/(:num)/gallery(:any)', 'Admin\Galleries::showUserGallery/$1/$2');

            $match = $routes->reverseRoute('Admin\Galleries::showUserGallery', 15, 12);

            expect($match)->toBe('/admin/15/gallery12');
        });

        it('Reverse route avec une route nommee', function (): void {
            $routes = getCollector();

            $routes->get('test/(:segment)/(:segment)', 'TestController::test/$1/$2', ['as' => 'testRouter']);

            $match = $routes->reverseRoute('testRouter', 1, 2);

            expect($match)->toBe('/test/1/2');
        });

        it('Reverse route avec des queries string', function (): void {
            $routes = getCollector();

            $routes->get('test/(:segment)/(:segment)', 'TestController::test/$1/$2', ['as' => 'testRouter']);

            $match = $routes->reverseRoute('testRouter', 1, 2, ['query' => 'string']);

            expect($match)->toBe('/test/1/2?query=string');
        });

		it('Reverse route avec des queries string et la locale', function (): void {
			$routes = getCollector();

            $routes->add('{locale}/contact', 'myController::goto');

            $match = $routes->reverseRoute('myController::goto', ['foo' => 'bar', 'baz' => 'faa']);

            expect($match)->toBe('/en/contact?foo=bar&baz=faa');
        });

        it('Reverse route avec une route nommee et la locale', function (): void {
            $routes = getCollector();

            $routes->get('{locale}/test/(:segment)/(:segment)', 'TestController::test/$1/$2', ['as' => 'testRouter']);

            $match = $routes->reverseRoute('testRouter', 1, 2);

            expect($match)->toBe('/en/test/1/2');
        });

        it('Reverse route avec une closure', function (): void {
            $routes = getCollector();

            $routes->add('login', static function (): void {
            });

            $match = $routes->reverseRoute('login');

            expect($match)->toBe('/login');
        });

        it('Reverse route avec une closure qui ne correspond pas', function (): void {
            $routes = getCollector();

            $routes->add('login', static function (): void {
            });

            $match = $routes->reverseRoute('foobar');

            expect($match)->toBeFalsy();
        });

        it('Routage inversé avec correspondance de sous-domaine', function (): void {
            $_SERVER['HTTP_HOST'] = 'doc.example.com';

			setRequestMethod(Method::GET);
            $routes = getCollector();

            $routes->get('i/(:any)', 'App\Controllers\Site\CDoc::item/$1', ['subdomain' => 'doc', 'as' => 'doc_item']);

            expect($routes->reverseRoute('doc_item', 'sth'))->toBe('/i/sth');
        });

        it('Routage inversé sans correspondance de sous-domaine', function (): void {
            $_SERVER['HTTP_HOST'] = 'dev.example.com';

			setRequestMethod(Method::GET);
            $routes = getCollector();

            $routes->get('i/(:any)', 'App\Controllers\Site\CDoc::item/$1', ['subdomain' => 'doc', 'as' => 'doc_item']);

            expect($routes->reverseRoute('doc_item', 'sth'))->toBeFalsy();
        });

        it('Routage inversé sans sous-domaine', function (): void {
            $_SERVER['HTTP_HOST'] = 'example.com';

			setRequestMethod(Method::GET);
            $routes = getCollector();

            $routes->get('i/(:any)', 'App\Controllers\Site\CDoc::item/$1', ['subdomain' => 'doc', 'as' => 'doc_item']);

            expect($routes->reverseRoute('doc_item', 'sth'))->toBeFalsy();
        });

		it('Routage inversé avec correspondance de sous-domaine generique', function (): void {
            $_SERVER['HTTP_HOST'] = 'doc.example.com';

			setRequestMethod(Method::GET);
            $routes = getCollector();

            $routes->get('i/(:any)', 'App\Controllers\Site\CDoc::item/$1', ['subdomain' => '*', 'as' => 'doc_item']);

            expect($routes->reverseRoute('doc_item', 'sth'))->toBe('/i/sth');
        });

        it('Routage inversé sans sous-domaine generique', function (): void {
            $_SERVER['HTTP_HOST'] = 'example.com';

			setRequestMethod(Method::GET);
            $routes = getCollector();

            $routes->get('i/(:any)', 'App\Controllers\Site\CDoc::item/$1', ['subdomain' => '*', 'as' => 'doc_item']);

            expect($routes->reverseRoute('doc_item', 'sth'))->toBeFalsy();
        });

		it('Routage inversé sans sous-domaine correspondant', function (): void {
            $_SERVER['HTTP_HOST'] = 'doc.example.com';

			setRequestMethod(Method::GET);
            $routes = getCollector();

            $routes->get('i/(:any)', 'App\Controllers\Site\CDoc::item/$1', ['hostname' => 'example.com', 'as' => 'doc_item']);

            expect($routes->reverseRoute('doc_item', 'sth'))->toBeFalsy();
        });

        it('Routage inversé sans sous-domaine avec le hostname', function (): void {
            $_SERVER['HTTP_HOST'] = 'example.com';

			setRequestMethod(Method::GET);
            $routes = getCollector();

            $routes->get('i/(:any)', 'App\Controllers\Site\CDoc::item/$1', ['hostname' => 'example.com', 'as' => 'doc_item']);

            expect($routes->reverseRoute('doc_item', 'sth'))->toBe('/i/sth');
        });
    });

	describe('Surchage du router', function (): void {
        it('Zero comme chemin URI', function (): void {
            $routes = getCollector();
			$routes->setDefaultNamespace('App\Controllers');

            $router = new Router($routes, Services::request());

			$routes->get('/0', 'Core\Home::index');

			$expects = 'App\Controllers\Core\HomeController';

            expect($router->handle('/0'))->toBe($expects);
        });

		it('Écrasement de routes dans différents sous-domaines', function (): void {
            $_SERVER['HTTP_HOST'] = 'doc.domain.com';
			setRequestMethod(Method::GET);

			$routes = getCollector(Method::GET);
			$router = new Router($routes, Services::request());

			$routes->get('/', '\App\Controllers\Site\CDoc::index', ['subdomain' => 'doc', 'as' => 'doc_index']);
			$routes->get('/', 'Home::index', ['subdomain' => 'dev']);

			$expects = 'App\Controllers\Site\CDocController';

			expect($router->handle('/'))->toBe($expects);
        });

		it('Route écrasant deux règles', function (): void {
            $_SERVER['HTTP_HOST'] = 'doc.domain.com';
			setRequestMethod(Method::GET);

			$routes = getCollector(Method::GET);
			$router = new Router($routes, Services::request());

			// Le sous-domaine de l'URL actuel est `doc`, donc cette route est enregistrée.
			$routes->get('/', '\App\Controllers\Site\CDoc::index', ['subdomain' => 'doc', 'as' => 'doc_index']);
			// La route du sous-domaine est déjà enregistrée, cette route n'est donc pas enregistrée.
			$routes->get('/', 'Home::index');

			$expects = 'App\Controllers\Site\CDocController';

			expect($router->handle('/'))->toBe($expects);
        });

		it('Écrasement de deux règles par le router, le dernier s\'applique', function (): void {
            $_SERVER['HTTP_HOST'] = 'doc.domain.com';
			setRequestMethod(Method::GET);

			$routes = getCollector(Method::GET);
			$router = new Router($routes, Services::request());

			$routes->get('/', 'Home::index');
	        $routes->get('/', '\App\Controllers\Site\CDoc::index', ['subdomain' => 'doc', 'as' => 'doc_index']);

			$expects = 'App\Controllers\Site\CDocController';

			expect($router->handle('/'))->toBe($expects);
        });

		it('Écrasement de la route lorsque le sous-domaine correspond', function (): void {
            $_SERVER['HTTP_HOST'] = 'doc.domain.com';
			setRequestMethod(Method::GET);

			$routes = getCollector(Method::GET);
			$router = new Router($routes, Services::request());

			$routes->get('/', 'Home::index', ['as' => 'ddd']);
	        $routes->get('/', '\App\Controllers\Site\CDoc::index', ['subdomain' => 'doc', 'as' => 'doc_index']);

			$expects = 'App\Controllers\Site\CDocController';

			expect($router->handle('/'))->toBe($expects);
        });

		it('Écrasement de la route lorsque le nom d\'hote correspond', function (): void {
            $_SERVER['HTTP_HOST'] = 'doc.domain.com';
			setRequestMethod(Method::GET);

			$routes = getCollector(Method::GET);
			$router = new Router($routes, Services::request());

			$routes->get('/', 'Home::index', ['as' => 'ddd']);
	        $routes->get('/', '\App\Controllers\Site\CDoc::index', ['hostname' => 'doc.domain.com', 'as' => 'doc_index']);

			$expects = 'App\Controllers\Site\CDocController';

			expect($router->handle('/'))->toBe($expects);
        });
	});

	describe('Priorite de route', function (): void {
		it('Priorité detectée', function (): void {
			$collection = getCollector();

			expect(ReflectionHelper::getPrivateProperty($collection, 'prioritizeDetected'))->toBeFalsy();

			$collection->add('/', 'Controller::method', ['priority' => 0]);
			expect(ReflectionHelper::getPrivateProperty($collection, 'prioritizeDetected'))->toBeFalsy();

			$collection->add('priority', 'Controller::method', ['priority' => 1]);
			expect(ReflectionHelper::getPrivateProperty($collection, 'prioritizeDetected'))->toBeTruthy();
		});

		it('Valeur de la priorité', function (): void {
			$collection = getCollector();

			$collection->add('string', 'Controller::method', ['priority' => 'string']);
        	expect($collection->getRoutesOptions('string')['priority'])->toBe(0);

			$collection->add('negative-integer', 'Controller::method', ['priority' => -1]);
        	expect($collection->getRoutesOptions('negative-integer')['priority'])->toBe(1);

			$collection->add('string-negative-integer', 'Controller::method', ['priority' => '-1']);
        	expect($collection->getRoutesOptions('string-negative-integer')['priority'])->toBe(1);
        });
	});

	describe('RegisteredController', function (): void {
		it('GetRegisteredControllers Retourne le contrôleur pour le verbe HTTP', function (): void {
			$collection = getCollector();

			$collection->get('test', '\App\Controllers\Hello::get');
			$collection->post('test', '\App\Controllers\Hello::post');

			$routes = $collection->getRegisteredControllers(Method::GET);

			$expects = [
				'\App\Controllers\Hello',
			];
			expect($routes)->toBe($expects);

			$routes = $collection->getRegisteredControllers(Method::POST);

			$expects = [
				'\App\Controllers\Hello',
			];
			expect($routes)->toBe($expects);
		});

		it('GetRegisteredControllers Renvoie deux contrôleurs', function (): void {
			$collection = getCollector();

			$collection->post('test', '\App\Controllers\Test::post');
			$collection->post('hello', '\App\Controllers\Hello::post');

			$routes = $collection->getRegisteredControllers(Method::POST);

			$expects = [
				'\App\Controllers\Test',
				'\App\Controllers\Hello',
			];

			expect($routes)->toBe($expects);
		});

		it('GetRegisteredControllers renvoie un seul contrôleur lorsque deux routes ont des méthodes différentes', function (): void {
			$collection = getCollector();

			$collection->post('test', '\App\Controllers\Test::test');
			$collection->post('hello', '\App\Controllers\Test::hello');

			$routes = $collection->getRegisteredControllers(Method::POST);

			$expects = [
				'\App\Controllers\Test',
			];

			expect($routes)->toBe($expects);
		});

		it('GetRegisteredControllers Renvoie tous les contrôleurs', function (): void {
			$collection = getCollector();

			$collection->get('test', '\App\Controllers\HelloGet::get');
			$collection->post('test', '\App\Controllers\HelloPost::post');
			$collection->post('hello', '\App\Controllers\TestPost::hello');

			$routes = $collection->getRegisteredControllers('*');

			$expects = [
				'\App\Controllers\HelloGet',
				'\App\Controllers\HelloPost',
				'\App\Controllers\TestPost',
			];

			expect($routes)->toBe($expects);
		});

		it('GetRegisteredControllers Retourne le contrôleur par la méthode Add', function (): void {
			$collection = getCollector();

			$collection->get('test', '\App\Controllers\Hello::get');
			$collection->add('hello', '\App\Controllers\Test::hello');

			$routes = $collection->getRegisteredControllers(Method::GET);

			$expects = [
				'\App\Controllers\Hello',
				'\App\Controllers\Test',
			];

			expect($routes)->toBe($expects);
		});

		it('GetRegisteredControllers ne renvoie pas de closures', function (): void {
			$collection = getCollector();

			$collection->get('feed', static function (): void {
			});

			$routes = $collection->getRegisteredControllers('*');

			$expects = [];

			expect($routes)->toBe($expects);
		});
	});

	describe('FQCN', function (): void {
		$n = [
			'with \\ prefix'    => ['Spec\BlitzPHP\App\Controllers'],
			'without \\ prefix' => ['Spec\BlitzPHP\App\Controllers'],
		];

		it('ControllerName renvoie le FQCN via AutoRoute', function () use ($n): void {
			foreach ($n as [$namespace]) {
				$routes = getCollector();
				$routes->setAutoRoute(true);
				$routes->setDefaultNamespace($namespace);


				$router = new Router($routes, Services::request());
				$router->handle('/product');

				expect($router->controllerName())->toBe(ProductController::class);
			}
		});

		it('ControllerName renvoie le FQCN sans AutoRoute', function () use ($n): void {
			setRequestMethod(Method::GET);
			foreach ($n as [$namespace]) {
				$routes = getCollector();
				$routes->setAutoRoute(false);
				$routes->setDefaultNamespace($namespace);
				$routes->get('/product', 'Product');

				$router = new Router($routes, Services::request());
				$router->handle('/product');

				expect($router->controllerName())->toBe(ProductController::class);
			}
		});
	});
});
