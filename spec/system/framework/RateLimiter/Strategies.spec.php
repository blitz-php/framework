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
use BlitzPHP\RateLimiter\Strategies\TokenBucket;
use BlitzPHP\RateLimiter\Strategies\SlidingWindow;
use BlitzPHP\RateLimiter\Strategies\FixedWindow;
use BlitzPHP\RateLimiter\Strategies\BaseStrategy;
use BlitzPHP\Contracts\RateLimiter\Limiter;
use BlitzPHP\Contracts\RateLimiter\ResultInterface;

use function Kahlan\expect;

describe('RateLimiter / Strategies', function (): void {
    beforeAll(function (): void {
        $this->createCache = function (array $initialData = []): CacheInterface {
			$cache = new ArrayHandler();
			$cache->init(config('cache'));
			$cache->setReservedCharacters('');
			$cache->setMultiple($initialData);

			return $cache;
        };
    });

    describe('TokenBucket', function (): void {
        it("devrait autoriser une requête quand des tokens sont disponibles", function (): void {
            $cache = $this->createCache();
            $strategy = new TokenBucket($cache);

            $result = $strategy->attempt('test', 10, 60, 1);

            expect($result->isAllowed())->toBeTruthy();
            expect($result->remaining)->toBe(9);
            expect($result->limit)->toBe(10);
        });

        it("devrait refuser une requête quand aucun token n'est disponible", function (): void {
            $cache = $this->createCache([
                'throttler:test' => [
                    'tokens'      => 0,
                    'last_update' => microtime(true),
                    'max_tokens'  => 10,
                    'window'      => 60,
                ],
            ]);
            $strategy = new TokenBucket($cache);

            $result = $strategy->attempt('test', 10, 60, 1);

            expect($result->isAllowed())->toBeFalsy();
            expect($result->retryAfter)->toBeGreaterThan(0);
        });

        it("devrait supporter les coûts variables", function (): void {
            $cache = $this->createCache();
            $strategy = new TokenBucket($cache);

            $result = $strategy->attempt('test', 10, 60, 5);

            expect($result->isAllowed())->toBeTruthy();
            expect($result->remaining)->toBe(5); // 10 - 5 = 5
        });

        it("devrait recharger les tokens avec le temps", function (): void {
            $cache = $this->createCache([
                'throttler:test' => [
                    'tokens'      => 5,
                    'last_update' => microtime(true) - 30, // Il y a 30 secondes
                    'max_tokens'  => 10,
                    'window'      => 60,
                ],
            ]);
            $strategy = new TokenBucket($cache);
            // 30 secondes écoulées → 5 tokens ajoutés (10/60 * 30)

            $result = $strategy->attempt('test', 10, 60, 1);

            expect($result->isAllowed())->toBeTruthy();
            expect($result->remaining)->toBeGreaterThan(5); // Plus que 5 restants grâce au refill
        });

        it("ne devrait pas dépasser la capacité maximale du seau", function (): void {
            $cache = $this->createCache([
                'throttler:test' => [
                    'tokens'      => 9,
                    'last_update' => microtime(true) - 120, // Il y a 2 minutes
                    'max_tokens'  => 10,
                    'window'      => 60,
                ],
            ]);
            $strategy = new TokenBucket($cache);

            $result = $strategy->attempt('test', 10, 60, 1);

            expect($result->isAllowed())->toBeTruthy();
            // Le seau ne peut pas dépasser 10 tokens, donc après refill : 10 - 1 = 9
            expect($result->remaining)->toBe(9);
        });

        it("devrait retourner le nombre correct de tentatives", function (): void {
            $cache = $this->createCache([
                'throttler:test' => [
                    'tokens'      => 3,
                    'last_update' => microtime(true),
                    'max_tokens'  => 10,
                    'window'      => 60,
                ],
            ]);
            $strategy = new TokenBucket($cache);

            expect($strategy->attempts('test'))->toBe(7); // 10 - 3 = 7 tentatives
        });

        it("devrait retourner 0 tentatives pour une clé inexistante", function (): void {
            $cache = $this->createCache();
            $strategy = new TokenBucket($cache);

            expect($strategy->attempts('new-key'))->toBe(0);
        });

        it("devrait supporter l'incrémentation manuelle", function (): void {
            $cache = $this->createCache();
            $strategy = new TokenBucket($cache);

            $count = $strategy->increment('test', 60, 5);

            expect($count)->toBeGreaterThan(0);
        });

        it("devrait pouvoir être réinitialisé", function (): void {
            $cache = $this->createCache([
                'throttler:test' => [
                    'tokens'      => 0,
                    'last_update' => microtime(true),
                    'max_tokens'  => 10,
                    'window'      => 60,
                ],
            ]);
            $strategy = new TokenBucket($cache);

            $strategy->reset('test');
            expect($strategy->attempts('test'))->toBe(0);
        });
    });

    describe('SlidingWindow', function (): void {
        it("devrait autoriser une requête dans la limite", function (): void {
            $cache = $this->createCache();
            $strategy = new SlidingWindow($cache);

            $result = $strategy->attempt('test', 10, 60, 1);

            expect($result->isAllowed())->toBeTruthy();
            expect($result->remaining)->toBe(9);
        });

        it("devrait refuser une requête au-delà de la limite", function (): void {
            $cache = $this->createCache([
                'throttler:test' => [
                    'count'        => 10,
                    'reset'        => time() + 60,
                    'window_start' => time(),
                ],
            ]);
            $strategy = new SlidingWindow($cache);

            $result = $strategy->attempt('test', 10, 60, 1);

            expect($result->isAllowed())->toBeFalsy();
            expect($result->retryAfter)->toBeGreaterThan(0);
        });

        it("devrait réduire le poids des anciennes requêtes", function (): void {
            $cache = $this->createCache([
                'throttler:test' => [
                    'count'        => 10,
                    'reset'        => time() + 60,
                    'window_start' => time() - 30, // A commencé il y a 30 secondes
                ],
            ]);
            $strategy = new SlidingWindow($cache);
            // Poids = 1 - (30/60) = 0.5, donc count effectif ≈ 5

            $result = $strategy->attempt('test', 10, 60, 1);

            expect($result->isAllowed())->toBeTruthy();
            expect($result->remaining)->toBeLessThan(10);
        });

        it("devrait réinitialiser la fenêtre si elle est expirée", function (): void {
            $cache = $this->createCache([
                'throttler:test' => [
                    'count'        => 10,
                    'reset'        => time() - 10, // Reset dans le passé
                    'window_start' => time() - 120, // A commencé il y a 2 minutes (> 60s)
                ],
            ]);
            $strategy = new SlidingWindow($cache);

            $result = $strategy->attempt('test', 10, 60, 1);

            expect($result->isAllowed())->toBeTruthy();
            expect($result->remaining)->toBe(9); // Fenêtre réinitialisée
        });

        it("devrait retourner le temps avant disponibilité", function (): void {
            $cache = $this->createCache([
                'throttler:test' => [
                    'count'        => 10,
                    'reset'        => time() + 42,
                    'window_start' => time(),
                ],
            ]);
            $strategy = new SlidingWindow($cache);

            expect($strategy->availableIn('test'))->toBeGreaterThan(0);
        });
    });

    describe('FixedWindow', function (): void {
        it("devrait autoriser une requête dans la fenêtre", function (): void {
            $cache = $this->createCache();
            $strategy = new FixedWindow($cache);

            $result = $strategy->attempt('test', 10, 60, 1);

            expect($result->isAllowed())->toBeTruthy();
            expect($result->remaining)->toBe(9);
        });

        it("devrait refuser une requête au-delà de la limite dans la même fenêtre", function (): void {
            $now = time();
            $windowStart = (int) ($now / 60) * 60;
            $key = 'test:' . $windowStart;

            $cache = $this->createCache([
                'throttler:' . $key => 10, // Déjà 10 requêtes dans cette fenêtre
            ]);
            $strategy = new FixedWindow($cache);

            $result = $strategy->attempt('test', 10, 60, 1);

            expect($result->isAllowed())->toBeFalsy();
            expect($result->retryAfter)->toBeGreaterThan(0);
        });

        it("devrait supporter l'incrémentation manuelle", function (): void {
            $cache = $this->createCache();
            $strategy = new FixedWindow($cache);

            $count = $strategy->increment('test', 60, 3);

            expect($count)->toBe(3);
        });

        it("devrait retourner le temps avant reset", function (): void {
            $cache = $this->createCache();
            $strategy = new FixedWindow($cache);

            $strategy->attempt('test', 10, 60, 10); // Consomme tous les tokens
            $availableIn = $strategy->availableIn('test');

            expect($availableIn)->toBeGreaterThan(0);
        });
    });

    describe('BaseStrategy', function (): void {
        it("devrait résoudre les alias de stratégies", function (): void {
            expect(BaseStrategy::named('token_bucket'))->toBe(TokenBucket::class);
            expect(BaseStrategy::named('sliding_window'))->toBe(SlidingWindow::class);
            expect(BaseStrategy::named('fixed_window'))->toBe(FixedWindow::class);
        });

        it("devrait retourner la valeur telle quelle si l'alias n'est pas reconnu", function (): void {
            expect(BaseStrategy::named('App\\Custom\\Strategy'))->toBe('App\\Custom\\Strategy');
        });

        it("devrait permettre l'ajout de nouvelles stratégies", function (): void {
            $customStrategy = new class($this->createCache()) extends BaseStrategy implements Limiter {
                public function attempt(string $key, int $limit, int $window, int $cost = 1): ResultInterface {
                    return new \BlitzPHP\RateLimiter\RateLimitResult(true, $limit, $limit - $cost, time() + $window);
                }
                public function attempts(string $key): int { return 0; }
                public function increment(string $key, int $window, int $amount = 1): int { return $amount; }
                public function availableIn(string $key): int { return 0; }
            };

            BaseStrategy::extends('custom_test', $customStrategy::class);

            expect(BaseStrategy::named('custom_test'))->toBe($customStrategy::class);
        });
    });
});
