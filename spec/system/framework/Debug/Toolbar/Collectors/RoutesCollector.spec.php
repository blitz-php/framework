<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug\Toolbar\Collectors;

use BlitzPHP\Router\DefinedRouteCollector;
use BlitzPHP\Utilities\Reflection\ReflectionClass;
use Mockery;
use Spec\BlitzPHP\App\Controllers\HomeController;
use Spec\BlitzPHP\App\Controllers\RestController;

use function Kahlan\expect;

describe('Debug / Toolbar / Collectors / RoutesCollector', function (): void {
    beforeEach(function (): void {
        // Mock de la collection de routes
        $this->mockRouteCollection = Mockery::mock(service('routes'));
		$this->mockRouteCollection->shouldReceive('shouldAutoRoute')->andReturn(true);

		// Mock du routeur
		$this->mockRouter = Mockery::mock(service('router', $this->mockRouteCollection));
		$this->mockRouter->shouldReceive('controllerName')->andReturn(HomeController::class)->byDefault();;
		$this->mockRouter->shouldReceive('methodName')->andReturn('index')->byDefault();;
		$this->mockRouter->shouldReceive('params')->andReturn(['id' => 123])->byDefault();;
		$this->mockRouter->shouldReceive('directory')->andReturn('App\Controllers')->byDefault();;

        $this->collector = new RoutesCollector($this->mockRouteCollection, $this->mockRouter);
    });

    it('a les bonnes propriétés de base', function (): void {
        expect($this->collector->getTitle())->toBe('Routes');
        expect($this->collector->hasTimelineData())->toBe(false);
        expect($this->collector->hasTabContent())->toBe(true);
        expect($this->collector->hasVarData())->toBe(false);
        expect($this->collector->hasLabel())->toBe(false);
    });

    it('affiche les informations de la route correspondante', function (): void {
		$display = $this->collector->display();

        expect($display)->toBeAn('array');
        expect($display)->toContainKey('matchedRoute');
        expect($display)->toContainKey('routes');
        expect($display)->toContainKey('autoRoute');

        // Vérifier la route correspondante
        expect($display['matchedRoute'])->toHaveLength(1);
		expect($display['matchedRoute'][0]['controller'])->toBe(HomeController::class);
        expect($display['matchedRoute'][0]['method'])->toBe('index');
        expect($display['matchedRoute'][0]['directory'])->toBe('App\Controllers');
        expect($display['matchedRoute'][0]['paramCount'])->toBe(1);
    });

    it('gère les contrôleurs sous forme de callable', function (): void {
		// Simuler un contrôleur callable
		$this->mockRouter->shouldReceive('controllerName')->andReturn(fn (): string => 'callback');
		$this->mockRouter->shouldReceive('methodName')->andReturn('');

		$collector = new RoutesCollector($this->mockRouteCollection, $this->mockRouter);

        $display = $collector->display();

        expect($display['matchedRoute'][0]['controller'])->toBe('Non défini');
        expect($display['matchedRoute'][0]['method'])->toBe('Non définie');
    });

    it('gère les méthodes qui n\'existent pas avec _remap', function (): void {
       	$this->mockRouter->shouldReceive('controllerName')->andReturn(RestController::class);

		$collector = new RoutesCollector($this->mockRouteCollection, $this->mockRouter);

        expect(fn (): array => $collector->display())->not->toThrow();
    });

    it('collecte les routes définies sans les closures', function (): void {
        $mockRoutes = [
            [
                'method' => 'get',
                'route' => '/',
                'name' => 'home',
                'handler' => 'App\Controllers\Home::index',
            ],
            [
                'method' => 'post',
                'route' => '/login',
                'name' => 'login',
                'handler' => '(Closure)',
            ],
            [
                'method' => 'get',
                'route' => '/about',
                'name' => 'about',
                'handler' => 'App\Controllers\About::index',
            ],
        ];

		$definedRouteCollector = new DefinedRouteCollector($this->mockRouteCollection);
		ReflectionClass::make($definedRouteCollector)->setValue('cachedRoutes', $mockRoutes);

		$collector = new RoutesCollector(
			$this->mockRouteCollection,
			$this->mockRouter,
			$definedRouteCollector
		);
        $display = $collector->display();

        expect($display['routes'])->toHaveLength(2); // Les closures sont filtrées
        expect($display['routes'][0]['method'])->toBe('GET');
        expect($display['routes'][0]['route'])->toBe('/');
        expect($display['routes'][0]['handler'])->toBe('App\Controllers\Home::index');
        expect($display['routes'][0]['name'])->toBe('home');
    });

    it('retourne la valeur correcte du badge', function (): void {
        $mockRoutes = [
            ['handler' => 'Controller1'],
            ['handler' => 'Controller2'],
            ['handler' => '(Closure)'], // Ne devrait pas être compté
            ['handler' => 'Controller3'],
        ];

        $definedRouteCollector = new DefinedRouteCollector($this->mockRouteCollection);
		ReflectionClass::make($definedRouteCollector)->setValue('cachedRoutes', $mockRoutes);

		$collector = new RoutesCollector(
			$this->mockRouteCollection,
			$this->mockRouter,
			$definedRouteCollector
		);

        expect($collector->getBadgeValue())->toBe(3);
    });

    it('retourne une icône encodée en base64', function (): void {
        $icon = $this->collector->icon();

        expect($icon)->toBeA('string');
        expect($icon)->toMatch('/^data:image\/png;base64,/');
    });

    it('indique si l\'auto-routage est activé', function (): void {
        // Tester avec auto-routage activé
        $this->mockRouteCollection->shouldReceive('shouldAutoRoute')->andReturn(true);

		$collector = new RoutesCollector($this->mockRouteCollection, $this->mockRouter);
        $display = $collector->display();

        expect($display['autoRoute'])->toBe('Activé');
    });
});
