<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
    beforeEach(function () {
		ReflectionHelper::setPrivateProperty(Container::class, 'providerNames', []);
		ReflectionHelper::setPrivateProperty(Container::class, 'discovered', false);
    });

    afterEach(function () {
        // Container::reset();
    });

    describe('Initialisation', function () {
        it('initialize construit le container', function () {
            $container = new Container();
			$initialized = ReflectionHelper::getPrivateProperty($container,'initialized');
			$initialize = ReflectionHelper::getPrivateMethodInvoker($container,'initialize');

			expect($initialized)->toBeFalsy();
			$initialize();

			$initialized = ReflectionHelper::getPrivateProperty($container,'initialized');
			expect($initialized)->toBeTruthy();
        });

        it('initialize ne fait rien si déjà initialisé', function () {
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

        it('initialize avec cache en production', function () {
            allow('on_prod')->toBeCalled()->andReturn(true);
            allow('extension_loaded')->toBeCalled()->with('apcu')->andReturn(true);

            $container = new Container();
            $method = new ReflectionMethod($container, 'initialize');
            $method->setAccessible(true);
            $method->invoke($container);

            expect($container)->toBeAnInstanceOf(Container::class);
        });
    });

    describe('Méthodes de base', function () {
        beforeEach(function () {
            $this->container = new Container();
			$initialize = ReflectionHelper::getPrivateMethodInvoker($this->container,'initialize');
			$initialize();
        });

        it('get retourne une entrée', function () {
            // Définir une entrée de test
            $this->container->add('test', fn() => 'value');

            expect($this->container->get('test'))->toBe('value');
        });

        it('get lève NotFoundException si entrée inexistante', function () {
            expect(fn() => $this->container->get('inexistant'))
                ->toThrow(new NotFoundException());
        });

        it('has vérifie l\'existence d\'une entrée', function () {
            $this->container->add('test', fn() => 'value');

            expect($this->container->has('test'))->toBe(true);
            expect($this->container->has('inexistant'))->toBe(false);
        });

        it('make crée une nouvelle instance', function () {
            $this->container->add('test', fn() => new stdClass());

            $instance1 = $this->container->make('test');
            $instance2 = $this->container->make('test');

            expect($instance1)->toBeAnInstanceOf(stdClass::class);
            expect($instance2)->toBeAnInstanceOf(stdClass::class);
            expect($instance1)->not->toBe($instance2);
        });

        it('make avec paramètres', function () {
            $this->container->add('test', function ($container, $param) {
                $obj = new stdClass();
                $obj->param = $param;
                return $obj;
            });

            $instance = $this->container->make('test', ['param' => 'value']);
            expect($instance->param)->toBe('value');
        });

        it('call appelle une fonction avec injection', function () {
            $callback = function (stdClass $dep) {
                return 'called with ' . get_class($dep);
            };

            $this->container->add(stdClass::class, fn() => new stdClass());

            $result = $this->container->call($callback);
            expect($result)->toMatch('/called with stdClass/');
        });

        it('call avec paramètres supplémentaires', function () {
            $callback = function ($name, stdClass $dep) {
                return $name . ' with ' . get_class($dep);
            };

            $this->container->add(stdClass::class, fn() => new stdClass());

            $result = $this->container->call($callback, ['name' => 'test']);
            expect($result)->toMatch('/test with stdClass/');
        });
    });

    describe('Gestion des entrées', function () {
        beforeEach(function () {
            $this->container = new Container();
            $initialize = ReflectionHelper::getPrivateMethodInvoker($this->container,'initialize');
            $initialize();
        });

        it('add définit une entrée', function () {
            $callback = fn() => 'value';
            $this->container->add('test', $callback);

            expect($this->container->has('test'))->toBe(true);
            expect($this->container->get('test'))->toBe('value');
        });

        it('add remplace une entrée existante', function () {
            $this->container->add('test', fn() => 'first');
            expect($this->container->get('test'))->toBe('first');

            $this->container->add('test', fn() => 'second');
            expect($this->container->get('test'))->toBe('second');
        });

        it('addIf ignore si entrée existe déjà', function () {
            $this->container->add('test', fn() => 'first');

            $this->container->addIf('test', fn() => 'second');
            expect($this->container->get('test'))->toBe('first');
        });

        it('addIf ajoute si entrée n\'existe pas', function () {
            $this->container->addIf('test', fn() => 'value');
            expect($this->container->get('test'))->toBe('value');
        });

        it('merge fusionne plusieurs entrées', function () {
            $callbacks = [
                'key1' => fn() => 'value1',
                'key2' => fn() => 'value2',
            ];

            $this->container->merge($callbacks);

            expect($this->container->get('key1'))->toBe('value1');
            expect($this->container->get('key2'))->toBe('value2');
        });

        it('merge ignore les valeurs non-Closure', function () {
            $callbacks = [
                'key1' => fn() => 'value1',
                'key2' => 'not a closure',
            ];

            $this->container->merge($callbacks);

            expect($this->container->has('key1'))->toBe(true);
            expect($this->container->has('key2'))->toBe(false);
        });

        it('mergeIf fusionne conditionnellement', function () {
            $this->container->add('key1', fn() => 'first');

            $callbacks = [
                'key1' => fn() => 'second',
                'key2' => fn() => 'value2',
            ];

            $this->container->mergeIf($callbacks);

            expect($this->container->get('key1'))->toBe('first'); // Pas remplacé
            expect($this->container->get('key2'))->toBe('value2'); // Ajouté
        });

        it('bound vérifie les entrées explicitement définies', function () {
            $this->container->add('test', fn() => 'value');

            expect($this->container->bound('test'))->toBe(true);
            expect($this->container->bound('inexistant'))->toBe(false);
        });
    });

    describe('Découverte des providers', function () {
        it('discoverProviders découvre les providers', function () {
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
            $method->setAccessible(true);
            $method->invoke($container);

            $reflection = new ReflectionClass(Container::class);
            $discovered = $reflection->getProperty('discovered');
            $discovered->setAccessible(true);

            expect($discovered->getValue())->toBe(true);
        });

        it('discoverProviders ne fait rien si déjà découvert', function () {
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

        it('discoverProviders filtre par sous-classe de AbstractProvider', function () {
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

        it('discoverProviders ignore les classes non-Provider', function () {
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

    describe('Enregistrement des providers', function () {
        xit('registerProviders enregistre les providers', function () {
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
            $providerNames->setAccessible(true);
            $providerNames->setValue(null, [get_class($mockProvider)]);

            // Mock le container DI pour retourner notre mock
            $mockDIContainer = Double::instance(['class' => DIContainer::class]);
            allow($mockDIContainer)->toReceive('make')
				->with(get_class($mockProvider), ['container' => $container])
				->andReturn($mockProvider);

            $containerReflection = new ReflectionClass($container);
            $containerProp = $containerReflection->getProperty('container');
            $containerProp->setAccessible(true);
            $containerProp->setValue($container, $mockDIContainer);

            $method = new ReflectionMethod($container, 'registerProviders');
            $method->setAccessible(true);
            $method->invoke($container);

            expect($mockProvider)->toReceive('register');
        });

        it('registerProviders définit self et interface dans le container', function () {
            $container = new Container();

            // Initialiser le container interne
            $initialize = new ReflectionMethod($container, 'initialize');
            $initialize->setAccessible(true);
            $initialize->invoke($container);

            // Vérifier que le container est défini
            expect($container->has(Container::class))->toBe(true);
            expect($container->has(ContainerInterface::class))->toBe(true);
            expect($container->get(Container::class))->toBe($container);
            expect($container->get(ContainerInterface::class))->toBe($container);
        });
    });

    describe('Magie __call', function () {
        beforeEach(function () {
            $this->container = new Container();
			$initialize = ReflectionHelper::getPrivateMethodInvoker($this->container, 'initialize');
			$initialize();
        });

        it('__call délègue aux méthodes du container DI', function () {
            // set est une méthode de DIContainer
            $this->container->set('test', 'value');
            expect($this->container->get('test'))->toBe('value');
        });

        it('__call lève BadMethodCallException pour méthode inconnue', function () {
            expect(fn() => $this->container->unknownMethod())
                ->toThrow(new BadMethodCallException("Méthode 'unknownMethod' inconnue sur DIContainer."));
        });
    });

   	describe('Méthodes magiques déléguées', function () {
        beforeEach(function () {
            $this->container = new Container();
			$initialize = ReflectionHelper::getPrivateMethodInvoker($this->container, 'initialize');
			$initialize();
        });

        it('debugEntry fonctionne via __call', function () {
            $this->container->add('test', fn() => 'value');

            // debugEntry est une méthode de DIContainer
            $debug = $this->container->debugEntry('test');
            expect(is_string($debug))->toBeTruthy();
        });

        it('getKnownEntryNames fonctionne via __call', function () {
            $this->container->add('test', fn() => 'value');

            $entries = $this->container->getKnownEntryNames();
            expect($entries)->toBeAn('array');
            expect($entries)->toContain('test');
        });

        it('injectOn fonctionne via __call', function () {
            $obj = new stdClass();

            // injectOn est une méthode de DIContainer
            $result = $this->container->injectOn($obj);
            expect($result)->toBe($obj);
        });
    });

    describe('Intégration avec providers', function () {
        it('peut charger un provider personnalisé', function () {
			$container = new Container();

            // Créer un provider de test
            $testProvider = new class($container) extends AbstractProvider {
                public static function definitions(): array
                {
                    return [
                        'test.service' => fn() => 'test value',
                    ];
                }

                public function register(): void
                {
                    // Pas besoin d'implémentation pour ce test
                }
            };

            // Injecter manuellement notre provider
			ReflectionHelper::setPrivateProperty($container, 'providerNames', [get_class($testProvider)]);

            // Initialiser
			$initialize = ReflectionHelper::getPrivateMethodInvoker($container, 'initialize');
            $initialize();

            // Vérifier que le service est disponible
            expect($container->has('test.service'))->toBe(true);
            expect($container->get('test.service'))->toBe('test value');
        });
    });
});
