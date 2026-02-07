<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Config\Config;
use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Spec\ReflectionHelper;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

use function Kahlan\expect;

describe('Config / Config', function (): void {
	beforeAll(function(): void {
		// Méthode utilitaire pour nettoyer les dossiers
		$this->recursiveDelete = function(string $dir): void {
			if (!is_dir($dir)) {
				return;
			}

			$files = array_diff(scandir($dir), ['.', '..']);
			foreach ($files as $file) {
				$path = $dir . '/' . $file;
				is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
			}

			rmdir($dir);
		};


        // Créer un dossier temporaire pour les tests
        $this->tempDir = sys_get_temp_dir() . '/blitz-config-test';
		mkdir($this->tempDir, 0777, true);
		mkdir($this->tempDir . '/Config', 0777, true);
		mkdir($this->tempDir . '/Config/schemas', 0777, true);
	});

	beforeEach(function(): void {
		$this->config       = service('config');
		$this->configurator = ReflectionHelper::getPrivateProperty($this->config, 'configurator');
    });

	afterAll(function(): void {
        // Nettoyer le dossier temporaire
        if (is_dir($this->tempDir)) {
            $this->recursiveDelete($this->tempDir);
        }

        // Réinitialiser le configurator pour les tests
        $loaded = ReflectionHelper::setPrivateProperty(Config::class, 'loaded', []);
        $originals = ReflectionHelper::setPrivateProperty(Config::class, 'originals', []);
        $registrars = ReflectionHelper::setPrivateProperty(Config::class, 'registrars', []);
        $didDiscovery = ReflectionHelper::setPrivateProperty(Config::class, 'didDiscovery', false);
    });

    describe('Initialisation', function (): void {
        it('La config est toujours initialisee', function (): void {
            $initialized  = ReflectionHelper::getPrivateProperty(Config::class, 'initialized');
            $configurator = ReflectionHelper::getPrivateProperty($this->config, 'configurator');
            $finalConfig  = ReflectionHelper::getPrivateProperty($configurator, 'finalConfig');

            expect($initialized)->toBeTruthy();
            expect($finalConfig)->not->toBeNull();
        });

        it('La config charge bien les fichiers', function (): void {
			$loaded  = ReflectionHelper::getPrivateProperty(Config::class, 'loaded');

			expect($loaded)->toBeA('array');
			expect($loaded)->toContainKey('app');
			expect($loaded['app'])->toBe(config_path('app'));
        });

        it('La methode load charge belle et bien le fichier de config', function (): void {
			$loaded  = ReflectionHelper::getPrivateProperty(Config::class, 'loaded');

			// Soyons sur que seul les fichiers necessaires sont charges
			expect($loaded)->not->toContainKeys(['toolbar', 'publisher']);

			$this->config->load(['publisher']);
			$loaded  = ReflectionHelper::getPrivateProperty(Config::class, 'loaded');

			expect($loaded)->not->toContainKey('toolbar');
			expect($loaded)->toContainKey('publisher');

			expect($this->config->get('publisher.restrictions'))->toContainKeys([ROOTPATH, WEBROOT]);
		});
    });

    describe('Getters et setters', function (): void {
        it('has, exists, missing', function (): void {
			expect($this->config->has('appl'))->toBeFalsy();
			expect($this->config->has('app'))->toBeTruthy();

			expect($this->config->exists('app'))->toBeTruthy();
			expect($this->config->missing('app'))->toBeFalsy();
        });

        it('get', function (): void {
            expect($this->config->get('app.environment'))->toBe('testing');
            expect(fn() => $this->config->get('app.environement'))->toThrow(new ConfigException());
            expect($this->config->get('app.environement', 'default'))->toBe('default');
        });

        it('set', function (): void {
			$env = $this->config->get('app.environment');

			$this->config->set('app.environement', 'dev');
            expect($this->config->get('app.environement'))->toBe('dev');

			$this->config->set('app.environement', $env);
            expect($this->config->get('app.environement'))->toBe('testing');
        });

        it('set d\'une config abscente', function (): void {
			$this->config->set('appl.environement', 'dev');
			expect(fn() => $this->config->get('appl.environement'))->toThrow(new ConfigException());

			$this->config->ghost('appl');
			$this->config->set('appl.environement', 'dev');
            expect($this->config->get('appl.environement'))->toBe('dev');
        });
    });

	describe('Autres', function (): void {
		it('path', function (): void {
			expect(Config::file('app'))->toBe(config_path('app'));
			expect(Config::file('appl'))->toBeEmpty();
		});

		it('schema', function (): void {
			expect(Config::schema('app'))->toBeAnInstanceOf(Schema::class);
			expect(Config::schema('appl'))->toBeNull();
		});

		it('reset', function (): void {
			expect($this->config->get('app.environment'))->toBe('testing');
			expect($this->config->get('app.negotiate_locale'))->toBeTruthy();

			$this->config->set('app.environment', 'production');
			expect($this->config->get('app.environment'))->toBe('production');
			$this->config->set('app.negotiate_locale', false);
			expect($this->config->get('app.negotiate_locale'))->toBeFalsy();

			$this->config->reset('app');
			expect($this->config->get('app.environment'))->toBe('testing');
			expect($this->config->get('app.negotiate_locale'))->toBeTruthy();
		});

		it('reset multple', function (): void {
			expect($this->config->get('app.environment'))->toBe('testing');
			expect($this->config->get('publisher.restrictions'))->toContainKeys([ROOTPATH, WEBROOT]);
			expect($this->config->get('app.negotiate_locale'))->toBeTruthy();

			$this->config->set('app.environment', 'production');
			expect($this->config->get('app.environment'))->toBe('production');

			$this->config->set('publisher.restrictions', [WEBROOT => '*']);
			expect($this->config->get('publisher.restrictions'))->toBe([WEBROOT => '*']);

			$this->config->set('app.negotiate_locale', false);
			expect($this->config->get('app.negotiate_locale'))->toBeFalsy();

			$this->config->reset(['app.environment', 'publisher']);
			expect($this->config->get('app.environment'))->toBe('testing');
			expect($this->config->get('publisher.restrictions'))->toContainKeys([ROOTPATH, WEBROOT]);

			// On a pas reset negotiate_locale, donc on conserve la valeur modifiee
			expect($this->config->get('app.negotiate_locale'))->toBeFalsy();
		});
	});

	describe('Chargement avancé', function (): void {
        it('load avec chemin de fichier personnalisé', function (): void {
            // Créer un fichier de config temporaire
            $tempFile = $this->tempDir . '/custom.php';
            file_put_contents($tempFile, '<?php return ["test" => "value"];');

            $this->config->load('custom', $tempFile);

            expect($this->config->get('custom.test'))->toBe('value');
        });

        it('load avec schéma personnalisé', function (): void {
            $schema = Expect::structure([
                'test' => Expect::string()->required()
            ]);

            $this->config->ghost('custom', $schema);
            $this->config->set('custom.test', 'value');

            expect($this->config->get('custom.test'))->toBe('value');
        });

        it('load avec tableau vide autorisé', function (): void {
            $this->config->ghost('emptyconfig');
            expect($this->config->exists('emptyconfig'))->toBeTruthy();

            // Vérifier qu'on peut setter après
            $this->config->set('emptyconfig.somekey', 'value');
            expect($this->config->get('emptyconfig.somekey'))->toBe('value');
        });

        it('load sans fichier existant', function (): void {
            $before = ReflectionHelper::getPrivateProperty(Config::class, 'loaded');
            expect($before)->not->toContainKey('nonexistent');

            // Ne devrait rien charger
            $this->config->load('nonexistent');

            $after = ReflectionHelper::getPrivateProperty(Config::class, 'loaded');
            expect($after)->not->toContainKey('nonexistent');
        });

        it('load avec fichier déjà inclus', function (): void {
            // Créer un fichier qui se charge lui-même
            $tempFile = $this->tempDir . '/selfincluded.php';
            file_put_contents($tempFile, '<?php return ["self" => "loaded"];');

            // Inclure une première fois
            require $tempFile;

            // Essayer de charger via Config (ne devrait pas charger à nouveau)
            $this->config->load('selfincluded', $tempFile);

            // Le fichier ne devrait pas être marqué comme chargé
            $loaded = ReflectionHelper::getPrivateProperty(Config::class, 'loaded');
            expect($loaded)->toContainKey('selfincluded');
			expect($this->config->get('selfincluded.self'))->toThrow();
        });

        it('loadMultiple avec différents formats', function (): void {
            $tempFile1 = $this->tempDir . '/test1.php';
            $tempFile2 = $this->tempDir . '/test2.php';

            file_put_contents($tempFile1, '<?php return ["key1" => "value1"];');
            file_put_contents($tempFile2, '<?php return ["key2" => "value2"];');

            // Format 1: ['config1', 'config2']
            $this->config->load(['test1', 'test2']);

            // Format 2: ['config1' => 'path1.php', 'config2' => 'path2.php']
            $this->config->load(['test3' => $tempFile1, 'test4' => $tempFile2]);

            expect($this->config->exists('test1'))->toBeFalsy(); // Pas de fichier
            expect($this->config->exists('test3.key1'))->toBeTruthy();
            expect($this->config->get('test3.key1'))->toBe('value1');
        });
    });

	describe('Auto-détection', function (): void {
        it('initializeURL avec base_url auto', function (): void {
            // Sauvegarder la valeur originale
            $original = $this->config->get('app.base_url');

            // Tester avec 'auto'
            $this->config->set('app.base_url', 'auto');

            $initializeURL = ReflectionHelper::getPrivateMethodInvoker($this->config, 'initializeURL');
            $initializeURL();

            $newValue = $this->config->get('app.base_url');
            expect($newValue)->not->toBe('auto');
            expect($newValue)->not->toBeEmpty();

            // Restaurer
            $this->config->set('app.base_url', $original);
        });

        it('initializeEnvironment avec différentes valeurs', function (): void {
            // Tester différents environnements
            $environments = [
                'development' => 'development',
                'dev'         => 'development',
                'production'  => 'production',
                'prod'        => 'production',
                'testing'     => 'testing',
                'test'        => 'testing',
                'auto'        => is_online() ? 'production' : 'development'
            ];

            foreach ($environments as $input => $expected) {
                $this->config->set('app.environment', $input);

                $initializeEnv = ReflectionHelper::getPrivateMethodInvoker($this->config, 'initializeEnvironment');
                $initializeEnv();

                expect($this->config->get('app.environment'))->toBe($expected);
            }

            // Test avec valeur invalide
            // $this->config->set('app.environment', 'invalid');
            // expect(fn() => $this->config->get('app.environment'))->toThrow(new ConfigException());
        });
    });

    describe('Gestion des erreurs', function (): void {
		it('Chargement avec schéma invalide', function (): void {
            // Créer un fichier de schéma invalide
            $invalidSchema = $this->tempDir . '/schemas/invalid.config.php';
			if (! is_dir($dir = dirname($invalidSchema))) {
				mkdir($dir, 0777, true);
			}
            file_put_contents($invalidSchema, '<?php return "not-a-schema";');

            // Configurer le locator pour trouver ce schéma
            allow(service('locator'))->toReceive('search')->with('Config/schemas/invalid')->andReturn([$invalidSchema]);

            // Doit retourner null pour un schéma invalide
            expect(Config::schema('invalid'))->toBeNull();
        });

        it('Fichier de config invalide (ne retourne pas un tableau)', function (): void {
            $tempFile = $this->tempDir . '/invalid.php';
			if (! is_dir($dir = dirname($tempFile))) {
				mkdir($dir, 0777, true);
			}
            file_put_contents($tempFile, '<?php return "not-an-array";');

            $this->config->load('invalid', $tempFile);

            // Ne devrait pas charger car pas un tableau
            $loaded = ReflectionHelper::getPrivateProperty(Config::class, 'loaded');
            expect($loaded)->not->toContainKey('invalid');
        });
    });

    describe('Edge cases', function (): void {
        it('Accès à une clé avec get sans default sur config inexistante', function (): void {
            expect(fn() => $this->config->get('nonexistent.config.key'))
                ->toThrow(new ConfigException());
        });

        it('reset avec clé spécifique', function (): void {
            // Modifier plusieurs valeurs
            $this->config->set('app.environment', 'production');
            $this->config->set('app.locale', 'fr');

            // Reset seulement environment
            $this->config->reset('app.environment');

            expect($this->config->get('app.environment'))->toBe('testing'); // Valeur par défaut
            expect($this->config->get('app.locale'))->toBe('fr'); // Doit rester modifié
        });

        it('reset avec null (tout reset)', function (): void {
            // Modifier
            $this->config->set('app.environment', 'production');
            $this->config->load('publisher');
            $this->config->set('publisher.restrictions', [WEBROOT => '*']);

            // Tout reset
            $this->config->reset();

            expect($this->config->get('app.environment'))->toBe('testing');
            expect($this->config->get('publisher.restrictions'))->toContainKeys([ROOTPATH, WEBROOT]);
        });

        it('Méthodes chainées avec ghost', function (): void {
            $result = $this->config->ghost('chainable')
                ->set('chainable.key1', 'value1')
                ->set('chainable.key2', 'value2');

            expect($result)->toBe($this->config);
            expect($this->config->get('chainable.key1'))->toBe('value1');
            expect($this->config->get('chainable.key2'))->toBe('value2');
        });
    });

    describe('Performance et cache', function (): void {
        it('Cache est utilisé pour les appels répétés', function (): void {
            // Premier appel
            $start1 = microtime(true);
            $value1 = $this->config->get('app.environment');
            $time1 = microtime(true) - $start1;

            // Deuxième appel (devrait être plus rapide avec cache)
            $start2 = microtime(true);
            $value2 = $this->config->get('app.environment');
            $time2 = microtime(true) - $start2;

            expect($value1)->toBe($value2);
            // Note: Sur certains systèmes, la différence peut être minime
            // mais en principe $time2 devrait être <= $time1
			expect($time2)->toBeLessThan($time1);
        });

        it('Cache est invalidé après set', function (): void {
            // Premier get
            $value1 = $this->config->get('app.environment');

            // Modification
            $this->config->set('app.environment', 'production');

            // Deuxième get (doit être différent)
            $value2 = $this->config->get('app.environment');

            expect($value1)->not->toBe($value2);
            expect($value2)->toBe('production');

			$this->config->set('app.environment', $value1);
        });
    });

	describe('Registrars', function (): void {
        it('Découverte des registrars', function (): void {
            // Créer un fichier Registrar de test
            $registrarFile = config_path('Registrar.php');
            $registrarContent = <<<'PHP'
<?php

namespace App\Config;

class Registrar
{
    public static function TestConfig(): array
    {
        return [
            'key1' => 'from-registrar',
            'key2' => ['nested' => 'value']
        ];
    }

    public static function AnotherConfig(): array
    {
        return ['another' => 'value'];
    }

    // Méthode qui ne retourne pas un tableau (doit être ignorée)
    public static function InvalidMethod(): string
    {
        return 'not-an-array';
    }

    // Méthode privée (doit être ignorée)
    private static function PrivateMethod(): array
    {
        return ['private' => 'ignored'];
    }
}
PHP;

			if (! is_dir($dir = dirname($registrarFile))) {
				mkdir($dir, 0777, true);
			}
            file_put_contents($registrarFile, $registrarContent);

            // Reset la decouverte pour forcer le chargeur du registrar a s'executer de nouveau
            ReflectionHelper::setPrivateProperty(Config::class, 'didDiscovery', false);
            ReflectionHelper::setPrivateProperty(Config::class, 'discovering', false);

			// Appeler loadRegistrar via reflection
            $loadRegistrar = ReflectionHelper::getPrivateMethodInvoker($this->config, 'loadRegistrar');
            $loadRegistrar('test');

            $registrars = ReflectionHelper::getPrivateProperty(Config::class, 'registrars');

			expect($registrars)->toContainKeys(['TestConfig', 'AnotherConfig']);
            expect($registrars)->not->toContainKeys(['InvalidMethod', 'PrivateMethod']);
            expect($registrars['TestConfig']['key1'])->toBe('from-registrar');

			unlink($registrarFile);
        });

        it('Double découverte des registrars', function (): void {
            // Configurer pour être en train de découvrir
            ReflectionHelper::setPrivateProperty(Config::class, 'discovering', true);

            // Doit lancer une exception si on essaie de redécouvrir
            expect(fn() => $this->config->__construct())->toThrow();
        });
    });
});
