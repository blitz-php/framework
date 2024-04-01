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

        it("Une erreur syntaxique dans la closure d'une section leve une exception", function () {
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

    describe('Inclusion', function () {
        it('include fonctionne normalement', function () {
            $view = new NativeAdapter($this->config);

        	$view->setVar('testString', 'Hello World');

 	       	$content = $view->render('extend_include');

			expect($content)->toMatch(fn($actual) => str_contains($actual, '<p>Open</p>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, '<h1>Hello World</h1>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, 'Hello World'));
        });

		it('includeWhen', function () {
            $view = new NativeAdapter($this->config);

        	$view->setVar('testString', 'Hello World');

 	       	$content = $view->render('extend_include_when');

			expect($content)->toMatch(fn($actual) => str_contains($actual, '<p>Open</p>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, 'Hello World'));
			expect($content)->toMatch(fn($actual) => !str_contains($actual, '<h1>Hello World</h1>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, '<h1>{teststring}</h1>'));
        });

		it('includeUnless', function () {
            $view = new NativeAdapter($this->config);

        	$view->setVar('testString', 'Hello World');

 	       	$content = $view->render('extend_include_unless');

			expect($content)->toMatch(fn($actual) => str_contains($actual, '<p>Open</p>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, '<h1>Hello World</h1>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, 'Hello World'));
        });

		it('includeIf', function () {
            $view = new NativeAdapter($this->config);

        	$view->setVar('testString', 'Hello World');

 	       	$content = $view->render('extend_include_if');

			expect($content)->toMatch(fn($actual) => str_contains($actual, '<p>Open</p>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, '<h1>Hello World</h1>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, 'Hello World'));
        });

		it('includeFirst', function () {
            $view = new NativeAdapter($this->config);

        	$view->setVar('testString', 'Hello World');

 	       	$content = $view->render('extend_include_first');

			expect($content)->toMatch(fn($actual) => str_contains($actual, '<p>Open</p>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, '<h1>Hello World</h1>'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, 'Hello World'));
			expect($content)->toMatch(fn($actual) => str_contains($actual, '<h1>{teststring}</h1>'));
        });

		it('includeFirst leve  une exception si on ne trouve aucune vue', function () {
            $view = new NativeAdapter($this->config);

        	$view->setVar('testString', 'Hello World');

 	       	expect(fn() => $view->render('extend_include_first_throw'))->toThrow(new ViewException());
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

	describe('Methodes speciales', function () {
		beforeAll(function () {
			$this->view = new NativeAdapter($this->config);
		});

		it('title', function() {
			expect($this->view->getData())->not->toContainKey('title');
			expect($this->view->title('My Title'))->toBeAnInstanceOf(NativeAdapter::class);
			expect($this->view->getData())->toContainKey('title');
			expect($this->view->getData()['title'])->toBe('My Title');
		});

		it('meta', function() {
			expect($this->view->meta('description'))->toBeEmpty();
			expect($this->view->meta('description', 'BlitzPHP'))->toBeAnInstanceOf(NativeAdapter::class);
			expect($this->view->meta('charset', 'utf-8'))->toBeAnInstanceOf(NativeAdapter::class);
			expect($this->view->meta('description'))->toBe('BlitzPHP');
			expect($this->view->meta('charset'))->toBe('utf-8');
		});

		it('except', function () {
			expect($this->view->excerpt('methodes speciales'))->toBe('methodes speciales');
			expect($this->view->excerpt('methodes speciales', 8))->toBe('metho...');
		});
	});

	describe('Directives', function () {
		beforeAll(function () {
			$this->view = new NativeAdapter($this->config);
		});

		it('class', function () {
			expect($this->view->class([]))->toBe('');

			$isActive = false;
    		$hasError = true;

			expect($this->view->class([
				'p-4',
				'font-bold'     => $isActive,
				'text-gray-500' => ! $isActive,
				'bg-red'        => $hasError,
			]))->toBe('class="p-4 text-gray-500 bg-red"');
		});

		it('style', function () {
			expect($this->view->style([]))->toBe('');

			$isActive = true;

			expect($this->view->style([
				'background-color: red',
				'font-weight: bold' => $isActive,
			]))->toBe('style="background-color: red; font-weight: bold;"');
		});

		it('checked', function () {
			expect($this->view->checked('a'))->toBe('');
			expect($this->view->checked('true'))->toBe('checked="checked"');
			expect($this->view->checked('1'))->toBe('checked="checked"');
			expect($this->view->checked(true))->toBe('checked="checked"');
			expect($this->view->checked(1))->toBe('checked="checked"');
			expect($this->view->checked(0))->toBe('');
			expect($this->view->checked('0'))->toBe('');
			expect($this->view->checked('false'))->toBe('');
			expect($this->view->checked(false))->toBe('');
		});

		it('selected', function () {
			expect($this->view->selected('a'))->toBe('');
			expect($this->view->selected('true'))->toBe('selected="selected"');
			expect($this->view->selected('1'))->toBe('selected="selected"');
			expect($this->view->selected(true))->toBe('selected="selected"');
			expect($this->view->selected(1))->toBe('selected="selected"');
			expect($this->view->selected(0))->toBe('');
			expect($this->view->selected('0'))->toBe('');
			expect($this->view->selected('false'))->toBe('');
			expect($this->view->selected(false))->toBe('');
		});

		it('disabled', function () {
			expect($this->view->disabled('a'))->toBe('');
			expect($this->view->disabled('true'))->toBe('disabled');
			expect($this->view->disabled('1'))->toBe('disabled');
			expect($this->view->disabled(true))->toBe('disabled');
			expect($this->view->disabled(1))->toBe('disabled');
			expect($this->view->disabled(0))->toBe('');
			expect($this->view->disabled('0'))->toBe('');
			expect($this->view->disabled('false'))->toBe('');
			expect($this->view->disabled(false))->toBe('');
		});

		it('required', function () {
			expect($this->view->required('a'))->toBe('');
			expect($this->view->required('true'))->toBe('required');
			expect($this->view->required('1'))->toBe('required');
			expect($this->view->required(true))->toBe('required');
			expect($this->view->required(1))->toBe('required');
			expect($this->view->required(0))->toBe('');
			expect($this->view->required('0'))->toBe('');
			expect($this->view->required('false'))->toBe('');
			expect($this->view->required(false))->toBe('');
		});

		it('readonly', function () {
			expect($this->view->readonly('a'))->toBe('');
			expect($this->view->readonly('true'))->toBe('readonly');
			expect($this->view->readonly('1'))->toBe('readonly');
			expect($this->view->readonly(true))->toBe('readonly');
			expect($this->view->readonly(1))->toBe('readonly');
			expect($this->view->readonly(0))->toBe('');
			expect($this->view->readonly('0'))->toBe('');
			expect($this->view->readonly('false'))->toBe('');
			expect($this->view->readonly(false))->toBe('');
		});

		it('method', function () {
			expect($this->view->method('post'))->toBe('<input type="hidden" name="_method" value="POST">');
			expect(fn() => $this->view->method('test'))->toThrow(new InvalidArgumentException());
		});
	});
});
