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
use BlitzPHP\Exceptions\BadRequestException;
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Router\RouteCollection;
use Spec\BlitzPHP\App\Middlewares\CustomMiddleware;

use function Kahlan\expect;

describe('Router', function (): void {
	beforeAll(function (): void {
		$this->createCollection = function(array $config = []): RouteCollection {
			$default = array_merge(config('routing'), $config);

			return (new RouteCollection(service('locator'), (object) $default))
					->setDefaultNamespace('\\');
		};
	});

    beforeEach(function (): void {
        $this->collection = single_service('routes')->setDefaultNamespace('\\');

        $routes = [
            '/'                                               => 'Home::index',
            'users'                                           => 'Users::index',
            'user-setting/show-list'                          => 'User_setting::show_list',
            'user-setting/(:segment)'                         => 'User_setting::detail/$1',
            'posts'                                           => 'Blog::posts',
            'pages'                                           => 'App\Pages::list_all',
            'posts/(:num)'                                    => 'Blog::show/$1',
            'posts/(:num)/edit'                               => 'Blog::edit/$1',
            'shop/(:num)'                                     => 'Shop::show',
            'shop/(:num)/edit'                                => 'Shop::edit',
            'books/(:num)/(:alpha)/(:num)'                    => 'Blog::show/$3/$1',
            'closure/(:num)/(:alpha)'                         => static fn ($num, $str) => $num . '-' . $str,
            '{locale}/pages'                                  => 'App\Pages::list_all',
            'test/(:any)/lang/{locale}'                       => 'App\Pages::list_all',
            'admin/admins'                                    => 'App\Admin\Admins::list_all',
            'admin/admins/edit/(:any)'                        => 'App/Admin/Admins::edit_show/$1',
            '/some/slash'                                     => 'App\Slash::index',
            'objects/(:segment)/sort/(:segment)/([A-Z]{3,7})' => 'AdminList::objectsSortCreate/$1/$2/$3',
            '(:segment)/(:segment)/(:segment)'                => '$2::$3/$1',
        ];

        $this->collection->map($routes);
        $this->request = service('request')->withMethod('GET');
    });

    describe('URI', function (): void {
        it("L'URI vide correspond aux valeurs par défaut", function (): void {
            $router = single_service('router', $this->collection, $this->request);
            $router->handle('');

            expect('HomeController')->toBe($router->controllerName());
            expect('index')->toBe($router->methodName());
        });

        it('Zéro comme chemin URI', function (): void {
            $router = single_service('router', $this->collection, $this->request);

            expect(static function () use ($router): void {
                $router->handle('0');
            })->toThrow(new PageNotFoundException());
        });

		it('Caracteres non autorisés', function (): void {
            $router = single_service('router', $this->collection, $this->request);

            expect(static function () use ($router): void {
                $router->handle('test/%3Ca%3E');
            })->toThrow(new BadRequestException());
        });

        it("Mappages d'URI vers le contrôleur", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('users');

            expect('UsersController')->toBe($router->controllerName());
            expect('index')->toBe($router->methodName());
        });

        it("Mappages d'URI avec une barre oblique finale vers le contrôleur", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('users/');

            expect('UsersController')->toBe($router->controllerName());
            expect('index')->toBe($router->methodName());
        });

        it("Mappages d'URI vers une méthode alternative du contrôleur", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('posts');

            expect('BlogController')->toBe($router->controllerName());
            expect('posts')->toBe($router->methodName());
        });

        it("Mappage d'URI vers le contrôleur ayant un sous namespace", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('pages');

            expect('App\PagesController')->toBe($router->controllerName());
            expect('list_all')->toBe($router->methodName());
        });

        it("Mappage d'URI vers les paramètres aux références arrière", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('posts/123');

            expect('show')->toBe($router->methodName());
            expect(['123'])->toBe($router->params());
        });

        it("Mappage d'URI vers les paramètres aux références arrière réarrangées", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('posts/123/edit');

            expect('edit')->toBe($router->methodName());
            expect(['123'])->toBe($router->params());
        });

        it("Mappage d'URI vers les paramètres aux références arrière avec les inutilisés", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('books/123/sometitle/456');

            expect('show')->toBe($router->methodName());
            expect(['456', '123'])->toBe($router->params());
        });

        xit("Mappage d'URI vers les paramètres sans utilisation de références arrière", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('shop/123');

            expect('show')->toBe($router->methodName());
            expect('ShopController')->toBe($router->controllerName());
            expect(['123'])->toBe($router->params());
        });

        xit("Mappage d'URI vers les paramètres sans utilisation de références arrière", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('shop/123/edit');

            expect('edit')->toBe($router->methodName());
            expect(['123'])->toBe($router->params());
        });

        it("Mappages d'URI avec plusieurs paramètres", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('objects/123/sort/abc/FOO');

            expect('objectsSortCreate')->toBe($router->methodName());
            expect(['123', 'abc', 'FOO'])->toBe($router->params());
        });

        it("Mappages d'URI avec plusieurs paramètres et une barre oblique de fin", function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('objects/123/sort/abc/FOO/');

            expect('objectsSortCreate')->toBe($router->methodName());
            expect(['123', 'abc', 'FOO'])->toBe($router->params());
        });

        it('Closures', function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('closure/123/alpha');

            $closure = $router->controllerName();

            $expects = $closure(...$router->params());

            expect($closure)->toBeAnInstanceOf(Closure::class);
            expect($expects)->toBe('123-alpha');
        });
    });

    describe('Route', function (): void {
        it(': Message d\'exception quand la route n\'existe pas', function (): void {
            $router = single_service('router', $this->collection, $this->request);

            expect(static function () use ($router): void {
                $router->handle('url/not-exists');
            })->toThrow(new PageNotFoundException("Impossible de trouver une route pour 'GET: url/not-exists'."));
        });

        it(': Détection de la langue', function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('fr/pages');

            expect($router->hasLocale())->toBeTruthy();
            expect($router->getLocale())->toBe('fr');

            $router->handle('test/123/lang/bg');

            expect($router->hasLocale())->toBeTruthy();
            expect($router->getLocale())->toBe('bg');
        });

        it(': Route resource', function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('admin/admins');

            expect($router->controllerName())->toBe('App\Admin\AdminsController');
            expect($router->methodName())->toBe('list_all');
        });

        it(': Route avec barre oblique dans le nom du contrôleur', function (): void {
            $router = single_service('router', $this->collection, $this->request);

            expect(static function () use ($router): void {
                $router->handle('admin/admins/edit/1');
            })->toThrow(new RouterException('The namespace delimiter is a backslash (\), not a slash (/). Route handler: \App/Admin/Admins::edit_show/$1'));
        });

        it(': Route avec barre oblique en tête', function (): void {
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('some/slash');

            expect($router->controllerName())->toBe('App\SlashController');
            expect($router->methodName())->toBe('index');
        });

        it(': Routage avec contrôleur dynamique', function (): void {
            $router = single_service('router', $this->collection, $this->request);

            expect(static function () use ($router): void {
                $router->handle('en/zoo/bar');
            })->toThrow(new RouterException('A dynamic controller is not allowed for security reasons. Route handler: \$2::$3/$1'));
        });

        it(': Options de route', function (): void {
            $this->collection->add('foo', static function (): void {}, [
                'as'  => 'login',
                'foo' => 'baz',
            ]);
            $this->collection->add('baz', static function (): void {}, [
                'as'  => 'admin',
                'foo' => 'bar',
            ]);

            $router = single_service('router', $this->collection, $this->request);

            $router->handle('foo');

            expect($router->getMatchedRouteOptions())->toBe(['as' => 'login', 'foo' => 'baz']);
        });

        it(': Ordre de routage', function (): void {
            $this->collection->post('auth', 'Main::auth_post');
            $this->collection->add('auth', 'Main::index');

            $router = single_service('router', $this->collection, $this->request);
            $this->collection->setHTTPVerb('post');

            $router->handle('auth');

            expect($router->controllerName())->toBe('MainController');
            expect($router->methodName())->toBe('auth_post');
        });

        it(': Ordre de priorité de routage', function (): void {
            $this->collection->add('main', 'Main::index');
            $this->collection->add('(.*)', 'Main::wildcard', ['priority' => 1]);
            $this->collection->add('module', 'Module::index');

            $router = single_service('router', $this->collection, $this->request);
            $this->collection->setHTTPVerb(Method::GET);

            $router->handle('module');
            expect($router->controllerName())->toBe('MainController');
            expect($router->methodName())->toBe('wildcard');

            $this->collection->setPrioritize();

            $router->handle('module');
            expect($router->controllerName())->toBe('ModuleController');
            expect($router->methodName())->toBe('index');
        });

        it(': Expression régulière avec Unicode', function (): void {
            config()->set('app.permitted_uri_chars', 'a-z 0-9~%.:_\-\x{0980}-\x{09ff}');

			$this->collection->get('news/([a-z0-9\x{0980}-\x{09ff}-]+)', 'News::view/$1');
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('news/a0%E0%A6%80%E0%A7%BF-');
            expect($router->controllerName())->toBe('NewsController');
            expect($router->methodName())->toBe('view');
            expect($router->params())->toBe(['a0ঀ৿-']);

			config()->reset('app.permitted_uri_chars');
        });

        it(': Espace réservé d\'expression régulière avec Unicode', function (): void {
            config()->set('app.permitted_uri_chars', 'a-z 0-9~%.:_\-\x{0980}-\x{09ff}');

			$this->collection->addPlaceholder('custom', '[a-z0-9\x{0980}-\x{09ff}-]+');
            $this->collection->get('news/(:custom)', 'News::view/$1');
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('news/a0%E0%A6%80%E0%A7%BF-');
            expect($router->controllerName())->toBe('NewsController');
            expect($router->methodName())->toBe('view');
            expect($router->params())->toBe(['a0ঀ৿-']);

			config()->reset('app.permitted_uri_chars');
        });
    });

    describe('Groupes et middlewares', function (): void {
        it(': Le routeur fonctionne avec les middlewares', function (): void {
            $this->collection->group('foo', ['middleware' => 'test'], static function (RouteCollection $routes): void {
                $routes->add('bar', 'TestController::foobar');
            });

            $router = single_service('router', $this->collection, $this->request);

            $router->handle('foo/bar');
            expect($router->controllerName())->toBe('TestController');
            expect($router->methodName())->toBe('foobar');
            expect($router->getMiddlewares())->toBe(['test']);
        });

        it(': Ressources groupées avec des route ayant les middlewares', function (): void {
            $group = [
                'api',
                [
                    'namespace'  => 'App\Controllers\Api',
                    'middleware' => 'api-auth',
                ],
                static function (RouteCollection $routes): void {
                    $routes->resource('posts', [
                        'controller' => 'PostController',
                    ]);
                },
            ];

            $this->collection->group(...$group);

            $router = single_service('router', $this->collection, $this->request);

            $router->handle('api/posts');

            expect($router->controllerName())->toBe('App\Controllers\Api\PostController');
            expect($router->methodName())->toBe('index');
            expect($router->getMiddlewares())->toBe(['api-auth']);
        });

        it(': Le routeur fonctionne avec un nom de classe comme middleware', function (): void {
            $this->collection->add('foo', 'TestController::foo', ['middleware' => CustomMiddleware::class]);

            $router = single_service('router', $this->collection, $this->request);

            $router->handle('foo');
            expect($router->controllerName())->toBe('TestController');
            expect($router->methodName())->toBe('foo');
            expect($router->getMiddlewares())->toBe([CustomMiddleware::class]);
        });

        it(': Le routeur fonctionne avec plusieurs middlewares', function (): void {
            $this->collection->add('foo', 'TestController::foo', ['middleware' => ['filter1', 'filter2:param']]);

            $router = single_service('router', $this->collection, $this->request);

            $router->handle('foo');
            expect($router->controllerName())->toBe('TestController');
            expect($router->methodName())->toBe('foo');
            expect($router->getMiddlewares())->toBe(['filter1', 'filter2:param']);
        });

        it(': Correspond correctement aux verbes mixtes', function (): void {
            $this->collection->setHTTPVerb(Method::GET);

            $this->collection->add('/', 'Home::index');
            $this->collection->get('news', 'News::index');
            $this->collection->get('news/(:segment)', 'News::view/$1');
            $this->collection->add('(:any)', 'Pages::view/$1');

            $router = single_service('router', $this->collection, $this->request);

            $router->handle('/');
            expect($router->controllerName())->toBe('HomeController');
            expect($router->methodName())->toBe('index');

            $router->handle('news');
            expect($router->controllerName())->toBe('NewsController');
            expect($router->methodName())->toBe('index');

            $router->handle('news/daily');
            expect($router->controllerName())->toBe('NewsController');
            expect($router->methodName())->toBe('view');

            $router->handle('about');
            expect($router->controllerName())->toBe('PagesController');
            expect($router->methodName())->toBe('view');
        });
    });

    describe('Traduction des tirets d\'URI', function (): void {
        it(': Traduire les tirets URI en snake case (methode) et pascal case (controleur) lorsqu\'on desactive la traduction d\'URI', function (): void {
            $this->collection->setTranslateURIDashes(false);

            $router = single_service('router', $this->collection, $this->request);

            $router->handle('user-setting/show-list');
            expect($router->controllerName())->toBe('UserSettingController');
            expect($router->methodName())->toBe('show_list');
        });

        it(': Traduire les tirets URI', function (): void {
            $this->collection->setTranslateURIDashes(true);

            $router = single_service('router', $this->collection, $this->request);

            $router->handle('user-setting/show-list');
            expect($router->controllerName())->toBe('User_settingController');
            expect($router->methodName())->toBe('show_list');
        });

        it(': Traduire les tirets URI pour les paramètres', function (): void {
            $this->collection->setTranslateURIDashes(true);
            $router = single_service('router', $this->collection, $this->request);

            $router->handle('user-setting/2018-12-02');
            expect($router->controllerName())->toBe('User_settingController');
            expect($router->methodName())->toBe('detail');
            expect($router->params())->toBe(['2018-12-02']);
        });
    });

	describe('Segments multiple', function (): void {
		it('l\'option de segment multiple est desactivee', function (): void {
			$this->collection->get('product/(:any)', 'Catalog::productLookup/$1');
			$router = single_service('router', $this->collection, $this->request);

        	$router->handle('product/123/456');

			expect($router->controllerName())->toBe('CatalogController');
			expect('productLookup')->toBe($router->methodName());
			expect(['123', '456'])->toBe($router->params());
		});

		xit('l\'option de segment multiple est activee', function (): void {
			$collection = $this->createCollection([
				'multiple_segments_one_param' => true,
			]);

			$collection->get('product/(:any)', 'Catalog::productLookup/$1');
			$router = single_service('router', $this->collection, $this->request);

        	$router->handle('product/123/456');

			expect($router->controllerName())->toBe('CatalogController');
			expect('productLookup')->toBe($router->methodName());
			expect(['123/456'])->toBe($router->params());
		});
	});
});
