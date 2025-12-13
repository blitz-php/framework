<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Exceptions\LoadException;
use BlitzPHP\Exceptions\ViewException;
use BlitzPHP\Loader\FileLocator;
use BlitzPHP\Loader\Load;
use BlitzPHP\Spec\ReflectionHelper;

use function Kahlan\expect;

describe('Loader / Load', function () {
    beforeAll(function () {
        $this->originalLoaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
    });

    beforeEach(function () {
        ReflectionHelper::setPrivateProperty(Load::class, 'loaded', []);
    });

    afterAll(function () {
        ReflectionHelper::setPrivateProperty(Load::class, 'loaded', $this->originalLoaded);
    });

    describe('helper', function () {
        it('charge un helper simple', function () {
            expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded'))->toBe([]);
			// expect(function_exists('scl_cleaner'))->toBeFalsy();

            Load::helper('scl');

            expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded')['helper']['scl'])->toBeTruthy();
			expect(function_exists('scl_cleaner'))->toBeTruthy();
        });

        it('charge plusieurs helpers sous forme de tableau', function () {
            expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded'))->toBe([]);
			expect(function_exists('now'))->toBeFalsy();

            Load::helper(['assets', 'date']);

            expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded')['helper'])->toContainKeys(['assets', 'date']);
			expect(function_exists('css_url'))->toBeTruthy();
			expect(function_exists('now'))->toBeTruthy();
        });

        it('ignore les espaces superflus', function () {
            expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded'))->toBe([]);
			// expect(function_exists('camelize'))->toBeFalsy();

            Load::helper('	inflector     ');

            expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded')['helper']['inflector'])->toBeTruthy();
			expect(function_exists('camelize'))->toBeTruthy();
        });

        it('lance une exception pour des elements vide', function () {
            expect(fn() => Load::helper(''))
				->toThrow(new LoadException('Veuillez spécifier le helper à charger.'));

			expect(fn() => Load::helper('0'))
				->toThrow(new LoadException('Veuillez spécifier le helper à charger.'));

			expect(fn() => Load::helper([]))
				->toThrow(new LoadException('Veuillez spécifier le helper à charger.'));
        });

        it('ignore les noms de helpers vides dans la liste', function () {
            expect(fn() => Load::helper(['scl', '', ' ', 'date']))->not->toThrow();

			expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded')['helper'])->toContainKeys(['scl', 'date']);
        });

        it('propage les exceptions de FileLocator', function () {
            expect(fn() => Load::helper('nonexistent'))
				->toThrow(LoadException::helperNotFound('nonexistent'));
        });
    });

    describe('config', function () {
        it('charge et retourne une configuration', function () {
            $config = Load::config('app');

            expect($config)->toContainKeys(['base_url', 'environment']);
            expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded')['config']['app'])->toBe($config);
        });

        it('utilise le cache pour les configurations déjà chargées', function () {
           $config1 = Load::config('app');

            expect($config1)->toContainKeys(['base_url', 'environment']);
            expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded')['config']['app'])->toBe($config1);

			// on modifie le fichier mis en cache pour pourvoir tester aisement
			ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
				'config' => [
					'app' => $config1 = ['key' => 'value'],
				]
			]);

            $config2 = Load::config('app'); // Deuxième appel

            expect($config1)->toBe($config2);
        });

        it('charge différentes configurations séparément', function () {
            $config1 = Load::config('app');
            $config2 = Load::config('cache');

            expect($config1)->toContainKeys(['base_url', 'environment']);
            expect($config2)->toContainKeys(['handler' => 'prefix']);
            expect($config1)->not->toBe($config2);
        });

        it('retourne un tableau vide si FileLocator retourne un tableau vide', function () {
            $config = Load::config('nonexistent');

            expect($config)->toBe([]);
        });
    });

    describe('view', function () {
        it('retourne le chemin d\'une vue', function () {
            $path = Load::view('simple');

			expect($path)->toBe($expected = view_path('simple.php'));
            expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded')['view']['simple'])->toBe($expected);
        });

        it('utilise le cache pour les vues déjà chargées', function () {
            $path1 = Load::view('simple');

			expect($path1)->toBe($expected = view_path('simple.php'));
            expect(ReflectionHelper::getPrivateProperty(Load::class, 'loaded')['view']['simple'])->toBe($expected);

			// on modifie le fichier mis en cache pour pourvoir tester aisement
			ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
				'view' => [
					'simple' => $path1 = '/path/to/file.php',
				]
			]);

            $path2 = Load::view('simple'); // Deuxième appel

            expect($path1)->toBe($path2);
        });

        it('lance une exception si la vue n\'est pas trouvée', function () {
            expect(fn() => Load::view('nonexistent'))
				->toThrow(ViewException::invalidFile('nonexistent'));
        });
    });

    describe('unload', function () {
        it('décharge un élément spécifique d\'un module', function () {
            Load::helper('scl');
            Load::config('app');
            Load::view('simple');

            $loadedBefore = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
            expect($loadedBefore['helper'])->toContainKey('scl');
            expect($loadedBefore['config'])->toContainKey('app');
            expect($loadedBefore['view'])->toContainKey('simple');

            // Décharger un élément
            Load::unload('helper', 'scl');

            $loadedAfter = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
            expect($loadedAfter['helper'])->not->toContainKey('scl');
            expect($loadedAfter['config'])->toContainKey('app'); // Toujours présent
            expect($loadedAfter['view'])->toContainKey('simple'); // Toujours présent
        });

        it('décharge un élément objet en utilisant le nom de classe', function () {
            $object = new class {
                public function test() {}
            };

            // Simuler le chargement
            ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                'models' => [
                    get_class($object) => $object
                ]
            ]);

            Load::unload('models', $object);

            $loaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
            expect($loaded['models'])->not->toContainKey(get_class($object));
        });

        it('ne lance pas d\'exception si le module n\'existe pas', function () {
            expect(fn() => Load::unload('nonexistent', 'element'))->not->toThrow();
        });

        it('ne lance pas d\'exception si l\'élément n\'existe pas', function () {
            // Créer un module avec un élément
            ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                'module' => ['element1' => 'value1']
            ]);

            expect(fn()  => Load::unload('module', 'nonexistent'))->not->toThrow();

            // Vérifier que l'élément existant est toujours là
            $loaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
            expect($loaded['module'])->toContainKey('element1');
        });
    });

    describe('unloadAll', function () {
        it('décharge tous les éléments d\'un module spécifique', function () {
            Load::helper(['scl', 'url', 'assets']);
            Load::config('app');

            $loadedBefore = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
            expect($loadedBefore['helper'])->toHaveLength(3);
            expect($loadedBefore['config'])->toHaveLength(1);

            // Décharger seulement le module helper
            Load::unloadAll('helper');

            $loadedAfter = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
            expect($loadedAfter['helper'])->toBe([]);
            expect($loadedAfter['config'])->toHaveLength(1); // Toujours présent
        });

        it('décharge tous les modules si aucun module spécifié', function () {
            Load::helper('scl');
            Load::config('app');
            Load::view('simple');

            $loadedBefore = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
            expect($loadedBefore)->toContainKey('helper');
            expect($loadedBefore)->toContainKey('config');
            expect($loadedBefore)->toContainKey('view');

            // Décharger tout
            Load::unloadAll();

            $loadedAfter = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
            expect($loadedAfter)->toBe([]);
        });

        it('gère les modules vides', function () {
            // Créer un module vide
            ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                'empty_module' => []
            ]);

            expect(fn() => Load::unloadAll('empty_module'))->not->toThrow();

            $loaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
            expect($loaded['empty_module'])->toBe([]);
        });

        it('ne crée pas de module si on tente de décharger un module inexistant', function () {
            ReflectionHelper::setPrivateProperty(Load::class, 'loaded', []);

            Load::unloadAll('nonexistent');

            $loaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
            expect($loaded)->not->toContainKey('nonexistent');
        });
    });

    describe('méthodes protégées', function () {
        describe('isLoaded', function () {
            it('retourne true si un élément est chargé', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                    'module' => ['element' => 'value']
                ]);

                $isLoaded = ReflectionHelper::getPrivateMethodInvoker(Load::class, 'isLoaded')('module', 'element');
                expect($isLoaded)->toBe(true);
            });

            it('retourne false si un élément n\'est pas chargé', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                    'module' => ['element' => 'value']
                ]);

                $isLoaded = ReflectionHelper::getPrivateMethodInvoker(Load::class, 'isLoaded')('module', 'nonexistent');
                expect($isLoaded)->toBe(false);
            });

            it('retourne false si le module n\'existe pas', function () {
                $isLoaded = ReflectionHelper::getPrivateMethodInvoker(Load::class, 'isLoaded')('nonexistent', 'element');
                expect($isLoaded)->toBe(false);
            });

            it('retourne false si le module n\'est pas un tableau', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                    'module' => 'not_an_array'
                ]);

                $isLoaded = ReflectionHelper::getPrivateMethodInvoker(Load::class, 'isLoaded')('module', 'element');
                expect($isLoaded)->toBe(false);
            });

            it('gère les objets en utilisant le nom de classe comme clé', function () {
                $object = new class {
                    public function test() {}
                };

                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                    'models' => [get_class($object) => $object]
                ]);

                $isLoaded = ReflectionHelper::getPrivateMethodInvoker(Load::class, 'isLoaded')('models', $object);
                expect($isLoaded)->toBe(true);
            });
        });

        describe('loaded', function () {
            it('ajoute un élément au cache', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', []);

                ReflectionHelper::getPrivateMethodInvoker(Load::class, 'loaded')('module', 'element', 'value');

                $loaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
                expect($loaded['module']['element'])->toBe('value');
            });

            it('crée le module s\'il n\'existe pas', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', []);

                ReflectionHelper::getPrivateMethodInvoker(Load::class, 'loaded')('new_module', 'element', 'value');

                $loaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
                expect($loaded)->toContainKey('new_module');
            });

            it('écrase une valeur existante', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                    'module' => ['element' => 'old_value']
                ]);

                ReflectionHelper::getPrivateMethodInvoker(Load::class, 'loaded')('module', 'element', 'new_value');

                $loaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
                expect($loaded['module']['element'])->toBe('new_value');
            });

            it('gère les objets en utilisant le nom de classe comme clé', function () {
                $object = new FileLocator();

                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', []);

                ReflectionHelper::getPrivateMethodInvoker(Load::class, 'loaded')('models', $object, 'test');

                $loaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
                expect($loaded['models'][get_class($object)])->toBe('test');
            });

            it('accepte différents types de valeurs', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', []);

                ReflectionHelper::getPrivateMethodInvoker(Load::class, 'loaded')('module', 'bool', true);
                ReflectionHelper::getPrivateMethodInvoker(Load::class, 'loaded')('module', 'array', ['key' => 'value']);
                ReflectionHelper::getPrivateMethodInvoker(Load::class, 'loaded')('module', 'string', 'value');

                $loaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
                expect($loaded['module']['bool'])->toBe(true);
                expect($loaded['module']['array'])->toBe(['key' => 'value']);
                expect($loaded['module']['string'])->toBe('value');
            });
        });

        describe('getLoaded', function () {
            it('retourne la valeur d\'un élément chargé', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                    'module' => ['element' => 'value']
                ]);

                $value = ReflectionHelper::getPrivateMethodInvoker(Load::class, 'getLoaded')('module', 'element');
                expect($value)->toBe('value');
            });

            it('retourne null si l\'élément n\'existe pas', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                    'module' => ['element' => 'value']
                ]);

                $value = ReflectionHelper::getPrivateMethodInvoker(Load::class, 'getLoaded')('module', 'nonexistent');
                expect($value)->toBeNull();
            });

            it('crée le module s\'il n\'existe pas et retourne null', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', []);

                $value = ReflectionHelper::getPrivateMethodInvoker(Load::class, 'getLoaded')('new_module', 'element');

                expect($value)->toBeNull();
                $loaded = ReflectionHelper::getPrivateProperty(Load::class, 'loaded');
                expect($loaded)->toContainKey('new_module');
            });

            it('gère les objets en utilisant le nom de classe comme clé', function () {
                $object = new class {
                    public function test() {}
                };

                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                    'models' => [get_class($object) => $object]
                ]);

                $value = ReflectionHelper::getPrivateMethodInvoker(Load::class, 'getLoaded')('models', $object);
                expect($value)->toBe($object);
            });

            it('retourne différents types de valeurs', function () {
                ReflectionHelper::setPrivateProperty(Load::class, 'loaded', [
                    'module' => [
                        'bool' => true,
                        'array' => ['key' => 'value'],
                        'string' => 'text',
                        'null' => null
                    ]
                ]);

                expect(ReflectionHelper::getPrivateMethodInvoker(Load::class, 'getLoaded')('module', 'bool'))->toBe(true);
                expect(ReflectionHelper::getPrivateMethodInvoker(Load::class, 'getLoaded')('module', 'array'))->toBe(['key' => 'value']);
                expect(ReflectionHelper::getPrivateMethodInvoker(Load::class, 'getLoaded')('module', 'string'))->toBe('text');
                expect(ReflectionHelper::getPrivateMethodInvoker(Load::class, 'getLoaded')('module', 'null'))->toBeNull();
            });
        });
    });
});
