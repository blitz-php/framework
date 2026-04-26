<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Cache\Handlers\ArrayHandler;
use BlitzPHP\Contracts\Cache\CacheInterface;
use BlitzPHP\Http\Request;
use BlitzPHP\RateLimiter\Limit;
use BlitzPHP\RateLimiter\Throttler;
use BlitzPHP\RateLimiter\Strategies\TokenBucket;
use BlitzPHP\RateLimiter\Strategies\SlidingWindow;
use BlitzPHP\RateLimiter\Strategies\FixedWindow;

use function Kahlan\expect;

describe('RateLimiter / Throttler', function (): void {
    beforeAll(function (): void {
        $this->createCache = function (array $initialData = []): CacheInterface {
			$cache = new ArrayHandler();
			$cache->init(config('cache'));
			$cache->setReservedCharacters('');
			$cache->setMultiple($initialData);

			return $cache;
        };
    });

    describe('->attempt()', function (): void {
        it("devrait exécuter le callback lorsque la limite n'est pas atteinte", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $result = $throttler->attempt('test-key', $maxAttempts = 5, function () {
                return 'success';
            });

            expect($result)->toBe('success');
        });

        it("devrait retourner false lorsque la limite est atteinte", function (): void {
            $cache = $this->createCache([
                'throttler:test-key' => ['tokens' => 0, 'last_update' => microtime(true), 'max_tokens' => 5, 'window' => 60],
                'throttler:test-key:timer' => time() + 60,
            ]);
            $throttler = new Throttler($cache);

            $result = $throttler->attempt('test-key', $maxAttempts = 5, function () {
                return 'success';
            });

            expect($result)->toBeFalsy();
        });

        it("devrait retourner true quand le callback ne retourne rien", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $result = $throttler->attempt('test-key', $maxAttempts = 5, function () {
                // Ne retourne rien
            });

            expect($result)->toBeTruthy();
        });
    });

    describe('->tooManyAttempts()', function (): void {
        it("devrait retourner false quand la limite n'est pas atteinte", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            expect($throttler->tooManyAttempts('test-key', 5))->toBeFalsy();
        });

        it("devrait retourner true quand la limite est atteinte", function (): void {
            $cache = $this->createCache([
                'throttler:test-key' => ['tokens' => 0, 'last_update' => microtime(true), 'max_tokens' => 5, 'window' => 60],
            ]);
            $throttler = new Throttler($cache);

            expect($throttler->tooManyAttempts('test-key', 5, 60))->toBeTruthy();
        });

        it("ne devrait pas incrémenter le compteur lors de la vérification", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->tooManyAttempts('test-key', 5);
            $attempts = $throttler->attempts('test-key');

            expect($attempts)->toBe(0);
        });
    });

    describe('->hit() et ->increment()', function (): void {
        it("devrait incrémenter le compteur de 1 par défaut", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->hit('test-key');
			$attempts = $throttler->attempts('test-key');

            expect($attempts)->toBe(1);
        });

        it("devrait incrémenter le compteur du montant spécifié", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->increment('test-key', 60, 5);
            $attempts = $throttler->attempts('test-key');

            expect($attempts)->toBe(5);
        });

        it("devrait supporter des clés avec des caractères spéciaux", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->hit('send-message:user@example.com');

            expect($throttler->hasAttempts('send-message:user@example.com'))->toBeTruthy();
        });
    });

	describe('->hitIf()', function (): void {
        it("devrait incrémenter quand la condition est vraie", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $result = $throttler->hitIf('test-key', fn () => true);

            expect($result)->toBeTruthy();
            expect($throttler->attempts('test-key'))->toBe(1);
        });

        it("ne devrait pas incrémenter quand la condition est fausse", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $result = $throttler->hitIf('test-key', fn () => false);

            expect($result)->toBeFalsy();
            expect($throttler->attempts('test-key'))->toBe(0);
        });
    });

	describe('->decrement()', function (): void {
        it("devrait décrémenter le compteur", function (): void {
            $cache = $this->createCache([
                'throttler:test-key' => ['tokens' => 2, 'last_update' => microtime(true), 'max_tokens' => 10, 'window' => 60],
            ]);
            $throttler = new Throttler($cache);

            $throttler->decrement('test-key', 60);

            // Après décrémentation, le nombre de tokens restants devrait augmenter
            // car on "annule" une tentative
            expect($throttler->remaining('test-key', 10))->toBe(3);
        });

        it("ne devrait pas descendre en dessous de 0", function (): void {
            $cache = $this->createCache([
                'throttler:test-key' => ['tokens' => 10, 'last_update' => microtime(true), 'max_tokens' => 10, 'window' => 60],
            ]);
            $throttler = new Throttler($cache);

            // Tenter de décrémenter alors qu'aucune tentative n'a été faite
            $throttler->decrement('test-key', 60, 5);

            expect($throttler->remaining('test-key', 10))->toBe(10);
        });
    });

    describe('->attempts()', function (): void {
        it("devrait retourner 0 pour une clé inexistante", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            expect($throttler->attempts('new-key'))->toBe(0);
        });

        it("devrait retourner le nombre correct de tentatives", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->hit('test-key');
            $throttler->hit('test-key');
            $throttler->hit('test-key');

            expect($throttler->attempts('test-key'))->toBe(3);
        });
    });

    describe('->remaining() et ->retriesLeft()', function (): void {
        it("devrait retourner la limite totale quand aucune tentative n'a été faite", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            expect($throttler->remaining('test-key', 10))->toBe(10);
            expect($throttler->retriesLeft('test-key', 10))->toBe(10);
        });

        it("devrait retourner le nombre correct de tentatives restantes", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->hit('test-key');
            $throttler->hit('test-key');

            expect($throttler->remaining('test-key', 10))->toBe(8);
        });

        it("ne devrait pas retourner une valeur négative", function (): void {
            $cache = $this->createCache([
                'throttler:test-key' => ['tokens' => 0, 'last_update' => microtime(true), 'max_tokens' => 5, 'window' => 60],
            ]);
            $throttler = new Throttler($cache);

            expect($throttler->remaining('test-key', 5))->toBe(0);
        });
    });

    describe('->clear(), ->reset(), ->resetAttempts()', function (): void {
        it("devrait réinitialiser le compteur", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->hit('test-key');
            $throttler->hit('test-key');
            expect($throttler->attempts('test-key'))->toBe(2);

            $throttler->clear('test-key');
            expect($throttler->attempts('test-key'))->toBe(0);
        });

        it("reset() et resetAttempts() devraient être des alias de clear()", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->hit('test-key');
            $throttler->reset('test-key');
            expect($throttler->attempts('test-key'))->toBe(0);

            $throttler->hit('test-key');
            $throttler->resetAttempts('test-key');
            expect($throttler->attempts('test-key'))->toBe(0);
        });
    });

    describe('->availableIn()', function (): void {
        it("devrait retourner 0 quand la clé n'existe pas", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            expect($throttler->availableIn('new-key'))->toBe(0);
        });

        it("devrait retourner un temps positif quand la limite est atteinte", function (): void {
            $cache = $this->createCache([
                'throttler:test-key' => ['tokens' => 0, 'last_update' => microtime(true), 'max_tokens' => 5, 'window' => 60],
            ]);
            $throttler = new Throttler($cache);

            expect($throttler->availableIn('test-key'))->toBeGreaterThan(0);
        });
    });

    describe('->hasAttempts()', function (): void {
        it("devrait retourner false pour une clé non utilisée", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            expect($throttler->hasAttempts('new-key'))->toBeFalsy();
        });

        it("devrait retourner true pour une clé utilisée", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->hit('test-key');

            expect($throttler->hasAttempts('test-key'))->toBeTruthy();
        });
    });

    describe('->info()', function (): void {
        it("devrait retourner toutes les informations d'une clé", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $info = $throttler->info('test-key', 10);

            expect($info)->toBeAn('array');
            expect($info)->toContainKeys(['attempts', 'remaining', 'limit', 'available_in', 'is_limited']);
            expect($info['limit'])->toBe(10);
            expect($info['attempts'])->toBe(0);
            expect($info['remaining'])->toBe(10);
        });
    });

    describe('Limiteurs nommés', function (): void {
        it("devrait enregistrer et récupérer un limiteur nommé", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->for('api', Limit::perMinute(100));

            $limiter = $throttler->limiter('api');
            expect($limiter)->toBeAnInstanceOf(Closure::class);

            $limits = $limiter(Mockery::mock(Request::class));
            expect($limits)->toBeAn('array');
            expect($limits[0]->maxAttempts)->toBe(100);
            expect($limits[0]->decaySeconds)->toBe(60);
        });

        it("devrait retourner null pour un limiteur inexistant", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            expect($throttler->limiter('inexistant'))->toBeNull();
        });

        it("devrait supporter un callable comme limiteur", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->for('dynamic', function (Request $request) {
                $user = $request->getAttribute('user');
                $limit = $user && $user['plan'] === 'premium' ? 1000 : 60;
                return [Limit::perMinute($limit)];
            });

            $limiter = $throttler->limiter('dynamic');
            expect($limiter)->toBeAnInstanceOf(Closure::class);
        });
    });

    describe('Stratégies', function (): void {
        it("devrait utiliser TokenBucket par défaut", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            expect($throttler->getStrategy())->toBeAnInstanceOf(TokenBucket::class);
        });

        it("devrait pouvoir changer de stratégie par alias", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $throttler->setStrategy('sliding_window');
            expect($throttler->getStrategy())->toBeAnInstanceOf(SlidingWindow::class);

            $throttler->setStrategy('fixed_window');
            expect($throttler->getStrategy())->toBeAnInstanceOf(FixedWindow::class);

            $throttler->setStrategy('token_bucket');
            expect($throttler->getStrategy())->toBeAnInstanceOf(TokenBucket::class);
        });

        it("devrait pouvoir changer de stratégie par instance", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            $sliding = new SlidingWindow($cache);
            $throttler->setStrategy($sliding);
            expect($throttler->getStrategy())->toBe($sliding);
        });

        it("devrait lever une exception pour une stratégie invalide", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            expect(function () use ($throttler) {
                $throttler->setStrategy('strategie_inexistante');
            })->toThrow(new InvalidArgumentException());
        });
    });
});
