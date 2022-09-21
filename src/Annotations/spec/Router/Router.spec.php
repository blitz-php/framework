<?php

use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Loader\Services;
use BlitzPHP\Router\RouteCollection;

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

    });
});
