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
            $router = Services::router()->init($this->collection, $this->request);
            $router->handle('');

            expect($this->collection->getDefaultController())->toBe($router->controllerName());
            expect($this->collection->getDefaultMethod())->toBe($router->methodName());
        });

        it("Zéro comme chemin URI", function() {
            $router = Services::router()->init($this->collection, $this->request);

            expect(function() use ($router) {
                $router->handle('0');
            })->toThrow(new PageNotFoundException());

        });

        it("Mappages d'URI vers le contrôleur", function() {
            $router = Services::router()->init($this->collection, $this->request);

            $router->handle('users');
            
            expect('UsersController')->toBe($router->controllerName());
            expect('index')->toBe($router->methodName());
        });

        it("Mappages d'URI avec une barre oblique finale vers le contrôleur", function() {
            $router = Services::router()->init($this->collection, $this->request);

            $router->handle('users/');
            
            expect('UsersController')->toBe($router->controllerName());
            expect('index')->toBe($router->methodName());
        });

        it("Mappages d'URI vers une méthode alternative du contrôleur", function() {
            $router = Services::router()->init($this->collection, $this->request);

            $router->handle('posts');
            
            expect('BlogController')->toBe($router->controllerName());
            expect('posts')->toBe($router->methodName());
        });

        it("Mappages d'URI vers le contrôleur d'espace de noms", function() {
            $router = Services::router()->init($this->collection, $this->request);

            $router->handle('pages');
            
            expect('App\PagesController')->toBe($router->controllerName());
            expect('list_all')->toBe($router->methodName());
        });
        
        it("Mappages d'URI vers les paramètres aux références arrière", function() {
            $router = Services::router()->init($this->collection, $this->request);

            $router->handle('posts/123');
            
            expect('show')->toBe($router->methodName());
            expect(['123'])->toBe($router->params());
        });
        
        it("Mappages d'URI vers les paramètres aux références arrière", function() {
            $router = Services::router()->init($this->collection, $this->request);

            $router->handle('posts/123');
            
            expect('show')->toBe($router->methodName());
            expect(['123'])->toBe($router->params());
        });
        
        it("Mappages d'URI vers les paramètres aux références arrière", function() {
            $router = Services::router()->init($this->collection, $this->request);

            $router->handle('posts/123');
            
            expect('show')->toBe($router->methodName());
            expect(['123'])->toBe($router->params());
        });
    });
});
