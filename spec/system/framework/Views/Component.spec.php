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
use BlitzPHP\Http\Response;
use BlitzPHP\Spec\Mock\MockCache;
use BlitzPHP\View\Components\ComponentLoader;

describe('Views / Component', function () {
    describe('Composants simples', function () {
		beforeAll(function () {
			$this->cache     = new MockCache();
			$this->cache->init();
			$this->component = new ComponentLoader($this->cache);
		});
		afterAll(function () {
			$this->cache->clear();
		});

		describe('prepareParams', function () {
			it('PrepareParams retourne un tableau vide lorsqu\'on lui passe une chaine vide', function () {
				expect($this->component->prepareParams(''))->toBe([]);
			});

			it('PrepareParams retourne le tableau qu\'on lui passe', function () {
				$object = [
					'one'   => 'two',
					'three' => 'four',
				];
				expect($this->component->prepareParams($object))->toBe($object);

				expect($this->component->prepareParams([]))->toBe([]);
			});

			it('PrepareParams parse une chaine bien formatee et retourne le tableau correspondant', function () {
				$params   = 'one=two three=four';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
				];
				expect($this->component->prepareParams($params))->toBe($expected);
			});

			it('PrepareParams parse une chaine bien formatee suivant les convension et retourne le tableau correspondant', function () {
				$params   = 'one=2, three=4.15';
				$expected = [
					'one'   => '2',
					'three' => '4.15',
				];
				expect($this->component->prepareParams($params))->toBe($expected);

				$params   = 'one=two,three=four';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
				];
				expect($this->component->prepareParams($params))->toBe($expected);

				$params   = 'one= two,three =four, five = six';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
					'five'  => 'six',
				];
				expect($this->component->prepareParams($params))->toBe($expected);
			});
		});

		describe('render', function () {
			it('Affichage du rendu avec les classes namespaced', function () {
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::hello'))->toBe('Hello');
			});

			it('Affichage du rendu de deux composants avec le meme nom-court', function () {
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::hello'))->toBe('Hello');
				expect($this->component->render('\Spec\BlitzPHP\App\Views\OtherComponents\SampleClass::hello'))->toBe('Good-bye!');
			});

			it('Affichage du rendu avec les parametres sous forme de chaine valide', function () {
				$params   = 'one=two,three=four';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
				];
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::echobox', $params))->toBe(implode(',', $expected));
			});

			it('Affichage du rendu avec les methodes statiques', function () {
				$params   = 'one=two,three=four';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
				];
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::staticEcho', $params))->toBe(implode(',', $expected));
			});

			it('Parametres vide', function () {
				$params   = [];
				$expected = [];
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::staticEcho', $params))->toBe(implode(',', $expected));
			});

			it('Pas de parametres', function () {
				$expected = [];
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::staticEcho'))->toBe(implode(',', $expected));
			});

			it('Composant sans parametres', function () {
				$params   = ',';
				$expected = 'Hello World';
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::index', $params))->toBe($expected);
			});
		});

		describe('Exceptions', function () {
			it('Classe de composant manquante', function () {
				$params   = 'one=two,three=four';
				expect(fn() => $this->component->render('::echobox', $params))->toThrow(new ViewException());
			});

			it('Methode de composant manquante', function () {
				$params   = 'one=two,three=four';
				expect(fn() => $this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::', $params))->toThrow(new ViewException());
			});

			it('Mauvaise classe de composant', function () {
				$params   = 'one=two,three=four';
				expect(fn() => $this->component->render('\Spec\BlitzPHP\App\Views\GoodLuck::', $params))->toThrow(new ViewException());
			});

			it('Mauvaise methode de composant', function () {
				$params   = 'one=two,three=four';
				expect(fn() => $this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::notThere', $params))->toThrow(new ViewException());
			});
		});

		describe('Mise en cache', function () {
			it('Rendu avec cache actif', function () {
				$params   = 'one=two,three=four';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
				];

				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::echobox', $params, 60, 'rememberme'))->toBe(implode(',', $expected));
				$params = 'one=six,three=five';
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::echobox', $params, 1, 'rememberme'))->toBe(implode(',', $expected));
		   });

		   it('Rendu avec cache actif', function () {
				$params   = 'one=two,three=four';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
				];

				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::echobox', $params, 60))->toBe(implode(',', $expected));
				$params = 'one=six,three=five';
				// Lors de la génération automatique, il prend les paramètres en tant que partie de cachename, donc il n'aurait pas réellement mis en cache cela,
				// mais nous voulons nous assurer qu'il ne nous lance pas une balle courbe ici.
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::echobox', $params, 1))->toBe('six,five');
			});
		});

		describe('Parametres', function () {
			it('Les parametres correspondent', function () {
				$params = [
					'p1' => 'one',
					'p2' => 'two',
					'p4' => 'three',
				];
				$expected = 'Right on';

				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::work', $params))->toBe($expected);
		   });

		   it('Les parametres ne correspondent pas', function () {
				$params   = 'p1=one,p2=two,p3=three';
				expect(fn() => $this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::work', $params))->toThrow(new ViewException());
			   });
		});

		describe('Autres', function () {
			it('initialize', function () {
				expect(
					$this->component->render('Spec\BlitzPHP\App\Views\SampleClassWithInitialize::index')
				)->toBe(Response::class);
			});

			it('Parvient a trouver le composant', function () {
				expect($this->component->render('StarterComponent::hello'))->toBe('Hello World!');
				expect($this->component->render('StarterComponent::hello', ['name' => 'BlitzPHP']))->toBe('Hello BlitzPHP!');
			});
		});
	});
});
