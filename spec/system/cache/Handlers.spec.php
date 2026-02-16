<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */


use BlitzPHP\Cache\Handlers\Apcu;
use BlitzPHP\Cache\Handlers\ArrayHandler;
use BlitzPHP\Cache\Handlers\Dummy;
use BlitzPHP\Cache\Handlers\File;
use BlitzPHP\Cache\Handlers\Memcached;
use BlitzPHP\Cache\Handlers\RedisHandler;
use BlitzPHP\Cache\Handlers\Wincache;
use BlitzPHP\Cache\InvalidArgumentException;

use function Kahlan\expect;

describe('Cache / Handlers', function (): void {
   	describe('ArrayHandler', function (): void {
        beforeEach(function (): void {
            $this->handler = new ArrayHandler();
            $this->handler->init(['prefix' => 'test_']);
        });

        it('Stocke et récupère les valeurs correctement', function (): void {
            $this->handler->set('key1', 'value1');
            expect($this->handler->get('key1'))->toBe('value1');
        });

        it('Gère l\'expiration des valeurs', function (): void {
            $this->handler->set('expiring_key', 'value', 1); // 1 seconde
            expect($this->handler->get('expiring_key'))->toBe('value');

            // Attendre que la valeur expire
            sleep(2);
            expect($this->handler->get('expiring_key'))->toBeNull();
        });

        it('Supporte l\'incrémentation et la décrémentation', function (): void {
            $this->handler->set('counter', 10);

            expect($this->handler->increment('counter', 5))->toBe(15);
            expect($this->handler->decrement('counter', 3))->toBe(12);
        });

        it('Initialise à 0 pour les opérations sur les clés non existantes', function (): void {
            expect($this->handler->increment('new_counter'))->toBe(1);
            expect($this->handler->decrement('another_counter'))->toBe(0);
        });
    });

    describe('File Handler', function (): void {
        beforeEach(function (): void {
            $this->tempDir = sys_get_temp_dir() . '/blitz_cache_test';
            $this->handler = new File();
            $this->handler->init([
                'path' => $this->tempDir,
                'prefix' => 'test_',
                'serialize' => true
            ]);
        });

        afterEach(function (): void {
            // Nettoyer les fichiers de test
            if (is_dir($this->tempDir)) {
                array_map(unlink(...), glob($this->tempDir . '/*'));
            }
        });

        afterAll(function (): void {
            // Nettoyer les fichiers de test
            if (is_dir($this->tempDir)) {
                rmdir($this->tempDir);
            }
        });

        it('Stocke et récupère les valeurs avec sérialisation', function (): void {
            $data = ['array' => 'data', 'number' => 123];
            $this->handler->set('complex_data', $data);

            $result = $this->handler->get('complex_data');
            expect($result)->toBe($data);
        });

        it('Gère l\'expiration des fichiers', function (): void {
            $this->handler->set('temp_data', 'value', 1);
            expect($this->handler->get('temp_data'))->toBe('value');

            sleep(2);
            expect($this->handler->get('temp_data'))->toBeNull();
        });

        it('Lève une exception pour les caractères invalides dans les clés', function (): void {
            expect(fn() => $this->handler->set('invalid/key', 'value'))
                ->toThrow(new InvalidArgumentException());
        });

        it('Peut vider tout le cache', function (): void {
            $this->handler->set('key1', 'val1');
            $this->handler->set('key2', 'val2');

            $result = $this->handler->clear();
            expect($result)->toBeTruthy();

            expect($this->handler->get('key1'))->toBeNull();
            expect($this->handler->get('key2'))->toBeNull();
        });
    });

    describe('Dummy Handler', function (): void {
        beforeEach(function (): void {
            $this->handler = new Dummy();
            $this->handler->init();
        });

        it('Retourne toujours true pour les opérations d\'écriture', function (): void {
            expect($this->handler->set('key', 'value'))->toBeTruthy();
            expect($this->handler->setMultiple(['key' => 'value']))->toBeTruthy();
        });

        it('Retourne toujours la valeur par défaut pour les lectures', function (): void {
            expect($this->handler->get('any_key', 'default'))->toBe('default');
            expect($this->handler->getMultiple(['key1', 'key2'], 'default'))->toBe([
				'key1' => 'default',
				'key2' => 'default'
			]);
        });

        it('Retourne des valeurs prédéfinies pour incrément/décrément', function (): void {
            expect($this->handler->increment('counter'))->toBe(1);
            expect($this->handler->decrement('counter'))->toBe(0);
        });

        it('Retourne toujours true pour les suppressions', function (): void {
            expect($this->handler->delete('key'))->toBeTruthy();
            expect($this->handler->deleteMultiple(['key1', 'key2']))->toBeTruthy();
            expect($this->handler->clear())->toBeTruthy();
        });
    });

    describe('Redis Handler', function (): void {
        it('Lève une exception si l\'extension redis n\'est pas chargée', function (): void {
            if (extension_loaded('redis')) {
                return; // Skip test if Redis is available
            }

            $handler = new RedisHandler();
            expect(fn(): bool => $handler->init())->toThrow(new RuntimeException());
        });
    });

    describe('APCu Handler', function (): void {
        it('Lève une exception si l\'extension apcu n\'est pas chargée', function (): void {
            if (extension_loaded('apcu')) {
                return; // Skip test if APCu is available
            }

            $handler = new Apcu();
            expect(fn(): bool => $handler->init())->toThrow(new RuntimeException());
        });
    });

    describe('Memcached Handler', function (): void {
        it('Lève une exception si l\'extension memcached n\'est pas chargée', function (): void {
            if (extension_loaded('memcached')) {
                return; // Skip test if Memcached is available
            }

            $handler = new Memcached();
            expect(fn(): bool => $handler->init())->toThrow(new RuntimeException());
        });
    });

    describe('Wincache Handler', function (): void {
        it('Lève une exception si l\'extension wincache n\'est pas chargée', function (): void {
            if (extension_loaded('wincache')) {
                return; // Skip test if Wincache is available
            }

            $handler = new Wincache();
            expect(fn(): bool => $handler->init())->toThrow(new RuntimeException());
        });
    });
});
