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
use Psr\Http\Message\UriInterface;

use function Kahlan\expect;

describe('RateLimiter / Intégration', function (): void {
    beforeAll(function (): void {
        $this->createCache = function (array $initialData = []): CacheInterface {
			$cache = new ArrayHandler();
			$cache->init(config('cache'));
			$cache->setReservedCharacters('');
			$cache->setMultiple($initialData);

			return $cache;
        };

        $this->createRequest = fn (string $ip = '127.0.0.1', string $path = '/test') => Mockery::mock(Request::class, [
            'getUri'      => Mockery::mock(UriInterface::class, ['getPath' => $path]),
            'clientIp'    => $ip,
            'getMethod'   => 'GET',
            'getAttribute' => null,
            'getHeaderLine' => '',
            'bearerToken' => '',
        ]);
    });

    describe('Scénario : Login avec limite de tentatives', function (): void {
        it("devrait permettre 5 tentatives puis bloquer", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);
            $ip = '192.168.1.50';
            $key = 'login:' . $ip;

            // 5 tentatives autorisées
            for ($i = 1; $i <= 5; $i++) {
                $result = $throttler->attempt($key, 5, fn () => true, 900);
                expect($result)->toBeTruthy(); // Tentative {$i} devrait être autorisée
            }

            // La 6ème tentative devrait être bloquée
            $result = $throttler->attempt($key, 5, fn () => true, 900);
            expect($result)->toBeFalsy(); // La 6ème tentative devrait être bloquée
            expect($throttler->tooManyAttempts($key, 5, 900))->toBeTruthy();
            expect($throttler->remaining($key, 5))->toBe(0);
        });

        it("devrait réinitialiser le compteur après un login réussi", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);
            $key = 'login:' . '192.168.1.60';

            // Faire 3 tentatives échouées
            for ($i = 0; $i < 3; $i++) {
                $throttler->hit($key, 900);
            }

            expect($throttler->attempts($key))->toBe(3);

            // Login réussi → réinitialiser
            $throttler->clear($key);
            expect($throttler->attempts($key))->toBe(0);
            expect($throttler->tooManyAttempts($key, 5, 900))->toBeFalsy();
        });
    });

    describe('Scénario : API avec différents plans utilisateurs', function (): void {
        it("devrait appliquer des limites différentes selon le plan", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);

            // Configurer les limiteurs nommés
            $throttler->for('api_free', Limit::perMinute(60));
            $throttler->for('api_premium', Limit::perMinute(300));
            $throttler->for('api_enterprise', Limit::perMinute(10000));

            $freeLimiter = $throttler->limiter('api_free');
            $premiumLimiter = $throttler->limiter('api_premium');
            $enterpriseLimiter = $throttler->limiter('api_enterprise');

            expect($freeLimiter)->toBeAnInstanceOf(Closure::class);
            expect($premiumLimiter)->toBeAnInstanceOf(Closure::class);
            expect($enterpriseLimiter)->toBeAnInstanceOf(Closure::class);
        });
    });

    describe('Scénario : Upload avec coûts variables', function (): void {
        it("devrait consommer plus de tokens pour les gros fichiers", function (): void {
            $cache = $this->createCache();
            $throttler = new Throttler($cache);
            $key = 'upload:user:123';

            // Petit fichier (1 MB) = 1 token
            $throttler->increment($key, 3600, 1);
            expect($throttler->attempts($key))->toBe(1);

            // Gros fichier (10 MB) = 10 tokens
            $throttler->increment($key, 3600, 10);
            expect($throttler->attempts($key))->toBe(11);

            // Vérifier le quota restant (sur 100 MB par heure)
            expect($throttler->remaining($key, 100))->toBe(89);
        });
    });
});
