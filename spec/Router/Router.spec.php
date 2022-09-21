<?php

use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Loader\Services;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Spec\App\Middlewares\CustomMiddleware;

describe("Router", function() {
    beforeEach(function() {
        $this->collection      = new RouteCollection();

        $routes = [
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

    describe("URI", function() {
       
        it("L'URI vide correspond aux valeurs par défaut", function() {
            $router = Services::router($this->collection, $this->request, false);
            $router->handle('');

            expect($this->collection->getDefaultController())->toBe($router->controllerName());
            expect($this->collection->getDefaultMethod())->toBe($router->methodName());
        });

        it("Zéro comme chemin URI", function() {
            $router = Services::router($this->collection, $this->request, false);

            expect(function() use ($router) {
                $router->handle('0');
            })->toThrow(new PageNotFoundException());

        });

        it("Mappages d'URI vers le contrôleur", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('users');
            
            expect('UsersController')->toBe($router->controllerName());
            expect('index')->toBe($router->methodName());
        });

        it("Mappages d'URI avec une barre oblique finale vers le contrôleur", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('users/');
            
            expect('UsersController')->toBe($router->controllerName());
            expect('index')->toBe($router->methodName());
        });

        it("Mappages d'URI vers une méthode alternative du contrôleur", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('posts');
            
            expect('BlogController')->toBe($router->controllerName());
            expect('posts')->toBe($router->methodName());
        });

        it("Mappage d'URI vers le contrôleur d'espace de noms", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('pages');
            
            expect('App\PagesController')->toBe($router->controllerName());
            expect('list_all')->toBe($router->methodName());
        });
        
        it("Mappage d'URI vers les paramètres aux références arrière", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('posts/123');
            
            expect('show')->toBe($router->methodName());
            expect(['123'])->toBe($router->params());
        });
        
        it("Mappage d'URI vers les paramètres aux références arrière réarrangées", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('posts/123/edit');
            
            expect('edit')->toBe($router->methodName());
            expect(['123'])->toBe($router->params());
        });
        
        it("Mappage d'URI vers les paramètres aux références arrière avec les inutilisés", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('books/123/sometitle/456');
            
            expect('show')->toBe($router->methodName());
            expect(['456', '123'])->toBe($router->params());
        });
        
        it("Mappages d'URI avec plusieurs paramètres", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('objects/123/sort/abc/FOO');
            
            expect('objectsSortCreate')->toBe($router->methodName());
            expect(['123', 'abc', 'FOO'])->toBe($router->params());
        });
        
        it("Mappages d'URI avec plusieurs paramètres et une barre oblique de fin", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('objects/123/sort/abc/FOO/');
            
            expect('objectsSortCreate')->toBe($router->methodName());
            expect(['123', 'abc', 'FOO'])->toBe($router->params());
        });
        
        it("Closures", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('closure/123/alpha');

            $closure = $router->controllerName();
        
            $expects = $closure(...$router->params());

            expect($closure)->toBeAnInstanceOf(Closure::class);
            expect($expects)->toBe('123-alpha');
        });

    });

    describe("Auto routing", function() {
        beforeEach(function() {
            $this->collection->setAutoRoute(true);
        });

        it("L'autorouter trouve le contrôleur et la méthode par défaut", function() {
            $this->collection->setDefaultController('Test');
            $this->collection->setDefaultMethod('test');
            $router = Services::router($this->collection, $this->request, false);

            $router->autoRoute('/');

            expect($router->controllerName())->toBe('TestController');
            expect($router->methodName())->toBe('test');
        });

        it("L'autorouter trouve le contrôleur et la méthode définis", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->autoRoute('my/someMethod');

            expect($router->controllerName())->toBe('MyController');
            expect($router->methodName())->toBe('someMethod');
        });

        it("L'autorouter trouve le contrôleur défini et la méthode par défaut", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->autoRoute('my');

            expect($router->controllerName())->toBe('MyController');
            expect($router->methodName())->toBe('index');
        });
        
        it("L'autorouter trouve le contrôleur dans un sous dossier", function() {
            $router = Services::router($this->collection, $this->request, false);

            mkdir(CONTROLLER_PATH . 'Subfolder', 0777, true);

            $router->autoRoute('subfolder/my/someMethod');

            rmdir(CONTROLLER_PATH . 'Subfolder');

            expect($router->controllerName())->toBe('MyController');
            expect($router->methodName())->toBe('someMethod');
        });
        
        it("L'autorouter trouve le contrôleur dans un sous dossier dont le nom a un undescore", function() {
            $router = Services::router($this->collection, $this->request, false);

            mkdir(CONTROLLER_PATH . 'Dash_folder', 0777, true);

            $router->autoRoute('dash-folder/my/somemethod');

            rmdir(CONTROLLER_PATH . 'Dash_folder');

            expect($router->directory())->toBe('Dash_folder/');
            expect($router->controllerName())->toBe('MyController');
            expect($router->methodName())->toBe('somemethod');
        });
        
        it("L'autorouter trouve le contrôleur dont le nom a un undescore et est dans un sous dossier dont le nom a un undescore", function() {
            $router = Services::router($this->collection, $this->request, false);

            mkdir(CONTROLLER_PATH . 'Dash_folder', 0777, true);
            file_put_contents(CONTROLLER_PATH . 'Dash_folder/Dash_moduleController.php', '');

            $router->autoRoute('dash-folder/dash-module/somemethod');

            unlink(CONTROLLER_PATH . 'Dash_folder/Dash_moduleController.php');
            rmdir(CONTROLLER_PATH . 'Dash_folder');

            expect($router->directory())->toBe('Dash_folder/');
            expect($router->controllerName())->toBe('Dash_moduleController');
            expect($router->methodName())->toBe('somemethod');
        });

        it("L'autorouter trouve le contrôleur et la méthode dont les noms ont un undescore et dont le sous dossier a un undescore", function() {
            $router = Services::router($this->collection, $this->request, false);

            mkdir(CONTROLLER_PATH . 'Dash_folder', 0777, true);
            file_put_contents(CONTROLLER_PATH . 'Dash_folder/Dash_moduleController.php', '');

            $router->autoRoute('dash-folder/dash-module/dash-method');

            unlink(CONTROLLER_PATH . 'Dash_folder/Dash_moduleController.php');
            rmdir(CONTROLLER_PATH . 'Dash_folder');

            expect($router->directory())->toBe('Dash_folder/');
            expect($router->controllerName())->toBe('Dash_moduleController');
            expect($router->methodName())->toBe('dash_method');
        });

        it("L'autorouter trouve le contrôleur par défaut dans un sous dossier dont le nom a un undescore", function() {
            $router = Services::router($this->collection, $this->request, false);

            mkdir(CONTROLLER_PATH . 'Dash_folder', 0777, true);

            $router->autoRoute('dash-folder');

            rmdir(CONTROLLER_PATH . 'Dash_folder');

            expect($router->directory())->toBe('Dash_folder/');
            expect($router->controllerName())->toBe('HomeController');
            expect($router->methodName())->toBe('index');
        });

        it("L'autorouter trouve le répertoire MByte", function() {
            $router = Services::router($this->collection, $this->request, false);

            mkdir(CONTROLLER_PATH . 'Φ', 0777, true);

            $router->autoRoute('Φ');

            rmdir(CONTROLLER_PATH . 'Φ');

            expect($router->directory())->toBe('Φ/');
            expect($router->controllerName())->toBe('HomeController');
            expect($router->methodName())->toBe('index');
        });

        it("L'autorouter trouve le contrôleur MByte", function() {
            $router = Services::router($this->collection, $this->request, false);

            file_put_contents(CONTROLLER_PATH . 'Φ', '');

            $router->autoRoute('Φ');

            unlink(CONTROLLER_PATH . 'Φ');

            expect($router->controllerName())->toBe('ΦController');
            expect($router->methodName())->toBe('index');
        });

        it("L'autorouter rejette un seul point comme URL", function() {
            $router = Services::router($this->collection, $this->request, false);

            expect(function() use ($router) {
                $router->autoRoute('.');
            })->toThrow(new PageNotFoundException());
        });

        it("L'autorouter rejette deux points comme URL", function() {
            $router = Services::router($this->collection, $this->request, false);

            expect(function() use ($router) {
                $router->autoRoute('..');
            })->toThrow(new PageNotFoundException());
        });

        it("L'autorouter rejette le point médian comme URL", function() {
            $router = Services::router($this->collection, $this->request, false);

            expect(function() use ($router) {
                $router->autoRoute('Foo.bar');
            })->toThrow(new PageNotFoundException());
        });

        it(": Méthode de routage automatique Vide", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('Home/');
            expect($router->controllerName())->toBe('HomeController');
            expect($router->methodName())->toBe('index');
            
            $router->handle('Home');
            expect($router->controllerName())->toBe('HomeController');
            expect($router->methodName())->toBe('index');
        });
        
        it(": Répertoire prioritaire du routeur", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->setDirectory('foo/bar/baz', false, true);
            $router->handle('Some_controller/some_method/param1/param2/param3');

            expect($router->directory())->toBe('foo/bar/baz/');
            expect($router->controllerName())->toBe('SomeController');
            expect($router->methodName())->toBe('some_method');
        });
        
        it(": Définir le répertoire valide", function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->setDirectory('foo/bar/baz', false, true);

            expect($router->directory())->toBe('foo/bar/baz/');
        });
    });

    describe('Route', function() {
        it(': Message d\'exception quand la route n\'existe pas', function() {
            $this->collection->setAutoRoute(false);
            $router = Services::router($this->collection, $this->request, false);

            expect(function() use ($router) {
                $router->handle('url/not-exists');
            })->toThrow(new PageNotFoundException("Can't find a route for 'get: url/not-exists'."));
        });
        
        it(': Détection de la langue', function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('fr/pages');
    
            expect($router->hasLocale())->toBeTruthy();
            expect($router->getLocale())->toBe('fr');
            
            $router->handle('test/123/lang/bg');

            expect($router->hasLocale())->toBeTruthy();
            expect($router->getLocale())->toBe('bg');
        });

        it(': Route resource', function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('admin/admins');

            expect($router->controllerName())->toBe('App\Admin\AdminsController');
            expect($router->methodName())->toBe('list_all');
        });

        it(': Route avec barre oblique dans le nom du contrôleur', function() {
            $router = Services::router($this->collection, $this->request, false);

            expect(function() use ($router) {
                $router->handle('admin/admins/edit/1');
            })->toThrow(new RouterException( 'The namespace delimiter is a backslash (\), not a slash (/). Route handler: \App/Admin/Admins::edit_show/$1'));
        });

        it(': Route avec barre oblique en tête', function() {
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('some/slash');

            expect($router->controllerName())->toBe('App\SlashController');
            expect($router->methodName())->toBe('index');
        });

        it(': Routage avec contrôleur dynamique', function() {
            $router = Services::router($this->collection, $this->request, false);

            expect(function() use ($router) {
                $router->handle('en/zoo/bar');
            })->toThrow(new RouterException('A dynamic controller is not allowed for security reasons. Route handler: \$2::$3/$1'));
        
        });

        it(': Options de route', function() {
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

        it(': Ordre de routage', function() {
            $this->collection->post('auth', 'Main::auth_post');
            $this->collection->add('auth', 'Main::index');
            
            $router = Services::router($this->collection, $this->request, false);
            $this->collection->setHTTPVerb('post');

            $router->handle('auth');

            expect($router->controllerName())->toBe('MainController');
            expect($router->methodName())->toBe('auth_post');
        });

        it(': Ordre de priorité de routage', function() {
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

        it(': Expression régulière avec Unicode', function() {
            $this->collection->get('news/([a-z0-9\x{0980}-\x{09ff}-]+)', 'News::view/$1');
            $router = Services::router($this->collection, $this->request, false);
          
            $router->handle('news/a0%E0%A6%80%E0%A7%BF-');
            expect($router->controllerName())->toBe('NewsController');
            expect($router->methodName())->toBe('view');
            expect($router->params())->toBe(['a0ঀ৿-']);
        });
        
        it(': Espace réservé d\'expression régulière avec Unicode', function() {
            $this->collection->addPlaceholder('custom', '[a-z0-9\x{0980}-\x{09ff}-]+');
            $this->collection->get('news/(:custom)', 'News::view/$1');
            $router = Services::router($this->collection, $this->request, false);
          
            $router->handle('news/a0%E0%A6%80%E0%A7%BF-');
            expect($router->controllerName())->toBe('NewsController');
            expect($router->methodName())->toBe('view');
            expect($router->params())->toBe(['a0ঀ৿-']);
        });
    });

    describe('Groupes et middlewares', function() {
        it(': Le routeur fonctionne avec les middlewares', function() {
            $this->collection->group('foo', ['filter' => 'test'], static function (RouteCollection $routes) {
                $routes->add('bar', 'TestController::foobar');
            });
            
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('foo/bar');
            expect($router->controllerName())->toBe('TestController');
            expect($router->methodName())->toBe('foobar');
            expect($router->getMiddlewares())->toBe(['test']);
        });

        it(': Le routeur fonctionne avec un nom de classe comme filtre', function() {
            $this->collection->add('foo', 'TestController::foo', ['filter' => CustomMiddleware::class]);
            
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('foo');
            expect($router->controllerName())->toBe('TestController');
            expect($router->methodName())->toBe('foo');
            expect($router->getMiddlewares())->toBe([CustomMiddleware::class]);
        });

        it(': Le routeur fonctionne avec plusieurs middlewares', function() {
            $this->collection->add('foo', 'TestController::foo', ['filter' => ['filter1', 'filter2:param']]);
            
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('foo');
            expect($router->controllerName())->toBe('TestController');
            expect($router->methodName())->toBe('foo');
            expect($router->getMiddlewares())->toBe(['filter1', 'filter2:param']);
        });
        
        it(': Correspond correctement aux verbes mixtes', function() {
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

    describe('Traduction des tirets d\'URI', function() {
        it(': Traduire les tirets URI', function() {
            $this->collection->setTranslateURIDashes(true);
            
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('user-setting/show-list');
            expect($router->controllerName())->toBe('User_settingController');
            expect($router->methodName())->toBe('show_list');
        });

        it(': Traduire les tirets URI pour les paramètres', function() {
            $this->collection->setTranslateURIDashes(true);
            $router = Services::router($this->collection, $this->request, false);

            $router->handle('user-setting/2018-12-02');
            expect($router->controllerName())->toBe('User_settingController');
            expect($router->methodName())->toBe('detail');
            expect($router->params())->toBe(['2018-12-02']);
        });

        it(': Traduire les tirets URI pour l\'autorouter', function() {
            $this->collection->setAutoRoute(true);
            $router = Services::router($this->collection, $this->request, false);

            $router->autoRoute('admin-user/show-list');
            expect($router->controllerName())->toBe('Admin_userController');
            expect($router->methodName())->toBe('show_list');
        });
        
        it(': La route automatique correspond à zéro paramètre', function() {
            $this->collection->setAutoRoute(true);
            $router = Services::router($this->collection, $this->request, false);
    
            $router->autoRoute('myController/someMethod/0/abc');
            expect($router->controllerName())->toBe('MyController');
            expect($router->methodName())->toBe('someMethod');
            expect($router->params())->toBe(['0', 'abc']);
        });
    });
});
