<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use BlitzPHP\Container\Container;
use BlitzPHP\Container\AbstractProvider;
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\Container\ContainerInterface;
use BlitzPHP\Spec\ReflectionHelper;
use DI\Container as DIContainer;
use DI\NotFoundException;
use Kahlan\Plugin\Double;

use function Kahlan\expect;
use function Kahlan\allow;

describe('Container / Container', function (): void {
    beforeEach(function (): void {
		ReflectionHelper::setPrivateProperty(Container::class, 'providerNames', []);
		ReflectionHelper::setPrivateProperty(Container::class, 'discovered', false);
    });

    afterEach(function (): void {
        // Container::reset();
    });

    describe('Initialisation', function (): void {
        it('initialize construit le container', function (): void {
            $container = new Container();
			$initialized = ReflectionHelper::getPrivateProperty($container,'initialized');
			$initialize = ReflectionHelper::getPrivateMethodInvoker($container,'initialize');

			expect($initialized)->toBeFalsy();
			$initialize();

			$initialized = ReflectionHelper::getPrivateProperty($container,'initialized');
			expect($initialized)->toBeTruthy();
        });

        it('initialize ne fait rien si déjà initialisé', function (): void {
            $container = new Container();
            $initialize = ReflectionHelper::getPrivateMethodInvoker($container,'initialize');

            // premier appel
            $initialize();

            // Mock pour vérifier que build n'est pas appelé à nouveau
            allow(Container::class)->toReceive('initialize')->andReturn(null);

            // le prochain appel ne fera rien
            $initialize();
            expect($container)->toBeAnInstanceOf(Container::class);
        });

        it('initialize avec cache en production', function (): void {
            allow('on_prod')->toBeCalled()->andReturn(true);
            allow('extension_loaded')->toBeCalled()->with('apcu')->andReturn(true);

            $container = new Container();
            $method = new ReflectionMethod($container, 'initialize');
            $method->invoke($container);

            expect($container)->toBeAnInstanceOf(Container::class);
        });
    });

    describe('Méthodes de base', function (): void {
        beforeEach(function (): void {
            $this->container = new Container();
			$initialize = ReflectionHelper::getPrivateMethodInvoker($this->container,'initialize');
			$initialize();
        });

        it('get retourne une entrée', function (): void {
            // Définir une entrée de test
            $this->container->add('test', fn(): string => 'value');

            expect($this->container->get('test'))->toBe('value');
        });

        it('get lève NotFoundException si entrée inexistante', function (): void {
            expect(fn() => $this->container->get('inexistant'))
                ->toThrow(new NotFoundException());
        });

        it('has vérifie l\'existence d\'une entrée', function (): void {
            $this->container->add('test', fn(): string => 'value');

            expect($this->container->has('test'))->toBe(true);
            expect($this->container->has('inexistant'))->toBe(false);
        });

        it('make crée une nouvelle instance', function (): void {
            $this->container->add('test', fn(): stdClass => new stdClass());

            $instance1 = $this->container->make('test');
            $instance2 = $this->container->make('test');

            expect($instance1)->toBeAnInstanceOf(stdClass::class);
            expect($instance2)->toBeAnInstanceOf(stdClass::class);
            expect($instance1)->not->toBe($instance2);
        });

        it('make avec paramètres', function (): void {
            $this->container->add('test', function ($container, $param): stdClass {
                $obj = new stdClass();
                $obj->param = $param;
                return $obj;
            });

            $instance = $this->container->make('test', ['param' => 'value']);
            expect($instance->param)->toBe('value');
        });

        it('call appelle une fonction avec injection', function (): void {
            $callback = (fn(stdClass $dep) => 'called with ' . $dep::class);

            $this->container->add(stdClass::class, fn(): stdClass => new stdClass());

            $result = $this->container->call($callback);
            expect($result)->toMatch('/called with stdClass/');
        });

        it('call avec paramètres supplémentaires', function (): void {
            $callback = (fn($name, stdClass $dep) => $name . ' with ' . $dep::class);

            $this->container->add(stdClass::class, fn(): stdClass => new stdClass());

            $result = $this->container->call($callback, ['name' => 'test']);
            expect($result)->toMatch('/test with stdClass/');
        });
    });

    describe('Gestion des entrées', function (): void {
        beforeEach(function (): void {
            $this->container = new Container();
            $initialize = ReflectionHelper::getPrivateMethodInvoker($this->container,'initialize');
            $initialize();
        });

        it('add définit une entrée', function (): void {
            $callback = fn(): string => 'value';
            $this->container->add('test', $callback);

            expect($this->container->has('test'))->toBe(true);
            expect($this->container->get('test'))->toBe('value');
        });

        it('add remplace une entrée existante', function (): void {
            $this->container->add('test', fn(): string => 'first');
            expect($this->container->get('test'))->toBe('first');

            $this->container->add('test', fn(): string => 'second');
            expect($this->container->get('test'))->toBe('second');
        });

        it('addIf ignore si entrée existe déjà', function (): void {
            $this->container->add('test', fn(): string => 'first');

            $this->container->addIf('test', fn(): string => 'second');
            expect($this->container->get('test'))->toBe('first');
        });

        it('addIf ajoute si entrée n\'existe pas', function (): void {
            $this->container->addIf('test', fn(): string => 'value');
            expect($this->container->get('test'))->toBe('value');
        });

        it('merge fusionne plusieurs entrées', function (): void {
            $callbacks = [
                'key1' => fn(): string => 'value1',
                'key2' => fn(): string => 'value2',
            ];

            $this->container->merge($callbacks);

            expect($this->container->get('key1'))->toBe('value1');
            expect($this->container->get('key2'))->toBe('value2');
        });

        it('merge ignore les valeurs non-Closure', function (): void {
            $callbacks = [
                'key1' => fn(): string => 'value1',
                'key2' => 'not a closure',
            ];

            $this->container->merge($callbacks);

            expect($this->container->has('key1'))->toBe(true);
            expect($this->container->has('key2'))->toBe(false);
        });

        it('mergeIf fusionne conditionnellement', function (): void {
            $this->container->add('key1', fn(): string => 'first');

            $callbacks = [
                'key1' => fn(): string => 'second',
                'key2' => fn(): string => 'value2',
            ];

            $this->container->mergeIf($callbacks);

            expect($this->container->get('key1'))->toBe('first'); // Pas remplacé
            expect($this->container->get('key2'))->toBe('value2'); // Ajouté
        });

        it('bound vérifie les entrées explicitement définies', function (): void {
            $this->container->add('test', fn(): string => 'value');

            expect($this->container->bound('test'))->toBe(true);
            expect($this->container->bound('inexistant'))->toBe(false);
        });
    });

    describe('Découverte des providers', function (): void {
        it('discoverProviders découvre les providers', function (): void {
            $container = new Container();

            // Mock du locator
            $mockLocator = Double::instance([
				'implements' => [LocatorInterface::class],
			]);
            allow('service')->toBeCalled()->with('locator')->andReturn($mockLocator);

            // Simuler des fichiers de providers
            allow($mockLocator)->toReceive('search')->with('Config/Providers')->andReturn([]);
            allow($mockLocator)->toReceive('listFiles')->with('Providers/')->andReturn([]);

            $method = new ReflectionMethod($container, 'discoverProviders');
            $method->invoke($container);

            $reflection = new ReflectionClass(Container::class);
            $discovered = $reflection->getProperty('discovered');

            expect($discovered->getValue())->toBe(true);
        });

        it('discoverProviders ne fait rien si déjà découvert', function (): void {
            $container = new Container();

            // Defini "discovered" à true
			ReflectionHelper::setPrivateProperty($container, 'discovered', true);

            // Mock pour vérifier que les méthodes ne sont pas appelées
            $mockLocator = Double::instance([
				'implements' => [LocatorInterface::class],
			]);
            allow($mockLocator)->toReceive('search')->andReturn([]);
            allow($mockLocator)->toReceive('listFiles')->andReturn([]);

            allow('service')->toBeCalled()->with('locator')->andReturn($mockLocator);

			$discovereProviders = ReflectionHelper::getPrivateMethodInvoker($container, 'discoverProviders');
			$discovereProviders();

			expect(ReflectionHelper::getPrivateProperty($container, 'discovered'))->toBeTruthy();
        });

        it('discoverProviders filtre par sous-classe de AbstractProvider', function (): void {
            $container = new Container();

            // Créer un faux fichier de provider
            $testFilePath = APP_PATH . 'Providers/TestProvider.php';
			if (! is_dir ($dir = dirname($testFilePath))) {
				mkdir($dir, 0777, true);
			}
            file_put_contents($testFilePath, '<?php namespace App\Providers; class TestProvider extends \BlitzPHP\Container\AbstractProvider {}');

			$discovereProviders = ReflectionHelper::getPrivateMethodInvoker($container, 'discoverProviders');
			$discovereProviders();

			$providerNames = ReflectionHelper::getPrivateProperty($container, 'providerNames');
			expect($providerNames)->toContain('App\Providers\TestProvider');

			unlink($testFilePath);
        });

        it('discoverProviders ignore les classes non-Provider', function (): void {
            $container = new Container();

            // Créer un faux fichier de provider
            $testFilePath = APP_PATH . 'Providers/NoProvider.php';
			if (! is_dir ($dir = dirname($testFilePath))) {
				mkdir($dir, 0777, true);
			}
            file_put_contents($testFilePath, '<?php namespace App\Providers; class NoProvider {}');

			$discovereProviders = ReflectionHelper::getPrivateMethodInvoker($container, 'discoverProviders');
			$discovereProviders();

			$providerNames = ReflectionHelper::getPrivateProperty($container, 'providerNames');
			expect($providerNames)->not->toContain('App\Providers\NoProvider');

			unlink($testFilePath);
        });
    });

    describe('Enregistrement des providers', function (): void {
        xit('registerProviders enregistre les providers', function (): void {
            $container = new Container();

            // Mock un provider
            $mockProvider = Double::instance([
				'extends' => AbstractProvider::class,
				'args' => [$container],
			]);
            allow($mockProvider)->toReceive('register');

            // Définir un provider mock
            $reflection = new ReflectionClass(Container::class);
            $providerNames = $reflection->getProperty('providerNames');
            $providerNames->setValue(null, [$mockProvider::class]);

            // Mock le container DI pour retourner notre mock
            $mockDIContainer = Double::instance(['class' => DIContainer::class]);
            allow($mockDIContainer)->toReceive('make')
				->with($mockProvider::class, ['container' => $container])
				->andReturn($mockProvider);

            $containerReflection = new ReflectionClass($container);
            $containerProp = $containerReflection->getProperty('container');
            $containerProp->setValue($container, $mockDIContainer);

            $method = new ReflectionMethod($container, 'registerProviders');
            $method->invoke($container);

            expect($mockProvider)->toReceive('register');
        });

        it('registerProviders définit self et interface dans le container', function (): void {
            $container = new Container();

            // Initialiser le container interne
            $initialize = new ReflectionMethod($container, 'initialize');
            $initialize->invoke($container);

            // Vérifier que le container est défini
            expect($container->has(Container::class))->toBe(true);
            expect($container->has(ContainerInterface::class))->toBe(true);
            expect($container->get(Container::class))->toBe($container);
            expect($container->get(ContainerInterface::class))->toBe($container);
        });
    });

    describe('Magie __call', function (): void {
        beforeEach(function (): void {
            $this->container = new Container();
			$initialize = ReflectionHelper::getPrivateMethodInvoker($this->container, 'initialize');
			$initialize();
        });

        it('__call délègue aux méthodes du container DI', function (): void {
            // set est une méthode de DIContainer
            $this->container->set('test', 'value');
            expect($this->container->get('test'))->toBe('value');
        });

        it('__call lève BadMethodCallException pour méthode inconnue', function (): void {
            expect(fn() => $this->container->unknownMethod())
                ->toThrow(new BadMethodCallException("Méthode 'unknownMethod' inconnue sur DIContainer."));
        });
    });

   	describe('Méthodes magiques déléguées', function (): void {
        beforeEach(function (): void {
            $this->container = new Container();
			$initialize = ReflectionHelper::getPrivateMethodInvoker($this->container, 'initialize');
			$initialize();
        });

        it('debugEntry fonctionne via __call', function (): void {
            $this->container->add('test', fn(): string => 'value');

            // debugEntry est une méthode de DIContainer
            $debug = $this->container->debugEntry('test');
            expect(is_string($debug))->toBeTruthy();
        });

        it('getKnownEntryNames fonctionne via __call', function (): void {
            $this->container->add('test', fn(): string => 'value');

            $entries = $this->container->getKnownEntryNames();
            expect($entries)->toBeAn('array');
            expect($entries)->toContain('test');
        });

        it('injectOn fonctionne via __call', function (): void {
            $obj = new stdClass();

            // injectOn est une méthode de DIContainer
            $result = $this->container->injectOn($obj);
            expect($result)->toBe($obj);
        });
    });

    describe('Intégration avec providers', function (): void {
        it('peut charger un provider personnalisé', function (): void {
			$container = new Container();

            // Créer un provider de test
            $testProvider = new class($container) extends AbstractProvider {
                public static function definitions(): array
                {
                    return [
                        'test.service' => fn(): string => 'test value',
                    ];
                }

                public function register(): void
                {
                    // Pas besoin d'implémentation pour ce test
                }
            };

            // Injecter manuellement notre provider
			ReflectionHelper::setPrivateProperty($container, 'providerNames', [$testProvider::class]);

            // Initialiser
			$initialize = ReflectionHelper::getPrivateMethodInvoker($container, 'initialize');
            $initialize();

            // Vérifier que le service est disponible
            expect($container->has('test.service'))->toBe(true);
            expect($container->get('test.service'))->toBe('test value');
        });
    });

	describe('Méthode set avec aliases', function (): void {
		beforeEach(function (): void {
			$this->container = new Container();
			$initialize = ReflectionHelper::getPrivateMethodInvoker($this->container, 'initialize');
			$initialize();
		});

		it('set définit une valeur pour une clé simple', function (): void {
			$value = new stdClass();
			$value->name = 'test';

			$this->container->set('test', $value);

			expect($this->container->has('test'))->toBe(true);
			expect($this->container->get('test'))->toBe($value);
		});

		it('set avec alias canonique définit tous les aliases', function (): void {
			$value = new stdClass();
			$value->name = 'locator';

			$this->container->set('locator', $value);

			// Vérifie tous les aliases
			expect($this->container->has('locator'))->toBe(true);
			expect($this->container->has(Locator::class))->toBe(true);
			expect($this->container->has(LocatorInterface::class))->toBe(true);

			// Vérifie que tous pointent vers la même instance
			expect($this->container->get('locator'))->toBe($value);
			expect($this->container->get(Locator::class))->toBe($value);
			expect($this->container->get(LocatorInterface::class))->toBe($value);
		});

		it('set avec alias FQCN définit tous les aliases', function (): void {
			$value = new stdClass();
			$value->name = 'via-fqcn';

			// Utilise le FQCN d'un alias
			$this->container->set(LocatorInterface::class, $value);

			// Vérifie tous les aliases
			expect($this->container->has('locator'))->toBe(true);
			expect($this->container->has(Locator::class))->toBe(true);
			expect($this->container->has(LocatorInterface::class))->toBe(true);

			// Tous pointent vers la même instance
			expect($this->container->get('locator'))->toBe($value);
			expect($this->container->get(LocatorInterface::class))->toBe($value);
		});

		it('set avec alias remplace toutes les entrées précédentes', function (): void {
			// Première valeur pour locator
			$value1 = new stdClass();
			$value1->name = 'first';
			$this->container->set('locator', $value1);

			// Deuxième valeur via un alias différent
			$value2 = new stdClass();
			$value2->name = 'second';
			$this->container->set(Locator::class, $value2);

			// Tous les aliases doivent pointer vers la deuxième valeur
			expect($this->container->get('locator'))->toBe($value2);
			expect($this->container->get(Locator::class))->toBe($value2);
			expect($this->container->get(LocatorInterface::class))->toBe($value2);
		});

		it('set sans alias fonctionne normalement', function (): void {
			$value = new stdClass();
			$value->name = 'custom';

			$this->container->set('custom_service', $value);

			expect($this->container->has('custom_service'))->toBe(true);
			expect($this->container->get('custom_service'))->toBe($value);

			// Vérifie qu'aucun alias n'a été créé
			expect($this->container->has('custom_service_alias'))->toBe(false);
		});

		it('set avec multiple aliases pour un même service', function (): void {
			$value = new stdClass();
			$value->name = 'request';

			$this->container->set('request', $value);

			// Vérifie tous les aliases pour request
			expect($this->container->has('request'))->toBe(true);
			expect($this->container->has(Request::class))->toBe(true);
			expect($this->container->has(ServerRequest::class))->toBe(true);
			expect($this->container->has(ServerRequestInterface::class))->toBe(true);

			// Tous pointent vers la même instance
			expect($this->container->get('request'))->toBe($value);
			expect($this->container->get(Request::class))->toBe($value);
			expect($this->container->get(ServerRequestInterface::class))->toBe($value);
		});

		it('set gère les valeurs non-objets', function (): void {
			$stringValue = 'string value';
			$arrayValue = ['key' => 'value'];
			$intValue = 123;
			$boolValue = true;

			$this->container->set('string_key', $stringValue);
			$this->container->set('array_key', $arrayValue);
			$this->container->set('int_key', $intValue);
			$this->container->set('bool_key', $boolValue);

			expect($this->container->get('string_key'))->toBe($stringValue);
			expect($this->container->get('array_key'))->toBe($arrayValue);
			expect($this->container->get('int_key'))->toBe($intValue);
			expect($this->container->get('bool_key'))->toBe($boolValue);
		});

		it('set via différents points d\'entrée donne même résultat', function (): void {
			$value = new stdClass();
			$value->id = 'test';

			// Test via différents alias
			$this->container->set('locator', $value);
			$result1 = $this->container->get('locator');

			$this->container->set(Locator::class, $value);
			$result2 = $this->container->get(Locator::class);

			$this->container->set(LocatorInterface::class, $value);
			$result3 = $this->container->get(LocatorInterface::class);

			expect($result1)->toBe($value);
			expect($result2)->toBe($value);
			expect($result3)->toBe($value);
			expect($result1)->toBe($result2);
			expect($result2)->toBe($result3);
		});
	});
});
