<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Exceptions\ViewException;
use BlitzPHP\View\Parser;
use BlitzPHP\View\View;
use Spec\BlitzPHP\App\Views\BadDecorator;
use Spec\BlitzPHP\App\Views\WorldDecorator;

describe('Views / Decorator', function () {
    beforeEach(function () {

    });

	it('L\'abscence d\'un decorateur n\'impacte rien', function () {
		config(['view.decorators' => []]);

		$view = new View();

			$view->setVar('testString', 'Hello World');
			$view->display('simple');

			$expected = '<h1>Hello World</h1>';

			expect(fn() => $view->render())->toEcho($expected);
	});

	it('Un mauvais decorateur leve une exception', function () {
		config()->set('view.decorators', [BadDecorator::class]);

		$view = new View();

		$view->setVar('testString', 'Hello World');
		$view->display('simple');

		expect(fn() => $view->render())
			->toThrow(ViewException::invalidDecorator(BadDecorator::class));
	});

	it('Le decorateur modifie la sortie', function () {
		config()->set('view.decorators', [WorldDecorator::class]);

		$view = new View();

		$view->setVar('testString', 'Hello World');
		$view->display('simple');

		expect(fn() => $view->render())
			->toEcho('<h1>Hello Galaxy</h1>');
	});

	it('L\'abscence d\'un decorateur n\'impacte pas la sortie du parser', function () {
		config(['view.decorators' => []]);

		$view = new Parser(config('view.adapters.native'));

		$view->setVar('teststring', 'Hello World');

		expect(trim($view->render('parser1')))->toBe('<h1>Hello World</h1>');
	});

	it('Le decorateur modifie la sortie du parser', function () {
		config()->set('view.decorators', [WorldDecorator::class]);

		$view = new Parser(config('view.adapters.native'));

		$view->setVar('teststring', 'Hello World');

		expect(trim($view->render('parser1')))->toBe('<h1>Hello Galaxy</h1>');
	});
});
