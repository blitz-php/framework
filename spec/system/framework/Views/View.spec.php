<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Exceptions\ViewException;
use BlitzPHP\Spec\ReflectionHelper;
use BlitzPHP\Validation\ErrorBag;
use BlitzPHP\View\View;

use function Kahlan\expect;

describe('Views / View', function (): void {
    describe('Donnees', function (): void {
        it('Peut-on stocker des variable', function (): void {
            $view = new View();

        	$view->setVar('foo', 'bar');
            expect($view->getData())->toBe(['foo' => 'bar']);
        });

        it("Peut-on ecraser une variable existante", function (): void {
            $view = new View();

        	$view->setVar('foo', 'bar');
        	$view->setVar('foo', 'baz');
            expect($view->getData())->toBe(['foo' => 'baz']);
        });

        it("Peut-on stocker un tableau de donnees", function (): void {
            $view = new View();

        	$expected = [
				'foo' => 'bar',
				'bar' => 'baz',
			];
			$view->setData($expected);

            expect($view->getData())->toBe($expected);
        });

        it("Fusion de donnees", function (): void {
			$view = new View();

        	$expected = [
				'fee' => 'fi',
				'foo' => 'bar',
				'bar' => 'baz',
			];

			$view->with('fee', 'fi');
			$view->addData([
				'foo' => 'bar',
				'bar' => 'baz',
			]);

            expect($view->getData())->toBe($expected);
        });

        it("with", function (): void {
			$view = new View();

        	$expected = [
				'fee' => 'fi',
				'foo' => 'bar',
				'bar' => 'baz',
			];

			$view->with('fee', 'fi')->with([
				'foo' => 'bar',
				'bar' => 'baz',
			]);

            expect($view->getData())->toBe($expected);
        });

        it("setData ecrase les donnees", function (): void {
			$view = new View();

        	$view->setVar('fee', 'fi');
			$view->addData([
				'foo' => 'bar',
				'bar' => 'baz',
			]);
			$view->setData(['bar' => 'foo']);

            expect($view->getData())->toBe(['bar' => 'foo']);
        });

        it("Peut-on ecraser une donnee existante", function (): void {
            $view = new View();

        	$expected = [
				'foo' => 'bar',
				'bar' => 'baz',
			];

			$view->setVar('foo', 'fi');
			$view->setData([
				'foo' => 'bar',
				'bar' => 'baz',
			]);

            expect($view->getData())->toBe($expected);
        });

		it('Peut-on stocker des variable avec un echappement', function (): void {
            $view = new View();

        	$view->setVar('foo', 'bar&', 'html');
            expect($view->getData())->toBe(['foo' => 'bar&amp;']);
        });

        it("Peut-on stocker un tableau de donnees avec un echappement", function (): void {
            $view = new View();

        	$expected = [
				'foo' => 'bar&amp;',
            	'bar' => 'baz&lt;',
			];
			$view->setData([
				'foo' => 'bar&',
				'bar' => 'baz<',
			], 'html');

            expect($view->getData())->toBe($expected);
        });

        it("Reset data", function (): void {
            $view = new View();

        	$view->setData([
				'foo' => 'bar',
				'bar' => 'baz',
			]);
			$view->resetData();

            expect($view->getData())->toBe([]);
        });
    });

	describe('Render', function(): void {
		it('La methode render affiche le bon contenu', function(): void {
			$view = new View();

			$view->setVar('testString', 'Hello World');
			$view->display('simple');

			$expected = '<h1>Hello World</h1>';

			expect(fn() => $view->render())->toEcho($expected);
		});

		it('Leve une exception si la vue n\'existe pas', function(): void {
			$view = new View();

			$view->setVar('testString', 'Hello World')->display('missing');

			expect(fn() => $view->render())->toThrow(new ViewException);
		});

		it('Render peut vider les donnees', function(): void {
			$view = new View();

			$view->make('simple', ['testString' =>'Hello World'])->get(false, false);

			expect($view->getData())->toBe([]);
		});

		it('Render conserve les donnees', function(): void {
			$view = new View();

			$expected = ['testString' => 'Hello World'];
			$view->make('simple', $expected)->get(false, true);

			expect($view->getData())->toBe($expected);
		});

		it('Mise en cache', function(): void {
			$view = new View();

			$view->setVar('testString', 'Hello World');
			$expected = '<h1>Hello World</h1>';

			expect(fn() => $view->display('simple', ['cache' => 10])->render())
				->toEcho($expected);
			// ce deuxième rendu doit passer par le cache
			expect(fn() => $view->display('simple', ['cache' => 10])->render())
				->toEcho($expected);
		});

		it('RenderString sauvegarde les donnees', function(): void {
			$view = new View();
			$expected = '<h1>Hello World</h1>';

			// Je pense que saveData est la sauvegarde des données actuelles, et non le nettoyage des données déjà définies.
			$view->setVar('testString', 'Hello World');
			expect($view->renderString('<h1><?= $testString ?></h1>', [], false))->toBe($expected);
			expect($view->getData())->not->toContainKey('testString');

			$view->setVar('testString', 'Hello World');
			expect($view->renderString('<h1><?= $testString ?></h1>', [], true))->toBe($expected);
			expect($view->getData())->toContainKey('testString');
		});
	});

	describe('Performance logging', function (): void {
		it('Sauvegarde les performances', function (): void {
			config(['view.debug' => true]);

			$view = new View();
			expect($view->getPerformanceData())->toHaveLength(0);

			$view->setVar('testString', 'Hello World');
			$expected = '<h1>Hello World</h1>';

			expect($view->renderString('<h1><?= $testString ?></h1>', [], true))->toBe($expected);
			expect($view->getPerformanceData())->toHaveLength(1);
		});

		it('Ne sauvegarde pas les performances', function (): void {
			config(['view.debug' => false]);

			$view = new View();
			expect($view->getPerformanceData())->toHaveLength(0);

			$view->setVar('testString', 'Hello World');
			$expected = '<h1>Hello World</h1>';

			expect($view->renderString('<h1><?= $testString ?></h1>', [], true))->toBe($expected);
			expect($view->getPerformanceData())->toHaveLength(0);
		});
	});

	describe('Autres', function(): void {
		it('Vue stringable', function(): void {
			$view = new View();

			expect((string) $view->make('simple', ['testString' => 'Hello']))
				->toBe('<h1>Hello</h1>');
		});

		it('Share', function(): void {
			View::share('testString', 'String');
			View::share('testClosure', fn() => 'String');

			$view     = new View();
			$expected = '<h1>String</h1>';

			expect((string) $view->make('simple'))->toBe($expected);
			expect($view->renderString('<h1><?= $testClosure ?></h1>'))->toBe($expected);
		});

		it('WithErrors', function (): void {
			$view     = new View();

			$view->withErrors('invalid error');

			$adapter  = ReflectionHelper::getPrivateProperty($view, 'adapter');
			$tempData = ReflectionHelper::getPrivateProperty($adapter, 'tempData');

			expect($tempData)->toContainKey('errors');
			expect($tempData['errors'])->toBeAnInstanceOf(ErrorBag::class);


			$view     = new View();
			$view->with('errors','invalid error');

			$adapter  = ReflectionHelper::getPrivateProperty($view, 'adapter');
			$tempData = ReflectionHelper::getPrivateProperty($adapter, 'tempData');

			expect($tempData)->toContainKey('errors');
			expect($tempData['errors'])->toBeAnInstanceOf(ErrorBag::class);


			$view     = new View();

			$view->withErrors($errors = [
				'login'    => 'please enter your email address',
				'password' => 'please enter your password'
			]);

			$adapter  = ReflectionHelper::getPrivateProperty($view, 'adapter');
			$tempData = ReflectionHelper::getPrivateProperty($adapter, 'tempData');

			expect($tempData)->toContainKey('errors');
			expect($tempData['errors'])->toBeAnInstanceOf(ErrorBag::class);
			expect($tempData['errors']->toArray())->toBe($errors);
		});

		it('first', function (): void {
			$view     = new View();

			$view->first(['missing', 'simple'], ['testString' => 'String']);
			expect((string) $view)->toBe('<h1>String</h1>');

			expect(fn() => $view->first(['missing', 'mixed']))
				->toThrow(new ViewException());
		});

		it('setAdapter leve une exception', function (): void {
			$config = config()->get('view.active_adapter');
			config()->set('view.active_adapter', 'blade');

			expect(fn() => new View())->toThrow(new ConfigException());

			config()->set('view.active_adapter', $config);
		});
	});

	describe('Options', function (): void {
		it('Save data', function (): void {
			$view = new View();

			$view->setVar('testString', 'Hello World');
			$view->display('simple')
				->setOptions(['save_data' => false]);

			$expected = '<h1>Hello World</h1>';

			expect($view->getData())->toBe(['testString' => 'Hello World']);
			expect(fn() => $view->render())->toEcho($expected);
			expect($view->getData())->toBe([]);
		});

		it('Layout', function (): void {
			$view = new View();

			$view->setVar('testString', 'Hello World');
			$view->display('simple_layout')
				->setLayout('layout');

			expect(fn() => $view->render())->toMatchEcho(fn($actual) => str_contains($actual, '<h1>Hello World</h1>') && str_contains($actual, '<p>Open</p>'));
		});

		it('Layout a travers options', function (): void {
			$view = new View();

			$view->setVar('testString', 'Hello World');
			$view->display('simple_layout')
				->options(['layout' => 'layout']);

			expect(fn() => $view->render())->toMatchEcho(fn($actual) => str_contains($actual, '<h1>Hello World</h1>') && str_contains($actual, '<p>Open</p>'));
		});
	});
});
