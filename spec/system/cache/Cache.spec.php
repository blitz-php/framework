<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Cache\Cache;
use BlitzPHP\Cache\Handlers\Dummy;
use BlitzPHP\Cache\InvalidArgumentException;
use BlitzPHP\Spec\ReflectionHelper;

use function Kahlan\expect;

describe('Cache / Cache Factory', function (): void {

    describe('Erreur de gestionnaire de cache', function (): void {
        it('Renvoie Dummy lorsque le cache est désactivé', function (): void {
            $cache = new Cache();
            Cache::disable();

            $factory = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');

            expect(call_user_func($factory))->toBeAnInstanceOf(Dummy::class);

            Cache::enable();
        });

        it('Leve une exception lorsque les gestionnaires valides ne sont pas definis', function (): void {
            $cache = new Cache();

            expect(ReflectionHelper::getPrivateProperty($cache, 'config'))->toBe([]);

            $factory = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');

            expect(fn() => call_user_func($factory))->toThrow(new InvalidArgumentException());
        });

        it('Leve une exception lorsque le gestionnaire principal n\'est pas defini ou ne fait pas partir des gestionnaires valides', function (): void {
            $config                  = [];
            $config['valid_handlers'] = config('cache.valid_handlers');
            $cache                   = new Cache($config);

            $factory = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');
            expect(fn() => call_user_func($factory))->toThrow(new InvalidArgumentException());


            $config['handler'] = 'fake_handler';
            $cache             = new Cache($config);

            $factory = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');
            expect(fn() => call_user_func($factory))->toThrow(new InvalidArgumentException());
        });

        it('Utilise Dummy si le gestionnaire principal fait partir des gestionnaires valides mais n\'herite pas de BaseHandler', function (): void {
            $config                   = [];
            $config['valid_handlers'] = ['fake_handler' => stdClass::class];
            $config['handler']        = 'fake_handler';
            $cache                    = new Cache($config);

            $factory = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');
            expect(call_user_func($factory))->toBeAnInstanceOf(Dummy::class);
        });
    });

    describe('Configuration et initialisation', function (): void {
        it('Peut modifier la configuration après instanciation', function (): void {
            $cache = new Cache(['handler' => 'array']);
            $newConfig = ['handler' => 'array', 'prefix' => 'new_prefix_'];

            $cache->setConfig($newConfig);

            expect(ReflectionHelper::getPrivateProperty($cache, 'config')['prefix'])->toBe('new_prefix_');
        });

        it('Réinitialise l\'adapter lors du changement de configuration', function (): void {
            $cache = new Cache(['handler' => 'array']);
            $factory1 = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');
            $adapter1 = call_user_func($factory1);

            $cache->setConfig(['handler' => 'array', 'prefix' => 'changed_']);
            $adapter2 = call_user_func($factory1);

            expect($adapter1)->not->toBe($adapter2);
        });
    });

    describe('Opérations de base', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache(['handler' => 'array']);
        });

        it('Peut écrire et lire une valeur', function (): void {
            $result = $this->cache->write('test_key', 'test_value');
            expect($result)->toBeTruthy();

            $value = $this->cache->read('test_key');
            expect($value)->toBe('test_value');
        });

        it('Retourne la valeur par défaut si la clé n\'existe pas', function (): void {
            $value = $this->cache->read('nonexistent_key', 'default_value');
            expect($value)->toBe('default_value');
        });

        it('Utilise le callback comme valeur par défaut si fourni', function (): void {
            $value = $this->cache->read('nonexistent_key', function () {
                return 'computed_default';
            });
            expect($value)->toBe('computed_default');
        });

        it('Peut écrire et lire plusieurs valeurs', function (): void {
            $data = [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3'
            ];

            $result = $this->cache->writeMany($data);
            expect($result)->toBeTruthy();

            $values = $this->cache->readMany(['key1', 'key2', 'key3']);
            expect($values)->toBe($data);
        });

        it('Peut supprimer une clé', function (): void {
            $this->cache->write('to_delete', 'value');
            expect($this->cache->read('to_delete'))->toBe('value');

            $result = $this->cache->delete('to_delete');
            expect($result)->toBeTruthy();
            expect($this->cache->read('to_delete'))->toBeNull();
        });

        it('Peut supprimer plusieurs clés', function (): void {
            $this->cache->writeMany(['key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3']);

            $result = $this->cache->deleteMany(['key1', 'key2']);
            expect($result)->toBeTruthy();

            expect($this->cache->read('key1'))->toBeNull();
            expect($this->cache->read('key2'))->toBeNull();
            expect($this->cache->read('key3'))->toBe('val3');
        });

        it('Peut vider tout le cache', function (): void {
            $this->cache->writeMany(['key1' => 'val1', 'key2' => 'val2']);

            $result = $this->cache->clear();
            expect($result)->toBeTruthy();

            expect($this->cache->read('key1'))->toBeNull();
            expect($this->cache->read('key2'))->toBeNull();
        });
    });

    describe('Incrémentation et décrémentation', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache(['handler' => 'array']);
        });

        it('Peut incrémenter une valeur numérique', function (): void {
            $this->cache->write('counter', 5);

            $result = $this->cache->increment('counter', 3);
            expect($result)->toBe(8);

            expect($this->cache->read('counter'))->toBe(8);
        });

        it('Peut décrémenter une valeur numérique', function (): void {
            $this->cache->write('counter', 10);

            $result = $this->cache->decrement('counter', 4);
            expect($result)->toBe(6);

            expect($this->cache->read('counter'))->toBe(6);
        });

        it('Initialise à 0 avant d\'incrémenter si la clé n\'existe pas', function (): void {
            $result = $this->cache->increment('new_counter');
            expect($result)->toBe(1);
        });

        it('Lève une exception si l\'offset est négatif pour l\'incrémentation', function (): void {
            expect(fn() => $this->cache->increment('key', -1))
                ->toThrow(new InvalidArgumentException('Le décalage ne peut pas être inférieur à 0.'));
        });

        it('Lève une exception si l\'offset est négatif pour la décrémentation', function (): void {
            expect(fn() => $this->cache->decrement('key', -1))
                ->toThrow(new InvalidArgumentException('Le décalage ne peut pas être inférieur à 0.'));
        });
    });

    describe('Méthode remember', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache(['handler' => 'array']);
        });

        it('Retourne la valeur mise en cache si elle existe', function (): void {
            $this->cache->write('cached_key', 'cached_value');

            $result = $this->cache->remember('cached_key', 3600, function () {
                return 'new_value';
            });

            expect($result)->toBe('cached_value');
        });

        it('Exécute le callback et met en cache le résultat si la clé n\'existe pas', function (): void {
            $executionCount = 0;

            $result = $this->cache->remember('new_key', 3600, function () use (&$executionCount) {
                $executionCount++;
                return 'computed_value';
            });

            expect($result)->toBe('computed_value');
            expect($executionCount)->toBe(1);

            // Vérifie que la valeur a bien été mise en cache
            $cachedValue = $this->cache->read('new_key');
            expect($cachedValue)->toBe('computed_value');
        });

        it('Supporte la syntaxe avec TTL en premier paramètre', function (): void {
            $result = $this->cache->remember('key_with_ttl', function () {
                return 'value_with_ttl';
            });

            expect($result)->toBe('value_with_ttl');
        });
    });

    describe('Méthode add', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache(['handler' => 'array']);
        });

        it('Ajoute une valeur seulement si la clé n\'existe pas', function (): void {
            $result1 = $this->cache->add('unique_key', 'first_value');
            expect($result1)->toBeTruthy();

            $result2 = $this->cache->add('unique_key', 'second_value');
            expect($result2)->toBeFalsy();

            expect($this->cache->read('unique_key'))->toBe('first_value');
        });

        it('Ne peut pas ajouter une ressource', function (): void {
            $resource = fopen('php://memory', 'r');

            $result = $this->cache->add('resource_key', $resource);
            expect($result)->toBeFalsy();

            fclose($resource);
        });
    });

    describe('Méthode has', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache(['handler' => 'array']);
        });

        it('Retourne true si la clé existe', function (): void {
            $this->cache->write('existing_key', 'value');

            expect($this->cache->has('existing_key'))->toBeTruthy();
        });

        it('Retourne false si la clé n\'existe pas', function (): void {
            expect($this->cache->has('nonexistent_key'))->toBeFalsy();
        });
    });

    describe('Méthodes pull', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache(['handler' => 'array']);
        });

        it('Récupère et supprime une clé', function (): void {
            $this->cache->write('pull_key', 'pull_value');

            $value = $this->cache->pull('pull_key');
            expect($value)->toBe('pull_value');

            expect($this->cache->has('pull_key'))->toBeFalsy();
        });

        it('Récupère et supprime plusieurs clés', function (): void {
            $this->cache->writeMany(['pull1' => 'val1', 'pull2' => 'val2']);

            $values = $this->cache->pullMany(['pull1', 'pull2']);
            expect($values)->toBe(['pull1' => 'val1', 'pull2' => 'val2']);

            expect($this->cache->has('pull1'))->toBeFalsy();
            expect($this->cache->has('pull2'))->toBeFalsy();
        });
    });

    describe('Gestion des groupes', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache([
                'handler' => 'array',
                'groups' => ['users', 'products']
            ]);
        });

        it('Peut vider un groupe spécifique', function (): void {
            $this->cache->write('user_1', 'user_data');
            $this->cache->write('product_1', 'product_data');

            $result = $this->cache->clearGroup('users');
            expect($result)->toBeTruthy();
        });
    });

    describe('Gestion du statut enabled/disabled', function (): void {
        it('Peut activer et désactiver le cache globalement', function (): void {
            Cache::disable();
            expect(Cache::enabled())->toBeFalsy();

            Cache::enable();
            expect(Cache::enabled())->toBeTruthy();
        });

        it('Utilise Dummy lorsque le cache est désactivé', function (): void {
            Cache::disable();

            $cache = new Cache(['handler' => 'array']);
            $cache->write('test_key', 'test_value');

            // Avec cache désactivé, la lecture devrait retourner la valeur par défaut
            $value = $cache->read('test_key');
            expect($value)->toBeNull();

            Cache::enable();
        });
    });

    describe('Gestion des TTL', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache(['handler' => 'array']);
        });

        it('Supporte les TTL en secondes', function (): void {
            $result = $this->cache->write('ttl_key', 'ttl_value', 60);
            expect($result)->toBeTruthy();
        });

        it('Utilise la durée par défaut si aucun TTL n\'est spécifié', function (): void {
            $result = $this->cache->write('default_ttl_key', 'value');
            expect($result)->toBeTruthy();
        });
    });

    describe('Gestion des exceptions', function (): void {
        it('Lève une exception pour des clés invalides', function (): void {
            $cache = new Cache(['handler' => 'array']);

            expect(fn() => $cache->write('', 'value'))
                ->toThrow(new InvalidArgumentException());

            expect(fn() => $cache->write('key{with}invalid{chars}', 'value'))
                ->toThrow(new InvalidArgumentException());
        });
    });
});

describe('Cache / Edge Cases', function (): void {
    describe('Gestion des ressources', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache(['handler' => 'array']);
        });

        it('Ne peut pas écrire une ressource', function (): void {
            $resource = fopen('php://memory', 'r');

            $result = $this->cache->write('resource_key', $resource);
            expect($result)->toBeFalsy();

            fclose($resource);
        });

        it('Ne peut pas ajouter une ressource', function (): void {
            $resource = fopen('php://memory', 'r');

            $result = $this->cache->add('resource_key', $resource);
            expect($result)->toBeFalsy();

            fclose($resource);
        });
    });

    describe('Gestion des valeurs vides', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache(['handler' => 'array']);
        });

        it('Peut écrire une chaîne vide', function (): void {
            $result = $this->cache->write('empty_string', '');
            expect($result)->toBeTruthy();

            expect($this->cache->read('empty_string'))->toBe('');
        });

        it('Peut écrire la valeur null', function (): void {
            $result = $this->cache->write('null_value', null);
            expect($result)->toBeTruthy();

            expect($this->cache->read('null_value'))->toBeNull();
        });

        it('Peut écrire un tableau vide', function (): void {
            $result = $this->cache->write('empty_array', []);
            expect($result)->toBeTruthy();

            expect($this->cache->read('empty_array'))->toBe([]);
        });
    });

    describe('Gestion des caractères spéciaux', function (): void {
        beforeEach(function (): void {
            $this->cache = new Cache(['handler' => 'array']);
        });

        it('Peut gérer les caractères Unicode', function (): void {
            $unicodeValue = '🎉 Émojis et accents: café, naïve, 中文';

            $result = $this->cache->write('unicode_key', $unicodeValue);
            expect($result)->toBeTruthy();

            expect($this->cache->read('unicode_key'))->toBe($unicodeValue);
        });

        it('Peut gérer les données binaires', function (): void {
            $binaryData = "\x00\x01\x02\x03\x04\x05";

            $result = $this->cache->write('binary_key', $binaryData);
            expect($result)->toBeTruthy();

            expect($this->cache->read('binary_key'))->toBe($binaryData);
        });
    });

    describe('Performance et concurrence', function (): void {
        it('Peut gérer un grand nombre d\'opérations', function (): void {
            $cache = new Cache(['handler' => 'array']);
            $iterations = 1000;

            for ($i = 0; $i < $iterations; $i++) {
                $cache->write("key_{$i}", "value_{$i}");
            }

            for ($i = 0; $i < $iterations; $i++) {
                expect($cache->read("key_{$i}"))->toBe("value_{$i}");
            }

            $result = $cache->clear();
            expect($result)->toBeTruthy();
        });
    });
});
