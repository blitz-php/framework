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
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Spec\Middlewares\CustomMiddleware;

describe('Router', function () {
    beforeEach(function () {
        $this->collection = Services::routes(false)->setDefaultNamespace('\\');

        $routes = [
            '/'                                               => 'Home::index',
            'users'                                           => 'Users::index',
            'user-setting/show-list'                          => 'User_setting::show_list',
            'user-setting/(:segment)'                         => 'User_setting::detail/$1',
            'posts'                                           => 'Blog::posts',
            'pages'                                           => 'App\Pages::list_all',
            'posts/(:num)'                                    => 'Blog::show/$1',
            'posts/(:num)/edit'                               => 'Blog::edit/$1',
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
        $this->request = Services::request()->withMethod('GET');
    });

    describe('URI', function () {
        it("L'URI vide correspond aux valeurs par défaut", function () {
            $router = Services::router($this->collection, $this->request, false);
            $router->handle('');

            expect('HomeController')->toBe($router->controllerName());
            expect('index')->toBe($router->methodName());
        });

        it('Zéro comme chemin URI', function () {
            $router = Services::router($this->collection, $this->request, false);

            expect(static function () use ($router) {
                $router->handle('0');
            })->toThrow(new PageNotFoundException());
        });

        it("Mappages d'URI vers le contrôleur", function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('users');

            expect('UsersController')->toBe($router->controllerName());
            expect('index')->toBe($router->methodName());
        });

        it("Mappages d'URI avec une barre oblique finale vers le contrôleur", function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('users/');

            expect('UsersController')->toBe($router->controllerName());
            expect('index')->toBe($router->methodName());
        });

        it("Mappages d'URI vers une méthode alternative du contrôleur", function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('posts');

            expect('BlogController')->toBe($router->controllerName());
            expect('posts')->toBe($router->methodName());
        });

        it("Mappage d'URI vers le contrôleur ayant un sous namespace", function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('pages');

            expect('App\PagesController')->toBe($router->controllerName());
            expect('list_all')->toBe($router->methodName());
        });

        it("Mappage d'URI vers les paramètres aux références arrière", function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('posts/123');

            expect('show')->toBe($router->methodName());
            expect(['123'])->toBe($router->params());
        });

        it("Mappage d'URI vers les paramètres aux références arrière réarrangées", function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('posts/123/edit');

            expect('edit')->toBe($router->methodName());
            expect(['123'])->toBe($router->params());
        });

        it("Mappage d'URI vers les paramètres aux références arrière avec les inutilisés", function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('books/123/sometitle/456');

            expect('show')->toBe($router->methodName());
            expect(['456', '123'])->toBe($router->params());
        });

        it("Mappages d'URI avec plusieurs paramètres", function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('objects/123/sort/abc/FOO');

            expect('objectsSortCreate')->toBe($router->methodName());
            expect(['123', 'abc', 'FOO'])->toBe($router->params());
        });

        it("Mappages d'URI avec plusieurs paramètres et une barre oblique de fin", function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('objects/123/sort/abc/FOO/');

            expect('objectsSortCreate')->toBe($router->methodName());
            expect(['123', 'abc', 'FOO'])->toBe($router->params());
        });

        it('Closures', function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('closure/123/alpha');

            $closure = $router->controllerName();

            $expects = $closure(...$router->params());

            expect($closure)->toBeAnInstanceOf(Closure::class);
            expect($expects)->toBe('123-alpha');
        });
    });

    describe('Route', function () {
        it(': Message d\'exception quand la route n\'existe pas', function () {
            $router = Services::router($this->collection, $this->request, false);

            expect(static function () use ($router) {
                $router->handle('url/not-exists');
            })->toThrow(new PageNotFoundException("Impossible de trouver une route pour 'get: url/not-exists'."));
        });

        it(': Détection de la langue', function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('fr/pages');

            expect($router->hasLocale())->toBeTruthy();
            expect($router->getLocale())->toBe('fr');

            $router->handle('test/123/lang/bg');

            expect($router->hasLocale())->toBeTruthy();
            expect($router->getLocale())->toBe('bg');
        });

        it(': Route resource', function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('admin/admins');

            expect($router->controllerName())->toBe('App\Admin\AdminsController');
            expect($router->methodName())->toBe('list_all');
        });

        it(': Route avec barre oblique dans le nom du contrôleur', function () {
            $router = Services::router($this->collection, $this->request, false);

            expect(static function () use ($router) {
                $router->handle('admin/admins/edit/1');
            })->toThrow(new RouterException('The namespace delimiter is a backslash (\), not a slash (/). Route handler: \App/Admin/Admins::edit_show/$1'));
        });

        it(': Route avec barre oblique en tête', function () {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('some/slash');

            expect($router->controllerName())->toBe('App\SlashController');
            expect($router->methodName())->toBe('index');
        });

        it(': Routage avec contrôleur dynamique', function () {
            $router = Services::router($this->collection, $this->request, false);

            expect(static function () use ($router) {
                $router->handle('en/zoo/bar');
            })->toThrow(new RouterException('A dynamic controller is not allowed for security reasons. Route handler: \$2::$3/$1'));
        });

        it(': Options de route', function () {
            $this->collection->add('foo', static function () {}, [
                'as'  => 'login',
                'foo' => 'baz',
            ]);
            $this->collection->add('baz', static function () {}, [
                'as'  => 'admin',
                'foo' => 'bar',
            ]);

            $router = Services::router($this->collection, $this->request, false);

            $router->handle('foo');

            expect($router->getMatchedRouteOptions())->toBe(['as' => 'login', 'foo' => 'baz']);
        });

        it(': Ordre de routage', function () {
            $this->collection->post('auth', 'Main::auth_post');
            $this->collection->add('auth', 'Main::index');

            $router = Services::router($this->collection, $this->request, false);
            $this->collection->setHTTPVerb('post');

            $router->handle('auth');

            expect($router->controllerName())->toBe('MainController');
            expect($router->methodName())->toBe('auth_post');
        });

        it(': Ordre de priorité de routage', function () {
            $this->collection->add('main', 'Main::index');
            $this->collection->add('(.*)', 'Main::wildcard', ['priority' => 1]);
            $this->collection->add('module', 'Module::index');

            $router = Services::router($this->collection, $this->request, false);
            $this->collection->setHTTPVerb('get');

            $router->handle('module');
            expect($router->controllerName())->toBe('MainController');
            expect($router->methodName())->toBe('wildcard');

            $this->collection->setPrioritize();

            $router->handle('module');
            expect($router->controllerName())->toBe('ModuleController');
            expect($router->methodName())->toBe('index');
        });

        it(': Expression régulière avec Unicode', function () {
            $this->collection->get('news/([a-z0-9\x{0980}-\x{09ff}-]+)', 'News::view/$1');
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('news/a0%E0%A6%80%E0%A7%BF-');
            expect($router->controllerName())->toBe('NewsController');
            expect($router->methodName())->toBe('view');
            expect($router->params())->toBe(['a0ঀ৿-']);
        });

        it(': Espace réservé d\'expression régulière avec Unicode', function () {
            $this->collection->addPlaceholder('custom', '[a-z0-9\x{0980}-\x{09ff}-]+');
            $this->collection->get('news/(:custom)', 'News::view/$1');
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('news/a0%E0%A6%80%E0%A7%BF-');
            expect($router->controllerName())->toBe('NewsController');
            expect($router->methodName())->toBe('view');
            expect($router->params())->toBe(['a0ঀ৿-']);
        });
    });

    describe('Groupes et middlewares', function () {
        it(': Le routeur fonctionne avec les middlewares', function () {
            $this->collection->group('foo', ['middleware' => 'test'], static function (RouteCollection $routes) {
                $routes->add('bar', 'TestController::foobar');
            });

            $router = Services::router($this->collection, $this->request, false);

            $router->handle('foo/bar');
            expect($router->controllerName())->toBe('TestController');
            expect($router->methodName())->toBe('foobar');
            expect($router->getMiddlewares())->toBe(['test']);
        });

        it(': Ressources groupées avec des route ayant les middlewares', function () {
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

            $router = Services::router($this->collection, $this->request, false);

            $router->handle('api/posts');

            expect($router->controllerName())->toBe('App\Controllers\Api\PostController');
            expect($router->methodName())->toBe('index');
            expect($router->getMiddlewares())->toBe(['api-auth']);
        });

        it(': Le routeur fonctionne avec un nom de classe comme middleware', function () {
            $this->collection->add('foo', 'TestController::foo', ['middleware' => CustomMiddleware::class]);

            $router = Services::router($this->collection, $this->request, false);

            $router->handle('foo');
            expect($router->controllerName())->toBe('TestController');
            expect($router->methodName())->toBe('foo');
            expect($router->getMiddlewares())->toBe([CustomMiddleware::class]);
        });

        it(': Le routeur fonctionne avec plusieurs middlewares', function () {
            $this->collection->add('foo', 'TestController::foo', ['middleware' => ['filter1', 'filter2:param']]);

            $router = Services::router($this->collection, $this->request, false);

            $router->handle('foo');
            expect($router->controllerName())->toBe('TestController');
            expect($router->methodName())->toBe('foo');
            expect($router->getMiddlewares())->toBe(['filter1', 'filter2:param']);
        });

        it(': Correspond correctement aux verbes mixtes', function () {
            $this->collection->setHTTPVerb('get');

            $this->collection->add('/', 'Home::index');
            $this->collection->get('news', 'News::index');
            $this->collection->get('news/(:segment)', 'News::view/$1');
            $this->collection->add('(:any)', 'Pages::view/$1');

            $router = Services::router($this->collection, $this->request, false);

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

    describe('Traduction des tirets d\'URI', function () {
        it(': Traduire les tirets URI', function () {
            $this->collection->setTranslateURIDashes(true);

            $router = Services::router($this->collection, $this->request, false);

            $router->handle('user-setting/show-list');
            expect($router->controllerName())->toBe('User_settingController');
            expect($router->methodName())->toBe('show_list');
        });

        it(': Traduire les tirets URI pour les paramètres', function () {
            $this->collection->setTranslateURIDashes(true);
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('user-setting/2018-12-02');
            expect($router->controllerName())->toBe('User_settingController');
            expect($router->methodName())->toBe('detail');
            expect($router->params())->toBe(['2018-12-02']);
        });
    });
});
