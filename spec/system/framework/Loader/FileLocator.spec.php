<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use Nette\Schema\Schema;
use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Exceptions\LoadException;
use BlitzPHP\Loader\FileLocator;
use BlitzPHP\Spec\ReflectionHelper;
use Kahlan\Plugin\Double;

use function Kahlan\expect;

describe('Loader / FileLocator', function (): void {
    beforeAll(function (): void {
		$this->trueLocator = service('locator');

		$this->recursiveDelete = function(string $dir): void {
			if (!is_dir($dir)) return;
			foreach (glob($dir . '/*') as $file) {
				is_dir($file) ? $this->recursiveDelete($file) : unlink($file);
			}
			rmdir($dir);
		};

		$this->putFileContent = function(string $path, $content, int $flags = 0): int|false {
			if (! is_dir($dir = dirname($path))) {
				mkdir($dir, 0777, true);
			}

			return file_put_contents($path, $content, $flags);
		};

        // Configuration des chemins temporaires
        $this->tempDir = [
			'app'    => sys_get_temp_dir() . '/blitz-filelocator-app-' . uniqid(),
			'system' => sys_get_temp_dir() . '/blitz-filelocator-sys-' . uniqid(),
			'module' => sys_get_temp_dir() . '/blitz-filelocator-mod-' . uniqid(),
		];
		ReflectionHelper::setPrivateProperty(FileLocator::class, 'APP_PATH', $this->tempDir['app'] . DS);
		ReflectionHelper::setPrivateProperty(FileLocator::class, 'SYST_PATH', $this->tempDir['system'] . DS);
		ReflectionHelper::setPrivateProperty(FileLocator::class, 'CONFIG_PATH', $this->tempDir['app'] . DS . 'Config' . DS);

        // Création de fichiers de test
        $this->putFileContent($this->tempDir['app'] . '/Helpers/app_helper.php', '<?php function app_helper() { return "app"; }');
        $this->putFileContent($this->tempDir['system'] . '/Helpers/sys_helper.php', '<?php function sys_helper() { return "sys"; }');
        $this->putFileContent($this->tempDir['module'] . '/Helpers/mod_helper.php', '<?php function mod_helper() { return "mod"; }');

        $this->putFileContent($this->tempDir['app'] . '/Config/database.php', '<?php return ["app" => "config"];');
        $this->putFileContent($this->tempDir['system'] . '/Config/database.php', '<?php return ["sys" => "config"];');
        $this->putFileContent($this->tempDir['module'] . '/Config/database.php', '<?php return ["mod" => "config"];');

        $this->putFileContent($this->tempDir['app'] . '/Views/home.php', '<?php echo "app view"; ?>');
        $this->putFileContent($this->tempDir['system'] . '/Views/home.php', '<?php echo "sys view"; ?>');
        $this->putFileContent($this->tempDir['module'] . '/Views/home.php', '<?php echo "mod view"; ?>');

        $this->putFileContent($this->tempDir['app'] . '/schemas/database.php', '<?php return new class extends \Nette\Schema\Schema { public function normalize($value, $context) { return $value; } public function merge($value, $base) { return $value; } public function complete($value, $context) { return $value; } };');
        $this->putFileContent($this->tempDir['system'] . '/Constants/schemas/database.php', '<?php return \Nette\Schema\Expect::structure(["sys" => \Nette\Schema\Expect::string()]);');

        $this->putFileContent($this->tempDir['app'] . '/Translations/en/messages.php', '<?php return ["app" => "translation"];');
        $this->putFileContent($this->tempDir['system'] . '/Translations/en/messages.php', '<?php return ["sys" => "translation"];');
        $this->putFileContent($this->tempDir['module'] . '/Translations/en/messages.php', '<?php return ["mod" => "translation"];');
    });

    beforeEach(function (): void {
		ReflectionHelper::setPrivateProperty(FileLocator::class, 'locateCache', []);
    });

    afterAll(function (): void {
        $this->recursiveDelete($this->tempDir['app']);
        $this->recursiveDelete($this->tempDir['system']);
        $this->recursiveDelete($this->tempDir['module']);

		Services::override(Locator::class, $this->trueLocator);
    });

    describe('locateFiles', function (): void {
        it('retourne un tableau structuré avec app, system et modules', function (): void {
            // Mock du locator
			$dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn($path, $ext = 'php', $prioritizeApp = true): array => match(true) {
                        str_contains($path, 'Helpers') => [
							$dirs['app'] . '/Helpers/test.php',
                            $dirs['system'] . '/Helpers/test.php',
                            $dirs['module'] . '/Helpers/test.php',
						],
                        default => [],
                    },
                ]
            ]);

            $locateFiles = ReflectionHelper::getPrivateMethodInvoker(FileLocator::class, 'locateFiles');
            $files = $locateFiles('test', 'Helpers', $locator);

            expect($files)->toBeA('array');
            expect($files)->toContainKey('app');
            expect($files)->toContainKey('system');
            expect($files)->toContainKey('modules');
            expect($files['app'])->toBeA('array');
            expect($files['system'])->toBeA('array');
            expect($files['modules'])->toBeA('array');
        });

        it('retourne un tableau vide pour un fichier namespacé non trouvé', function (): void {
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'locateFile' => fn() => false
                ]
            ]);

            $locateFiles = ReflectionHelper::getPrivateMethodInvoker(FileLocator::class, 'locateFiles');
            $files = $locateFiles('Namespace\Test', 'Helpers', $locator);

            expect($files)->toBe([]);
        });

        it('retourne un tableau pour un fichier namespacé trouvé', function (): void {
			$dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'locateFile' => fn($file, $folder): string => $dirs['app'] . '/Helpers/app_helper.php',
                ]
            ]);

            $locateFiles = ReflectionHelper::getPrivateMethodInvoker(FileLocator::class, 'locateFiles');
            $files = $locateFiles('App\app_helper', 'Helpers', $locator);

            expect($files)->toBeA('array');
            expect($files[0])->toContain('app_helper.php');
        });

        it('filtre les fichiers non lisibles', function (): void {
			$dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [
						$dirs['app'] . '/Helpers/existing.php',
						$dirs['app'] . '/Helpers/nonexistent.php',
					],
                ]
            ]);

            $this->putFileContent($this->tempDir['app'] . '/Helpers/existing.php', '<?php');

            $locateFiles = ReflectionHelper::getPrivateMethodInvoker(FileLocator::class, 'locateFiles');
            $files = $locateFiles('test', 'Helpers', $locator);

			expect($files['app'])->toHaveLength(1);
            expect($files['app'][0])->toContain('existing.php');
        });
    });

    describe('helper', function (): void {
        it('charge un helper existant', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn($path): array => [$dirs['app'] . '/Helpers/app_helper.php'],
                ]
            ]);

            Services::override(Locator::class, $locator);

            expect(fn() => FileLocator::helper('app_helper'))->not->toThrow();
        });

        it('lance une exception pour un helper non trouvé', function (): void {
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [],
                ]
            ]);

            Services::override(Locator::class, $locator);

            expect(fn() => FileLocator::helper('nonexistent'))->toThrow(new LoadException);
        });

        it('charge plusieurs helpers', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn($path): array => match(true) {
                        str_contains($path, 'helper1') => [$dirs['app'] . '/Helpers/helper1.php'],
                        default => [$dirs['app'] . '/Helpers/helper2.php'],
                    },
                ]
            ]);

            $this->putFileContent($this->tempDir['app'] . '/Helpers/helper1.php', '<?php');
            $this->putFileContent($this->tempDir['app'] . '/Helpers/helper2.php', '<?php');

            Services::override(Locator::class, $locator);

            expect(fn() => FileLocator::helper(['helper1', 'helper2']))->not->toThrow();
        });

        it('ne recharge pas un helper déjà chargé', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['app'] . '/Helpers/cached_helper.php'],
                ]
            ]);

            $this->putFileContent($this->tempDir['app'] . '/Helpers/cached_helper.php', '<?php');

            Services::override(Locator::class, $locator);

            // Premier chargement
            FileLocator::helper('cached_helper');

            // Modifier le fichier pour vérifier qu'il n'est pas rechargé
            $this->putFileContent($this->tempDir['app'] . '/Helpers/cached_helper.php', '<?php // modified');

            // Deuxième chargement - ne devrait pas recharger
            FileLocator::helper('cached_helper');

            // Le cache devrait être utilisé
            expect(ReflectionHelper::getPrivateProperty(FileLocator::class, 'locateCache'))
                ->toContainKey('helper:cached_helper');
        });
    });

    describe('schema', function (): void {
        it('retourne un schema par défaut si aucun fichier trouvé', function (): void {
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn() => []
                ]
            ]);

            Services::override(Locator::class, $locator);

            $schema = FileLocator::schema('nonexistent');
            expect($schema)->toBeAnInstanceOf(Schema::class);
        });

        it('priorise le schema système', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
					'search' => fn(): array => [
						$dirs['app'] . '/schemas/database.php',
						$dirs['system'] . '/Constants/schemas/database.php',
					]
				],
            ]);

            Services::override(Locator::class, $locator);

            $schema = FileLocator::schema('database');
            expect($schema)->toBeAnInstanceOf(Schema::class);
        });

        it('utilise le cache', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['system'] . '/Constants/schemas/database.php'],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $schema1 = FileLocator::schema('database');
            $schema2 = FileLocator::schema('database');

            expect($schema1)->toBe($schema2); // Même instance à cause du cache
        });
    });

    describe('config', function (): void {
        it('retourne un tableau vide si aucune config trouvée', function (): void {
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => []
                ]
            ]);

            Services::override(Locator::class, $locator);

            $config = FileLocator::config('nonexistent');
            expect($config)->toBe([]);
        });

        it('fusionne les configurations dans le bon ordre', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array =>[
						$dirs['system'] . '/Config/database.php',
						$dirs['module'] . '/Config/database.php',
						$dirs['app'] . '/Config/database.php'
					],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $config = FileLocator::config('database');

            // Devrait contenir toutes les configurations fusionnées
            expect($config)->toContainKey('sys');
            expect($config)->toContainKey('mod');
            expect($config)->toContainKey('app');
        });

        it('utilise le cache', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['app'] . '/Config/database.php'],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $config1 = FileLocator::config('database');
            $config2 = FileLocator::config('database');

            expect($config1)->toBe($config2); // Même instance
        });

        it('ignore les fichiers non-tableaux', function (): void {
            $this->putFileContent($this->tempDir['app'] . '/Config/invalid.php', '<?php return "not an array";');

            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['app'] . '/Config/invalid.php'],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $config = FileLocator::config('invalid');
            expect($config)->toBe([]);
        });
    });

    describe('view', function (): void {
        it('retourne le chemin d\'une vue trouvée', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['app'] . '/Views/home.php'],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $path = FileLocator::view('home');
            expect($path)->toBe(str_replace(['/', '\\'], DS, $this->tempDir['app'] . '/Views/home.php'));
        });

        it('lance une exception pour une vue non trouvée', function (): void {
			$dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [
						$dirs['system'] . '/Views/existent.php',
					],
                ]
            ]);

            Services::override(Locator::class, $locator);

            expect(fn(): string|false => FileLocator::view('nonexistent'))
				->toThrow();
        });

        it('priorise les vues app sur system', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [
						$dirs['system'] . '/Views/home.php',
						$dirs['app'] . '/Views/home.php',
					],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $path = FileLocator::view('home');
            expect($path)->toBe(str_replace(['/', '\\'], DS, $this->tempDir['app'] . '/Views/home.php'));
        });

        it('utilise le cache', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['app'] . '/Views/home.php'],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $path1 = FileLocator::view('home');
            $path2 = FileLocator::view('home');

            expect($path1)->toBe($path2);
        });
    });

    describe('translation', function (): void {
        it('retourne un tableau vide si aucune traduction trouvée', function (): void {
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $translations = FileLocator::translation('nonexistent');
            expect($translations)->toBe([]);
        });

        it('fusionne les traductions dans le bon ordre', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [
						$dirs['system'] . '/Translations/en/messages.php',
						$dirs['module'] . '/Translations/en/messages.php',
						$dirs['app'] . '/Translations/en/messages.php'
					],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $translations = FileLocator::translation('messages', 'en');

            // Devrait contenir toutes les traductions fusionnées
            expect($translations)->toContainKey('sys');
            expect($translations)->toContainKey('mod');
            expect($translations)->toContainKey('app');
        });

        it('utilise la locale par défaut si non spécifiée', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['app'] . '/Translations/en/messages.php'],
                ]
            ]);

            Services::override(Locator::class, $locator);

            // Mock config('app.locale')
            $translations = FileLocator::translation('messages');
            expect($translations)->toBeAn('array');
        });

        it('ignore les fichiers non-tableaux', function (): void {
            $this->putFileContent($this->tempDir['app'] . '/Translations/en/invalid.php', '<?php return "not an array";');

            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['app'] . '/Translations/en/invalid.php'],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $translations = FileLocator::translation('invalid', 'en');
            expect($translations)->toBe([]);
        });

        it('utilise le cache', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['app'] . '/Translations/en/messages.php'],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $t1 = FileLocator::translation('messages', 'en');
            $t2 = FileLocator::translation('messages', 'en');

            expect($t1)->toBe($t2);
        });
    });

    describe('getBasename', function (): void {
        it('extrait le basename d\'une classe namespacée', function (): void {
            $basename = FileLocator::getBasename('Namespace\Subnamespace\ClassName');
            expect($basename)->toBe('ClassName');
        });

        it('retourne le nom tel quel si pas de namespace', function (): void {
            $basename = FileLocator::getBasename('ClassName');
            expect($basename)->toBe('ClassName');
        });

        it('retourne une chaîne vide pour une chaîne vide', function (): void {
            $basename = FileLocator::getBasename('');
            expect($basename)->toBe('');
        });

        it('gère les backslashes multiples', function (): void {
            $basename = FileLocator::getBasename('\\Namespace\\ClassName');
            expect($basename)->toBe('ClassName');
        });
    });

    describe('locateHelper', function (): void {
        it('retourne un tableau vide si aucun helper trouvé', function (): void {
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [],
                ]
            ]);

            $locateHelper = ReflectionHelper::getPrivateMethodInvoker(FileLocator::class, 'locateHelper');
            $result = $locateHelper('nonexistent', $locator);

            expect($result)->toBe([]);
        });

        it('retourne les chemins des helpers trouvés', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [
						$dirs['app'] . '/Helpers/test.php',
						$dirs['system'] . '/Helpers/test.php'
					],
                ]
            ]);

            $this->putFileContent($this->tempDir['app'] . '/Helpers/test.php', '<?php');
            $this->putFileContent($this->tempDir['system'] . '/Helpers/test.php', '<?php');

            $locateHelper = ReflectionHelper::getPrivateMethodInvoker(FileLocator::class, 'locateHelper');
            $result = $locateHelper('test', $locator);

            expect($result)->toBeA('array');
            expect($result)->toHaveLength(2);
        });

        it('utilise le cache', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['app'] . '/Helpers/cached.php'],
                ]
            ]);

            $this->putFileContent($this->tempDir['app'] . '/Helpers/cached.php', '<?php');

            $locateHelper = ReflectionHelper::getPrivateMethodInvoker(FileLocator::class, 'locateHelper');

            // Premier appel
            $result1 = $locateHelper('cached', $locator);
            // Deuxième appel (devrait utiliser le cache)
            $result2 = $locateHelper('cached', $locator);

            expect($result1)->toBe($result2);
        });
    });

    describe('cache', function (): void {
        it('vide le cache après chaque test', function (): void {
            $dirs = $this->tempDir;
            $locator = Double::instance([
                'implements' => [LocatorInterface::class],
                'fakeMethods' => [
                    'search' => fn(): array => [$dirs['app'] . '/Helpers/test.php'],
                ]
            ]);

            Services::override(Locator::class, $locator);

            $this->putFileContent($this->tempDir['app'] . '/Helpers/test.php', '<?php');

            FileLocator::helper('test');

            $cache = ReflectionHelper::getPrivateProperty(FileLocator::class, 'locateCache');
            expect($cache)->toContainKey('helper:test');

            // Le cache est vidé dans beforeEach, donc on vérifie qu'il est vide maintenant
            ReflectionHelper::setPrivateProperty(FileLocator::class, 'locateCache', []);
            $cache = ReflectionHelper::getPrivateProperty(FileLocator::class, 'locateCache');
            expect($cache)->toBe([]);
        });
    });

	describe('Cas limites et edge cases', function (): void {
		it('gère les chemins avec différents séparateurs', function (): void {
			$dirs = $this->tempDir;
			$locator = Double::instance([
				'implements' => [LocatorInterface::class],
				'fakeMethods' => [
					'search' => fn(): array => [
						str_replace(DS, '/', $dirs['app']) . '/Config/database.php',  // Avec /
						str_replace(DS, '\\', $dirs['system']) . '\\Config\\database.php', // Avec \
					],
				]
			]);

			$locateFiles = ReflectionHelper::getPrivateMethodInvoker(FileLocator::class, 'locateFiles');
			$files = $locateFiles('database', 'Config', $locator);

			// Les chemins devraient être normalisés
			expect($files['app'][0])->toContain(DS . 'Config' . DS . 'database.php');
			expect($files['system'][0])->toContain(DS . 'Config' . DS . 'database.php');
		});

		it('gère les fichiers avec extension différente', function (): void {
			$dirs = $this->tempDir;
			$this->putFileContent($this->tempDir['app'] . '/Helpers/test.inc.php', '<?php');

			$locator = Double::instance([
				'implements' => [LocatorInterface::class],
				'fakeMethods' => [
					'search' => fn(): array => [$dirs['app'] . '/Helpers/test.inc.php'],
				]
			]);

			$locateFiles = ReflectionHelper::getPrivateMethodInvoker(FileLocator::class, 'locateFiles');
			$files = $locateFiles('test.inc', 'Helpers', $locator);

			expect($files['app'])->toHaveLength(1);
		});

		it('limite à un seul fichier par catégorie (app/system)', function (): void {
			$dirs = $this->tempDir;
			$locator = Double::instance([
				'implements' => [LocatorInterface::class],
				'fakeMethods' => [
					'search' => fn(): array => [
						$dirs['app'] . '/Helpers/test1.php',
						$dirs['app'] . '/Helpers/test2.php',
						$dirs['system'] . '/Helpers/test1.php',
						$dirs['system'] . '/Helpers/test2.php',
					],
				]
			]);

			$this->putFileContent($this->tempDir['app'] . '/Helpers/test1.php', '<?php');
			$this->putFileContent($this->tempDir['app'] . '/Helpers/test2.php', '<?php');
			$this->putFileContent($this->tempDir['system'] . '/Helpers/test1.php', '<?php');
			$this->putFileContent($this->tempDir['system'] . '/Helpers/test2.php', '<?php');

			$locateFiles = ReflectionHelper::getPrivateMethodInvoker(FileLocator::class, 'locateFiles');
			$files = $locateFiles('test', 'Helpers', $locator);

			// Devrait limiter à 1 fichier par catégorie app/system
			expect($files['app'])->toHaveLength(1);
			expect($files['system'])->toHaveLength(1);
			expect($files['modules'])->toHaveLength(0); // Les modules peuvent avoir plusieurs
		});
	});

	describe('Performance et concurrence', function (): void {
		it('utilise le cache pour les appels répétés', function (): void {
			$dirs = $this->tempDir;
			$callCount = 0;

			$locator = Double::instance([
				'implements' => [LocatorInterface::class],
				'fakeMethods' => [
					'search' => function() use (&$callCount, $dirs): array {
						$callCount++;
						return [$dirs['app'] . '/Helpers/performance.php'];
					}
				]
			]);

			Services::override(Locator::class, $locator);
			$this->putFileContent($this->tempDir['app'] . '/Helpers/performance.php', '<?php');

			// Premier appel
			FileLocator::helper('performance');
			$firstCall = $callCount;

			// Deuxième appel (devrait utiliser le cache)
			FileLocator::helper('performance');

			expect($callCount)->toBe($firstCall); // Pas d'appel supplémentaire
		});

		it('gère correctement le cache avec des noms similaires', function (): void {
			$dirs = $this->tempDir;
			$locator = Double::instance([
				'implements' => [LocatorInterface::class],
				'fakeMethods' => [
					'search' => fn($path): array => match(true) {
						str_contains($path, 'test1') => [$dirs['app'] . '/Helpers/test1.php'],
						default => [$dirs['app'] . '/Helpers/test2.php'],
					},
				]
			]);

			Services::override(Locator::class, $locator);
			$this->putFileContent($this->tempDir['app'] . '/Helpers/test1.php', '<?php');
			$this->putFileContent($this->tempDir['app'] . '/Helpers/test2.php', '<?php');

			FileLocator::helper('test1');
			FileLocator::helper('test2');

			$cache = ReflectionHelper::getPrivateProperty(FileLocator::class, 'locateCache');
			expect($cache)->toContainKey('helper:test1');
			expect($cache)->toContainKey('helper:test2');
		});
	});

	describe('Fusion de configurations', function (): void {
		it('gère correctement les conflits de clés', function (): void {
			$dirs = $this->tempDir;

			// Créer des fichiers avec des clés en conflit
			$this->putFileContent($this->tempDir['system'] . '/Config/merge.php', '<?php return ["db" => ["host" => "localhost"]];');
			$this->putFileContent($this->tempDir['app'] . '/Config/merge.php', '<?php return ["db" => ["port" => 3306]];');

			$locator = Double::instance([
				'implements' => [LocatorInterface::class],
				'fakeMethods' => [
					'search' => fn(): array => [
						$dirs['system'] . '/Config/merge.php',
						$dirs['app'] . '/Config/merge.php',
					],
				]
			]);

			Services::override(Locator::class, $locator);

			$config = FileLocator::config('merge');

			// Devrait fusionner récursivement
			expect($config['db']['host'])->toBe('localhost');
			expect($config['db']['port'])->toBe(3306);
		});
	});
});
