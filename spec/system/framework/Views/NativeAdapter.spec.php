<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Spec\ReflectionHelper;
use BlitzPHP\View\Adapters\NativeAdapter;

describe('Views / NativeAdapter', function () {
    beforeAll(function () {
		$this->config = config('view.adapters.native');
    });

    describe('Extends', function () {
        it('Extend fonctionne normalement', function () {
            $view = new NativeAdapter($this->config);

			$view->setVar('testString', 'Hello World');
			$expected = "<p>Open</p>\n<h1>Hello World</h1>";

            expect($view->render('extend'))->toMatch(fn($actual) => str_contains($actual, $expected));
        });

        it("Le layout n'est pas rendu plusieurs fois même si on l'appelle à plusieurs reprise", function () {
            $view = new NativeAdapter($this->config);

			$view->setVar('testString', 'Hello World');
			$expected = "<p>Open</p>\n<h1>Hello World</h1>\n<p>Hello World</p>";

			$view->render('extend');

        	expect($view->render('extend'))->toMatch(fn($actual) => str_contains($actual, $expected));
        });

        it("Les variables sont disponibles partout", function () {
            $view = new NativeAdapter($this->config);

			$view->setVar('testString', 'Hello World');
			$expected = "<p>Open</p>\n<h1>Hello World</h1>\n<p>Hello World</p>";

        	expect($view->render('extend'))->toMatch(fn($actual) => str_contains($actual, $expected));
        });

        it("Deux sections peuvent avoir le meme nom", function () {
            $view = new NativeAdapter($this->config);

			$view->setVar('testString', 'Hello World');
			$expected = "<p>First</p>\n<p>Second</p>";

        	expect($view->render('extend_two'))->toMatch(fn($actual) => str_contains($actual, $expected));
        });

        it("Une erreur syntaxique dans la fermeture d'une section leve une exception", function () {
            $view = new NativeAdapter($this->config);

			$view->setVar('testString', 'Hello World');

			expect(fn() => $view->render('broken'))->toThrow(new RuntimeException());
        });

        it("L'abscence d'un renderSection n'affichera pas le contenu de la vue", function () {
            $view = new NativeAdapter($this->config);

        	$view->setVar('testString', 'Hello World');
        	$expected = '';

            expect($view->render('apples'))->toMatch(fn($actual) => str_contains($actual, $expected));
        });

        it("Le rendu de section conserve les donnees", function () {
            $view = new NativeAdapter($this->config);

        	$view->setVar('pageTitle', 'Bienvenue sur BlitzPHP!');
			$view->setVar('testString', 'Hello World');
			$expected = "<title>Bienvenue sur BlitzPHP!</title>\n<h1>Bienvenue sur BlitzPHP!</h1>\n<p>Hello World</p>";

            expect($view->render('extend_reuse_section'))->toMatch(fn($actual) => str_contains($actual, $expected));
        });
    });

	describe('Donnees', function() {
		it('render ne modifie pas la propriete saveData', function () {
            $view = new NativeAdapter($this->config);

			ReflectionHelper::setPrivateProperty($view, 'saveData', true);
        	$view->setVar('testString', 'test');
     	   	$view->render('simple', null, false);

			expect(ReflectionHelper::getPrivateProperty($view, 'saveData'))->toBeTruthy();
        });

		it("Render ne sauvegarde les donnees que lorsque c'est necessaire", function() {
			$view = new NativeAdapter($this->config);

			$view->setVar('testString', 'test');
			$view->render('simple', null, true);
			$view->render('simple', null, false);

			expect($view->render('simple', null, false))->toMatch(fn($actual) => str_contains($actual, '<h1>test</h1>'));
        });
	});

	describe('Vues imbriquees', function () {
		it('Sections imbriquees', function () {
			$view = new NativeAdapter($this->config);

			$view->setVar('testString', 'Hello World');
        	$content = $view->render('nested_section');

			expect($content)->toMatch(fn($actual) => str_contains($actual, '<p>First</p>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, '<p>Second</p>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, '<p>Third</p>'));
        });

		it('La mise en cache fonctionne', function() {
			$view = new NativeAdapter($this->config);

			$view->setVar('testString', 'Hello World');
        	$expected = '<h1>Hello World</h1>';

			expect($view->render('Nested/simple', ['cache' => 10]))->toMatch(fn($actual) => str_contains($actual, $expected));
        	// ce deuxième rendu doit passer par le cache
			expect($view->render('Nested/simple', ['cache' => 10]))->toMatch(fn($actual) => str_contains($actual, $expected));
		});
	});

	describe('assets bundle', function () {
		it('addCss, addJs', function () {
			$view = new NativeAdapter($this->config);

			$view->addCss('style.css');
			$view->addCss('color', 'content.min');

			$styles     = ReflectionHelper::getPrivateProperty($view, '_styles');
			$lib_styles = ReflectionHelper::getPrivateProperty($view, '_lib_styles');

			expect($styles)->toBe(['style.css', 'color', 'content.min']);
			expect($lib_styles)->toBe([]);

			$view->addJs('app.js')->addJs('color', 'content.min');

			$scripts     = ReflectionHelper::getPrivateProperty($view, '_scripts');
			$lib_scripts = ReflectionHelper::getPrivateProperty($view, '_lib_scripts');

			expect($scripts)->toBe(['app.js', 'color', 'content.min']);
			expect($lib_scripts)->toBe([]);
		});

		it('addLibCss, addLibJs', function () {
			$view = new NativeAdapter($this->config);

			$view->addLibCss('bootstrap.css');
			$view->addLibCss('tailwin', 'select2.min');

			$styles     = ReflectionHelper::getPrivateProperty($view, '_styles');
			$lib_styles = ReflectionHelper::getPrivateProperty($view, '_lib_styles');

			expect($styles)->toBe([]);
			expect($lib_styles)->toBe(['bootstrap.css', 'tailwin', 'select2.min']);

			$view->addLibJs('bootstrap.js')->addLibJs('jquery', 'select2.min');

			$scripts     = ReflectionHelper::getPrivateProperty($view, '_scripts');
			$lib_scripts = ReflectionHelper::getPrivateProperty($view, '_lib_scripts');

			expect($scripts)->toBe([]);
			expect($lib_scripts)->toBe(['bootstrap.js', 'jquery', 'select2.min']);
		});

		it('Style bundle', function () {
			$view = new NativeAdapter($this->config);

			$view->addLibCss('bootstrap.css');
			$view->addLibCss('tailwin', 'select2.min');

			$view->addCss('style.css')->addCss('color', 'content.min');

			$expecteds = [
				'<link rel="stylesheet" type="text/css" href="http://example.com/lib/bootstrap.css" />',
				'<link rel="stylesheet" type="text/css" href="http://example.com/lib/tailwin.css" />',
				'<link rel="stylesheet" type="text/css" href="http://example.com/lib/select2.min.css" />',
				'<link rel="stylesheet" type="text/css" href="http://example.com/css/style.css" />',
				'<link rel="stylesheet" type="text/css" href="http://example.com/css/color.css" />',
				'<link rel="stylesheet" type="text/css" href="http://example.com/css/content.min.css" />'
			];


			foreach ($expecteds as $expected) {
				expect(fn() => $view->stylesBundle())->toMatchEcho(fn($actual) => str_contains($actual, $expected));
			}
		});

		it('script bundle', function () {
			$view = new NativeAdapter($this->config);

			$view->addJs('style.js')->addJs('color', 'content.min');
			$view->addLibJs('bootstrap.js')->addLibJs('jquery', 'select2.min');

			$expecteds = [
				'<script type="text/javascript" src="http://example.com/lib/bootstrap.js"></script>',
				'<script type="text/javascript" src="http://example.com/lib/jquery.js"></script>',
				'<script type="text/javascript" src="http://example.com/lib/select2.min.js"></script>',
				'<script type="text/javascript" src="http://example.com/js/style.js"></script>',
				'<script type="text/javascript" src="http://example.com/js/color.js"></script>',
				'<script type="text/javascript" src="http://example.com/js/content.min.js"></script>',
			];


			expect(fn() => $view->stylesBundle())->toEcho('');

			foreach ($expecteds as $expected) {
				expect(fn() => $view->scriptsBundle())->toMatchEcho(fn($actual) => str_contains($actual, $expected));
			}
		});
	});
});
