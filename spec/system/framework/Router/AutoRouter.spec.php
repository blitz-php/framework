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
use BlitzPHP\Router\AutoRouter;
use BlitzPHP\Router\RouteCollection;
use Spec\BlitzPHP\App\Controllers\IndexController;
use Spec\BlitzPHP\App\Controllers\ProductController;

use function Kahlan\expect;

describe('AutoRouter', function (): void {
    beforeAll(function (): void {
        $this->collection      = new RouteCollection(Services::locator(), (object) config('routing'));

		$this->createNewAutoRouter = fn($namespace = 'Spec\BlitzPHP\App\Controllers'): AutoRouter => new AutoRouter(
				[],
				$namespace,
				$this->collection->getDefaultController(),
				$this->collection->getDefaultMethod(),
				true
			);
    });

    it('L\'autoroute trouve le controller et la methode par defaut "get"', function (): void {
        $this->collection->setDefaultController('Index');

        $router = $this->createNewAutoRouter();

        [$directory, $controller, $method, $params] = $router->getRoute('/', Method::GET);

        expect($directory)->toBeNull();
        expect($controller)->toBe(IndexController::class);
        expect($method)->toBe('getIndex',);
        expect($params)->toBe([]);

		expect($router->getPos())->toBe([
            'controller' => null,
            'method'     => null,
            'params'     => null,
        ]);
    });

	it('L\'autoroute trouve le controller et la methode par defaut "get" d\'un module', function (): void {
		config()->set('routing.module_routes', [
            'test' => 'Spec\BlitzPHP\App\Controllers',
        ]);

		$this->collection->setDefaultController('Index');

        $router = $this->createNewAutoRouter('App/Controllers');

        [$directory, $controller, $method, $params] = $router->getRoute('test', Method::GET);

        expect($directory)->toBeNull();
        expect($controller)->toBe(IndexController::class);
        expect($method)->toBe('getIndex',);
        expect($params)->toBe([]);

		expect($router->getPos())->toBe([
            'controller' => null,
            'method'     => null,
            'params'     => null,
        ]);

		config()->reset('routing.module_routes');
	});

	it('L\'autoroute trouve le controller et la methode par defaut "post"', function (): void {
        $this->collection->setDefaultController('Index');

        $router = $this->createNewAutoRouter();

        [$directory, $controller, $method, $params] = $router->getRoute('/', Method::POST);

        expect($directory)->toBeNull();
        expect($controller)->toBe(IndexController::class);
        expect($method)->toBe('postIndex',);
        expect($params)->toBe([]);

		expect($router->getPos())->toBe([
            'controller' => null,
            'method'     => null,
            'params'     => null,
        ]);
    });

	it('Trouve le controller a partir du nom du fichier et la methode', function (): void {
        $router = $this->createNewAutoRouter();

        [$directory, $controller, $method, $params] = $router->getRoute('product/somemethod', Method::GET);

        expect($directory)->toBeNull();
        expect($controller)->toBe(ProductController::class);
        expect($method)->toBe('getSomemethod');
        expect($params)->toBe([]);

		expect($router->getPos())->toBe([
            'controller' => 0,
            'method'     => 1,
            'params'     => null,
        ]);
    });

	it('Trouve le controller, la methode et les parametres', function (): void {
		$router = $this->createNewAutoRouter();

        [$directory, $controller, $method, $params] = $router->getRoute('product/somemethod/a', Method::GET);

		expect($directory)->toBeNull();
        expect($controller)->toBe(ProductController::class);
        expect($method)->toBe('getSomemethod');
        expect($params)->toBe(['a']);

		expect($router->getPos())->toBe([
            'controller' => 0,
            'method'     => 1,
            'params'     => 2,
        ]);
    });

	it('Leve une exception si le nombre de parametres recu est superieurs a celui attendu', function (): void {
		$router = $this->createNewAutoRouter();

        expect(fn() => $router->getRoute('product/somemethod/a/b', Method::GET))
			->toThrow(new PageNotFoundException());
    });

	it('Trouve le controller a partir du nom du fichier', function (): void {
        $router = $this->createNewAutoRouter();

        [$directory, $controller, $method, $params] = $router->getRoute('product', Method::GET);

        expect($directory)->toBeNull();
        expect($controller)->toBe(ProductController::class);
        expect($method)->toBe('getIndex');
        expect($params)->toBe([]);

		expect($router->getPos())->toBe([
            'controller' => 0,
            'method'     => null,
            'params'     => null,
        ]);
    });
});
