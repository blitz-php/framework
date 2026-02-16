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
use BlitzPHP\Container\Container;
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Event\EventListenerInterface;
use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Event\EventDiscover;
use BlitzPHP\Event\EventManager;
use BlitzPHP\Utilities\Reflection\ReflectionClass;

use function Kahlan\expect;

describe('Events / EventDiscover', function (): void {

    // Mock du localisateur
    $mockLocator = new class(service('autoloader')) extends Locator {
        public array $files = [];
        public array $classNames = [];

        public function listFiles(string $path): array
        {
            return $this->files[$path] ?? [];
        }

        public function getClassname(string $file): string
        {
            return $this->classNames[$file] ?? '';
        }
    };

    // Mock du conteneur
    $mockContainer = new class() extends Container {
        public array $instances = [];

        public function make(string $className, array $parameters = []): mixed
        {
            return $this->instances[$className] ?? new $className();
        }
    };

    // Écouteurs de test
    class TestListener1 implements EventListenerInterface
    {
        public static bool $listened = false;

        public function listen(EventManagerInterface $manager): void
        {
            self::$listened = true;
        }
    }

    class TestListener2 implements EventListenerInterface
    {
        public static bool $listened = false;

        public function listen(EventManagerInterface $manager): void
        {
            self::$listened = true;
        }
    }

    // Classe abstraite (ne doit pas être découverte)
    abstract class AbstractListener implements EventListenerInterface
    {
        public function listen(EventManagerInterface $manager): void {}
    }

    // Classe qui n'implémente pas l'interface (ne doit pas être découverte)
    class NotAnEventListener
    {
        public function someMethod(): void {}
    }

	$initialContainer = service('container');

	beforeAll(function () use($mockLocator, $mockContainer): void {
		Services::injectMock('container', $mockContainer);
		Services::injectMock('locator', $mockLocator);
	});

	afterAll(function () use($initialContainer): void {
		$reflection = new ReflectionClass(Services::class);
		$reflection->setValue('mocks', []);
		$instances = $reflection->getValue('instances');
		$instances['container'] = $initialContainer;
		$reflection->setValue('instances', $instances);

		Services::resetSingle('locator');
	});

    beforeEach(function () use ($mockLocator, $mockContainer): void {
        // Réinitialiser les statiques
        TestListener1::$listened = false;
        TestListener2::$listened = false;

        // Configurer le mock du localisateur
        $this->mockLocator = $mockLocator;
        $this->mockContainer = $mockContainer;

        // Créer le gestionnaire d'événements et le découvreur
        $this->eventManager = new EventManager();
        $this->eventDiscover = new EventDiscover($this->eventManager, $this->mockLocator);
    });

    describe('Configuration des chemins', function (): void {
        it('Initialise avec les chemins par défaut', function (): void {
            $paths = $this->eventDiscover->getPaths();

            expect($paths)->toContain('Listeners');
            expect($paths)->toContain('Events'); // Pour la compatibilité
        });

        it('Ajoute un chemin', function (): void {
            $this->eventDiscover->addPath('Custom/Listeners/');
            $paths = $this->eventDiscover->getPaths();

            expect($paths)->toContain('Custom/Listeners/');
        });

        it('Ne duplique pas les chemins', function (): void {
            $this->eventDiscover->addPath('Custom/Path/');
            $this->eventDiscover->addPath('Custom/Path/');

            $paths = $this->eventDiscover->getPaths();
            $customPathCount = array_count_values($paths)['Custom/Path/'] ?? 0;

            expect($customPathCount)->toBe(1);
        });

        it('Lève une exception si le chemin est vide', function (): void {
			$eventDiscover = clone $this->eventDiscover;

            expect(static function () use ($eventDiscover): void {
                $eventDiscover->addPath('');
            })->toThrow(new InvalidArgumentException('Le chemin ne peut pas être vide.'));
        });

        it('Définit de nouveaux chemins', function (): void {
            $this->eventDiscover->setPaths(['Path1/', 'Path2/']);

            $paths = $this->eventDiscover->getPaths();
            expect($paths)->toBe(['Path1/', 'Path2/']);
        });

        it('Efface les chemins existants lors du set', function (): void {
            $initialCount = count($this->eventDiscover->getPaths());

            $this->eventDiscover->setPaths(['NewPath/']);
            $paths = $this->eventDiscover->getPaths();

            expect(count($paths))->toBe(1);
            expect($paths)->toContain('NewPath/');
        });
    });

    describe('Découverte d\'écouteurs', function (): void {
        beforeEach(function (): void {
            // Configurer les fichiers de test
            $this->mockLocator->files = [
                'Listeners' => [
                    '/path/to/TestListener1.php',
                    '/path/to/TestListener2.php',
                    '/path/to/AbstractListener.php',
                    '/path/to/NotAnEventListener.php',
                ]
            ];

            $this->mockLocator->classNames = [
                '/path/to/TestListener1.php' => TestListener1::class,
                '/path/to/TestListener2.php' => TestListener2::class,
                '/path/to/AbstractListener.php' => AbstractListener::class,
                '/path/to/NotAnEventListener.php' => NotAnEventListener::class,
            ];

            // Simuler l'existence des classes
            if (!class_exists(TestListener1::class)) {
                eval('class TestListener1 implements BlitzPHP\Contracts\Event\EventListenerInterface {
                    public static $listened = false;
                    public function listen(BlitzPHP\Contracts\Event\EventManagerInterface \$manager): void {
                        self::\$listened = true;
                    }
                }');
            }

            if (!class_exists(TestListener2::class)) {
                eval('class TestListener2 implements BlitzPHP\Contracts\Event\EventListenerInterface {
                    public static $listened = false;
                    public function listen(BlitzPHP\Contracts\Event\EventManagerInterface \$manager): void {
                        self::\$listened = true;
                    }
                }');
            }

			ReflectionClass::make($this->eventDiscover)->setValue('locator', $this->mockLocator);
        });

        it('Découvre et enregistre les écouteurs valides', function (): void {
			$count = $this->eventDiscover->discover();

            expect($count)->toBe(2);
            expect(TestListener1::$listened)->toBe(true);
            expect(TestListener2::$listened)->toBe(true);
        });

        it('Ignore les classes qui n\'implémentent pas EventListenerInterface', function (): void {
            $count = $this->eventDiscover->discover();

            // Seuls TestListener1 et TestListener2 doivent être découverts
            expect($count)->toBe(2);
        });

        it('Ignore les chemins qui n\'existent pas', function (): void {
            $this->mockLocator->files['Inexistant/'] = [];

            // Simuler une exception quand le chemin n'existe pas
            ReflectionClass::make($this->eventDiscover)->setValue('locator', $this->mockLocator);

            $count = $this->eventDiscover->discover();

            // Devrait continuer avec les autres chemins
            expect($count)->toBe(2);
        });

        it('Élimine les doublons de fichiers', function (): void {
            // Ajouter des doublons
            $this->mockLocator->files['Listeners'] = array_merge(
                $this->mockLocator->files['Listeners'],
                ['/path/to/TestListener1.php'] // Dupliqué
            );
			ReflectionClass::make($this->eventDiscover)->setValue('locator', $this->mockLocator);

            $count = $this->eventDiscover->discover();

            // Ne devrait compter qu'une fois chaque écouteur
            expect($count)->toBe(2);
        });

        it('Utilise le cache après première découverte', function (): void {
            // Première découverte
            $count1 = $this->eventDiscover->discover();
            expect($count1)->toBe(2);

            // Réinitialiser les statiques pour vérifier si réexécuté
            TestListener1::$listened = false;
            TestListener2::$listened = false;

            // Deuxième découverte - devrait utiliser le cache
            $count2 = $this->eventDiscover->discover();

            expect($count2)->toBe(2); // Même compte
            expect(TestListener1::$listened)->toBe(false); // Non réexécuté
            expect(TestListener2::$listened)->toBe(false); // Non réexécuté
        });

        it('Efface le cache', function (): void {
            // Première découverte
            $this->eventDiscover->discover();

            // Effacer le cache
            $this->eventDiscover->clearCache();

            // Réinitialiser pour vérifier la réexécution
            TestListener1::$listened = false;
            TestListener2::$listened = false;

            // Redécouvrir
            $count = $this->eventDiscover->discover();

            expect($count)->toBe(2);
            expect(TestListener1::$listened)->toBe(true); // Réexécuté
            expect(TestListener2::$listened)->toBe(true); // Réexécuté
        });

        it('Récupère les classes découvertes', function (): void {
            $this->eventDiscover->discover();

            $classes = $this->eventDiscover->getDiscoveredClasses();

            expect($classes)->toBeAn('array');
            expect($classes)->toContain(TestListener1::class);
            expect($classes)->toContain(TestListener2::class);
            expect($classes)->not->toContain(NotAnEventListener::class);
        });

        it('Vérifie si une classe a été découverte', function (): void {
            $this->eventDiscover->discover();

            expect($this->eventDiscover->isDiscovered(TestListener1::class))->toBe(true);
            expect($this->eventDiscover->isDiscovered(TestListener2::class))->toBe(true);
            expect($this->eventDiscover->isDiscovered(NotAnEventListener::class))->toBe(false);
            expect($this->eventDiscover->isDiscovered('InexistantClass'))->toBe(false);
        });

        xit('Gère les erreurs lors de l\'instanciation', function (): void {
            // Simuler une erreur lors de l'instanciation
			$mockContainer = Mockery::mock($this->mockContainer);
			$mockContainer->shouldReceive('make')->with(TestListener1::class)->andReturn(function (): void {
                throw new Exception('Instantiation failed');
            });

			ReflectionClass::make($this->eventDiscover)->setValue('container', $mockContainer);

			$count = $this->eventDiscover->discover();

            // Devrait continuer avec les autres écouteurs
            expect($count)->toBe(1); // Seul TestListener2
            expect(TestListener2::$listened)->toBe(true);

            // L'erreur devrait être loggée
            // expect(count($mockLogger->errors))->toBe(1);
        });

        it('Ignore les classes qui n\'existent pas', function (): void {
            // Ajouter un fichier avec un nom de classe inexistant
            $this->mockLocator->files['Listeners'][] = '/path/to/InexistantClass.php';
            $this->mockLocator->classNames['/path/to/InexistantClass.php'] = 'Namespace\\InexistantClass';

            $count = $this->eventDiscover->discover();

            // Devrait ignorer la classe inexistante
            expect($count)->toBe(2);
        });
    });

    describe('Compatibilité', function (): void {
        it('Inclut le chemin Events pour la compatibilité', function (): void {
            $paths = $this->eventDiscover->getPaths();

            expect($paths)->toContain('Events');
        });
    });

    describe('Construction', function (): void {
        it('Utilise le localisateur par défaut si non fourni', function (): void {
            // Vérifier que le constructeur utilise service('locator') par défaut
            $discover = new EventDiscover($this->eventManager);

            // Le localisateur devrait être défini
            $ref = new ReflectionClass($discover);

            // Le localisateur devrait être celui fourni
            expect($ref->getValue('locator'))->toBe(service('locator'));
        });

        it('Utilise le localisateur fourni', function (): void {
            $customLocator = new class(service('autoloader')) extends Locator {};
            $discover = new EventDiscover($this->eventManager, $customLocator);

			$ref = new ReflectionClass($discover);

            // Le localisateur devrait être celui fourni
            expect($ref->getValue('locator'))->toBe($customLocator);
        });
    });
});
