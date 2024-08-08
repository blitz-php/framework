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
use Spec\BlitzPHP\App\Views\Components\AdditionComponent;
use Spec\BlitzPHP\App\Views\Components\AwesomeComponent;
use Spec\BlitzPHP\App\Views\Components\BadComponent;
use Spec\BlitzPHP\App\Views\Components\ColorsComponent;
use Spec\BlitzPHP\App\Views\Components\GreetingComponent;
use Spec\BlitzPHP\App\Views\Components\ListerComponent;
use Spec\BlitzPHP\App\Views\Components\MultiplierComponent;
use Spec\BlitzPHP\App\Views\Components\RenderedExtraDataNotice;
use Spec\BlitzPHP\App\Views\Components\RenderedNotice;
use Spec\BlitzPHP\App\Views\Components\SimpleNotice;

describe('Views / Component', function (): void {
    describe('Composants simples', function (): void {
		beforeAll(function (): void {
			$this->cache     = new MockCache();
			$this->cache->init();
			$this->component = new ComponentLoader($this->cache);
		});
		afterAll(function (): void {
			$this->cache->clear();
		});

		describe('prepareParams', function (): void {
			it('PrepareParams retourne un tableau vide lorsqu\'on lui passe une chaine vide', function (): void {
				expect($this->component->prepareParams(''))->toBe([]);
			});

			it('PrepareParams retourne un tableau vide lorsqu\'on lui passe un parametre invalide', function (): void {
				expect($this->component->prepareParams(1.023))->toBe([]);
			});

			it('PrepareParams retourne le tableau qu\'on lui passe', function (): void {
				$object = [
					'one'   => 'two',
					'three' => 'four',
				];
				expect($this->component->prepareParams($object))->toBe($object);

				expect($this->component->prepareParams([]))->toBe([]);
			});

			it('PrepareParams parse une chaine bien formatee et retourne le tableau correspondant', function (): void {
				$params   = 'one=two three=four';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
				];
				expect($this->component->prepareParams($params))->toBe($expected);
			});

			it('PrepareParams parse une chaine bien formatee suivant les convension et retourne le tableau correspondant', function (): void {
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

		describe('render', function (): void {
			it('Affichage du rendu avec les classes namespaced', function (): void {
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::hello'))->toBe('Hello');
			});

			it('Affichage du rendu de deux composants avec le meme nom-court', function (): void {
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::hello'))->toBe('Hello');
				expect($this->component->render('\Spec\BlitzPHP\App\Views\OtherComponents\SampleClass::hello'))->toBe('Good-bye!');
			});

			it('Affichage du rendu avec les parametres sous forme de chaine valide', function (): void {
				$params   = 'one=two,three=four';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
				];
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::echobox', $params))->toBe(implode(',', $expected));
			});

			it('Affichage du rendu avec les methodes statiques', function (): void {
				$params   = 'one=two,three=four';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
				];
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::staticEcho', $params))->toBe(implode(',', $expected));
			});

			it('Parametres vide', function (): void {
				$params   = [];
				$expected = [];
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::staticEcho', $params))->toBe(implode(',', $expected));
			});

			it('Pas de parametres', function (): void {
				$expected = [];
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::staticEcho'))->toBe(implode(',', $expected));
			});

			it('Composant sans parametres', function (): void {
				$params   = ',';
				$expected = 'Hello World';
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::index', $params))->toBe($expected);
			});
		});

		describe('Exceptions', function (): void {
			it('Classe de composant manquante', function (): void {
				$params   = 'one=two,three=four';
				expect(fn() => $this->component->render('::echobox', $params))->toThrow(new ViewException());
			});

			it('Methode de composant manquante', function (): void {
				$params   = 'one=two,three=four';
				expect(fn() => $this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::', $params))->toThrow(new ViewException());
			});

			it('Mauvaise classe de composant', function (): void {
				$params   = 'one=two,three=four';
				expect(fn() => $this->component->render('\Spec\BlitzPHP\App\Views\GoodLuck::', $params))->toThrow(new ViewException());
			});

			it('Mauvaise methode de composant', function (): void {
				$params   = 'one=two,three=four';
				expect(fn() => $this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::notThere', $params))->toThrow(new ViewException());
			});
		});

		describe('Mise en cache', function (): void {
			it('Rendu avec cache actif', function (): void {
				$params   = 'one=two,three=four';
				$expected = [
					'one'   => 'two',
					'three' => 'four',
				];

				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::echobox', $params, 60, 'rememberme'))->toBe(implode(',', $expected));
				$params = 'one=six,three=five';
				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::echobox', $params, 1, 'rememberme'))->toBe(implode(',', $expected));
		   });

		   it('Rendu avec cache actif', function (): void {
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

		describe('Parametres', function (): void {
			it('Les parametres correspondent', function (): void {
				$params = [
					'p1' => 'one',
					'p2' => 'two',
					'p4' => 'three',
				];
				$expected = 'Right on';

				expect($this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::work', $params))->toBe($expected);
		   });

		   it('Les parametres ne correspondent pas', function (): void {
				$params   = 'p1=one,p2=two,p3=three';
				expect(fn() => $this->component->render('\Spec\BlitzPHP\App\Views\SampleClass::work', $params))->toThrow(new ViewException());
			   });
		});

		describe('Autres', function (): void {
			it('initialize', function (): void {
				expect(
					$this->component->render('Spec\BlitzPHP\App\Views\SampleClassWithInitialize::index')
				)->toBe(Response::class);
			});

			it('Parvient a trouver le composant', function (): void {
				expect($this->component->render('StarterComponent::hello'))->toBe('Hello World!');
				expect($this->component->render('StarterComponent::hello', ['name' => 'BlitzPHP']))->toBe('Hello BlitzPHP!');
			});
		});
	});

	describe('Composants contrôlés', function (): void {
		it('Rendu du composant avec les valeurs par défaut', function (): void {
			expect(component(GreetingComponent::class))->toBe('Hello World');
		});

		it('Rendu du composant avec la vue ayant le meme nom que la classe', function (): void {
			expect(component(AwesomeComponent::class))->toMatch(fn($actual) => str_contains($actual, 'Found!'));
		});

		it('Rendu du composant avec une vue nommee', function (): void {
			expect(component(SimpleNotice::class))->toMatch(fn($actual) => str_contains($actual, '4, 8, 15, 16, 23, 42'));
		});

		it('Rendu du composant a travers la methode render()', function (): void {
			expect(component(RenderedNotice::class))->toMatch(fn($actual) => str_contains($actual, '4, 8, 15, 16, 23, 42'));
		});

		it('Rendu du composant a travers la methode render() et des donnees supplementaires', function (): void {
			expect(component(RenderedExtraDataNotice::class))->toMatch(fn($actual) => str_contains($actual, '42, 23, 16, 15, 8, 4'));
		});

		it('Leve une exception si on ne trouve aucune vue pour le composant', function (): void {
			expect(fn() => component(BadComponent::class))
				->toThrow(new LogicException('Impossible de localiser le fichier de vue pour le composant "Spec\\BlitzPHP\\App\\Views\\Components\\BadComponent".'));
		});

		it('Rendu du composant avec des parametres', function (): void {
			expect(component(GreetingComponent::class, 'greeting=Hi, name=Blitz PHP'))->toBe('Hi Blitz PHP');

			// Il n'est pas possible de modifier les proprietes de base du composant, comme `view`.
			expect(component(GreetingComponent::class, 'greeting=Hi, name=Blitz PHP, view=foo'))->toBe('Hi Blitz PHP');
		});

		it('Rendu d\'un composant ayant une methode personnalisee', function (): void {
			expect(component('Spec\BlitzPHP\App\Views\Components\GreetingComponent::sayHello', 'greeting=Hi, name=Blitz PHP'))->toBe('Well, Hi Blitz PHP');
		});

		it('Leve une exception si on la methodde personnalisee qu\'on souhaite n\'existe pas dans le composant', function (): void {
			expect(fn() => component('Spec\BlitzPHP\App\Views\Components\GreetingComponent::sayGoodbye'))
				->toThrow(new ViewException(lang('View.invalidComponentMethod', [
					'class'  => GreetingComponent::class,
					'method' => 'sayGoodbye',
				])));
		});

		it('Rendu d\'un composant ayant des proprietes calculees', function (): void {
			expect(component(ListerComponent::class, ['items' => ['one', 'two', 'three']]))
				->toMatch(fn($actual) => str_contains($actual, '-one -two -three'));
		});

		it('Rendu d\'un composant ayant des methodes publiques', function (): void {
			expect(component(ColorsComponent::class, ['color' => 'red']))
				->toMatch(fn($actual) => str_contains($actual, 'warm'));

			expect(component(ColorsComponent::class, ['color' => 'purple']))
				->toMatch(fn($actual) => str_contains($actual, 'cool'));
		});

		it('Montage du composant avec les valeurs par defaut', function (): void {
			expect(component(MultiplierComponent::class))
				->toMatch(fn($actual) => str_contains($actual, '4'));

			expect(component(AdditionComponent::class))
				->toMatch(fn($actual) => str_contains($actual, '2'));
		});

		it('Montage du composant avec d\'autres valeurs', function (): void {
			expect(component(MultiplierComponent::class, ['value' => 3, 'multiplier' => 3]))
				->toMatch(fn($actual) => str_contains($actual, '9'));
		});

		it('Montage du composant avec des parametres', function (): void {
			expect(component(AdditionComponent::class, ['value' => 3]))
				->toMatch(fn($actual) => str_contains($actual, '3'));
		});

		it('Montage du composant avec des valeurs et parametres de montage', function (): void {
			expect(component(AdditionComponent::class, ['value' => 3, 'number' => 4, 'skipAddition' => false]))
				->toMatch(fn($actual) => str_contains($actual, '7'));

			expect(component(AdditionComponent::class, ['value' => 3, 'number' => 4, 'skipAddition' => true]))
				->toMatch(fn($actual) => str_contains($actual, '3'));
		});

		it('Montage du composant avec des parametres manquant', function (): void {
			// Ne fourni aucun parametres
			expect(component(AdditionComponent::class, ['value' => 3]))
				->toMatch(fn($actual) => str_contains($actual, '3'));

			// Saute un parametre dans la liste des parametres
			expect(component(AdditionComponent::class, ['value' => 3, $skipAddition = true]))
				->toMatch(fn($actual) => str_contains($actual, '3'));
		});
	});
});
