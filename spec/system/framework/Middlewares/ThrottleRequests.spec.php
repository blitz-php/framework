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
use BlitzPHP\Middlewares\ThrottleRequests;
use BlitzPHP\RateLimiter\Limit;
use BlitzPHP\RateLimiter\Strategies\TokenBucket;
use BlitzPHP\Utilities\Reflection\ReflectionClass;
use Psr\Http\Message\UriInterface;
use Spec\BlitzPHP\Middlewares\TestRequestHandler;

use function Kahlan\expect;

describe('Middleware / ThrottleRequests', function (): void {
    beforeAll(function (): void {
        $this->createCache = function (array $cacheData = []): CacheInterface {
            if ($cacheData === []) {
				$cacheData = [
					'throttler:throttle:e66b15849958ec38' => [
						'tokens'      => 59,
						'last_update' => microtime(true),
						'max_tokens'  => 60,
						'window'      => 60,
					],
				];
			}

            $cache = new ArrayHandler();
			$cache->init(config('cache'));
			$cache->setReservedCharacters('');
			$cache->clear();
			$cache->setMultiple($cacheData);

            return $cache;
        };

        $this->createRequest = function (
            string $ip = '192.168.1.100', // IP non whitelistée
            string $path = '/test',
            string $method = 'GET',
            array $headers = []
        ): Request {
            $uri = Mockery::mock(UriInterface::class);
            $uri->allows('getPath')->andReturn($path);

            $request = Mockery::mock(Request::class);
            $request->allows('getUri')->andReturn($uri);
            $request->allows('clientIp')->andReturn($ip);
            $request->allows('getMethod')->andReturn($method);
            $request->allows('getHeaderLine')->andReturnUsing(fn ($name) => $headers[$name] ?? '');
            $request->allows('bearerToken')->andReturn($headers['Authorization'] ?? '');
            $request->allows('getAttribute')->andReturn(null);

            return $request;
        };
    });

    describe('Autorisation et refus', function (): void {
        it("devrait autoriser une requête dans les limites", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest();

            $middleware = new ThrottleRequests($cache);
            $response = $middleware->process($request, new TestRequestHandler());

			expect($response->getStatusCode())->toBe(200);
            expect($response->hasHeader('X-RateLimit-Limit'))->toBeTruthy();
            expect($response->hasHeader('X-RateLimit-Remaining'))->toBeTruthy();
        });

        it("devrait bloquer une requête au-delà des limites", function (): void {
            $now = time();
            $windowStart = (int) ($now / 60) * 60;
            $key = 'throttle:e66b15849958ec38';

            $cache = $this->createCache([
                'throttler:' . $key => [
                    'tokens'      => 0,
                    'last_update' => microtime(true),
                    'max_tokens'  => 60,
                    'window'      => 60,
                ],
            ]);
            $request = $this->createRequest();

			$middleware = new ThrottleRequests($cache);
			$response = $middleware->process($request, new TestRequestHandler());

			expect($response->getStatusCode())->toBe(429);
            expect($response->hasHeader('Retry-After'))->toBeTruthy();
            expect($response->hasHeader('X-RateLimit-Exceeded'))->toBeTruthy();
        });

        it("devrait ajouter les headers standards à la réponse", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest();

            $middleware = new ThrottleRequests($cache);
            $response = $middleware->process($request, new TestRequestHandler());

            expect($response->getHeaderLine('X-RateLimit-Limit'))->toBe('60');
            expect($response->hasHeader('X-RateLimit-Remaining'))->toBeTruthy();
            expect($response->hasHeader('X-RateLimit-Reset'))->toBeTruthy();
        });
    });

    describe('Blocage après dépassement', function (): void {
        it("devrait bloquer un utilisateur lorsque blockDuration est défini", function (): void {
            $key = 'throttle:e66b15849958ec38';

            $cache = $this->createCache([
                'throttler:' . $key => [
                    'tokens'      => 0,
                    'last_update' => microtime(true),
                    'max_tokens'  => 60,
                    'window'      => 60,
                ],
				$key . ':block' => time() + 60,
            ]);

            $request = $this->createRequest();

            $middleware = new ThrottleRequests($cache);
            // Forcer blockDuration
            ReflectionClass::make($middleware)->setValue('blockDuration', 5);

            $response = $middleware->process($request, new TestRequestHandler());

            expect($response->getStatusCode())->toBe(429);
        });

        it("devrait retourner une réponse de blocage pour un client déjà bloqué", function (): void {
            $key = 'throttle:e66b15849958ec38:block';

            $cache = $this->createCache([
                $key => time() + 300, // Bloqué jusqu'à dans 5 minutes
            ]);

            $request = $this->createRequest();

            $middleware = new ThrottleRequests($cache);
            $response = $middleware->process($request, new TestRequestHandler());

            expect($response->getStatusCode())->toBe(429);
            expect($response->hasHeader('X-RateLimit-Blocked'))->toBeTruthy();
            expect($response->hasHeader('X-RateLimit-Block-Reset'))->toBeTruthy();
        });

        it("devrait exécuter le callback onBlocked", function (): void {
            $key = 'throttle:e66b15849958ec38';

            $cache = $this->createCache([
                'throttler:' . $key => [
                    'tokens'      => 0,
                    'last_update' => microtime(true),
                    'max_tokens'  => 60,
                    'window'      => 60,
                ],
                'throttler:' . $key . ':timer' => time() + 60,
            ]);
            $request = $this->createRequest();

            $blockedKey = null;
            $blockedDuration = null;

            $middleware = new ThrottleRequests($cache);
            ReflectionClass::make($middleware)->setValue('blockDuration', 5);
            $middleware->onBlocked(function (string $key, int $duration) use (&$blockedKey, &$blockedDuration): void {
                $blockedKey = $key;
                $blockedDuration = $duration;
            });

            $middleware->process($request, new TestRequestHandler());

            expect($blockedKey)->not->toBeNull();
            expect($blockedDuration)->toBe(5);
        });
    });

	describe('Types d\'identifiants', function (): void {
        it("devrait identifier par IP par défaut", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest('192.168.1.100', '/api/users');

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);

            $identifier = $reflection->invoke('resolveIdentifier', $request);

            expect($identifier)->toContain('192.168.1.100');
        });

        it("devrait identifier par utilisateur quand userBased est true", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest();

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);
            $reflection->setValue('userBased', true);

            // Simuler la fonction auth()
            if (!function_exists('auth')) {
                // Le fallback sera l'IP
                $identifier = $reflection->invoke('resolveIdentifier', $request);
                expect($identifier)->toContain('ip:');
            }
        });

        it("devrait identifier par clé API quand configuré", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest('10.0.0.1', '/api/data', 'GET', [
                'X-API-Key' => 'sk_test_12345',
            ]);

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);
            $reflection->setValue('identifier', 'api_key');

            $identifier = $reflection->invoke('resolveIdentifier', $request);

            expect($identifier)->toContain('api_key:');
        });

        it("devrait identifier par route quand configuré", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest('10.0.0.1', '/api/users');

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);
            $reflection->setValue('identifier', 'route');

            $identifier = $reflection->invoke('resolveIdentifier', $request);

            expect($identifier)->toContain('route:');
        });

        it("devrait supporter un callback d'identification personnalisé", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest('10.0.0.1', '/test');

            $middleware = new ThrottleRequests($cache);
            $middleware->withIdentifier(function (Request $req): string {
                return 'custom:' . $req->clientIp();
            });

            $reflection = new ReflectionClass($middleware);
            $identifier = $reflection->invoke('resolveIdentifier', $request);

            expect($identifier)->toBe('custom:10.0.0.1');
        });
    });

	describe('Coût des requêtes', function (): void {
        it("devrait retourner le coût par défaut (1)", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest();

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);

            $cost = $reflection->invoke('resolveCost', $request, []);

            expect($cost)->toBe(1);
        });

        it("devrait calculer le coût automatique selon la méthode HTTP", function (): void {
            $cache = $this->createCache();
            $reflection = new ReflectionClass(new ThrottleRequests($cache));

            $getRequest = $this->createRequest('127.0.0.1', '/test', 'GET');
            expect($reflection->invoke('resolveCost', $getRequest, ['cost' => 'auto']))->toBe(1);

            $postRequest = $this->createRequest('127.0.0.1', '/test', 'POST');
            expect($reflection->invoke('resolveCost', $postRequest, ['cost' => 'auto']))->toBe(2);

            $deleteRequest = $this->createRequest('127.0.0.1', '/test', 'DELETE');
            expect($reflection->invoke('resolveCost', $deleteRequest, ['cost' => 'auto']))->toBe(3);
        });

        it("devrait supporter un callback de coût personnalisé", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest();

            $middleware = new ThrottleRequests($cache);
            $middleware->withCost(function (Request $req): int {
                return 10;
            });

            $reflection = new ReflectionClass($middleware);
            $cost = $reflection->invoke('resolveCost', $request, []);

            expect($cost)->toBe(10);
        });
    });

	describe('Ignorer le rate limiting', function (): void {
        it("devrait ignorer les IPs whitelistées", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest('127.0.0.1');

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);

            expect($reflection->invoke('shouldSkip', $request))->toBeTruthy();
        });

        it("ne devrait pas ignorer les IPs non whitelistées", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest('203.0.113.42');

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);

            expect($reflection->invoke('shouldSkip', $request))->toBeFalsy();
        });

        it("devrait supporter un callback skip personnalisé", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest('203.0.113.42');

            $middleware = new ThrottleRequests($cache);
            $middleware->skipWhen(function (Request $req): bool {
                return $req->clientIp() === '203.0.113.42';
            });

            $reflection = new ReflectionClass($middleware);

            expect($reflection->invoke('shouldSkip', $request))->toBeTruthy();
        });
    });

	describe('Construction de la clé de cache', function (): void {
        it("devrait générer une clé unique basée sur l'IP et le chemin", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest('192.168.1.100', '/api/users');

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);

            $key = $reflection->invoke('buildKey', $request, 'ip:192.168.1.100');

            expect($key)->toContain('throttle:');
            expect(strlen($key))->toBeGreaterThan(10);
        });

        it("devrait inclure le préfixe quand il est défini", function (): void {
            $cache = $this->createCache();
            $request = $this->createRequest('192.168.1.100', '/api/users');

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);
            $reflection->setValue('prefix', 'api');

            $key = $reflection->invoke('buildKey', $request, 'ip:192.168.1.100');

            expect($key)->toContain('throttle:api:');
        });

        it("devrait produire la même clé pour la même entrée", function (): void {
            $cache = $this->createCache();
            $request1 = $this->createRequest('192.168.1.100', '/api/users');
            $request2 = $this->createRequest('192.168.1.100', '/api/users');

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);

            $key1 = $reflection->invoke('buildKey', $request1, 'ip:192.168.1.100');
            $key2 = $reflection->invoke('buildKey', $request2, 'ip:192.168.1.100');

            expect($key1)->toBe($key2);
        });

        it("devrait produire des clés différentes pour des chemins différents", function (): void {
            $cache = $this->createCache();
            $request1 = $this->createRequest('192.168.1.100', '/api/users');
            $request2 = $this->createRequest('192.168.1.100', '/api/products');

            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);

            $key1 = $reflection->invoke('buildKey', $request1, 'ip:192.168.1.100');
            $key2 = $reflection->invoke('buildKey', $request2, 'ip:192.168.1.100');

            expect($key1)->not->toBe($key2);
        });
    });

	describe('Formatage des réponses d\'erreur', function (): void {
        it("devrait formater une réponse JSON quand Accept: application/json", function (): void {
            $cache = $this->createCache();
            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);

            $response = service('response')
                ->withHeader('Accept', 'application/json')
                ->withStatus(429);

            $response = $reflection->invoke('formatErrorResponse', $response, 'Too Many Requests');

            expect($response->getHeaderLine('Content-Type'))->toContain('application/json');

            $body = json_decode((string) $response->getBody(), true);
            expect($body)->toContainKey('error');
            expect($body['error'])->toBeTruthy();
            expect($body)->toContainKey('message');
            expect($body['message'])->toBe('Too Many Requests');
        });

        it("devrait formater une réponse texte brut par défaut", function (): void {
            $cache = $this->createCache();
            $middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);

            $response = service('response')->withStatus(429);

            $response = $reflection->invoke('formatErrorResponse', $response, 'Too Many Requests');

            expect($response->getHeaderLine('Content-Type'))->toContain('text/plain');
            expect((string) $response->getBody())->toBe('Too Many Requests');
        });
    });

	describe('Messages d\'erreur personnalisés', function (): void {
        it("devrait utiliser les messages d'erreur personnalisés", function (): void {
            $key = 'throttle:e66b15849958ec38';

            $cache = $this->createCache([
                'throttler:' . $key => [
                    'tokens'      => 0,
                    'last_update' => microtime(true),
                    'max_tokens'  => 60,
                    'window'      => 60,
                ],
                'throttler:' . $key . ':timer' => time() + 60,
            ]);
            $request = $this->createRequest();

            $middleware = new ThrottleRequests($cache);
            $middleware->setErrorMessages([
                'too_many_requests' => 'Trop de requêtes. Réessayez plus tard.',
            ]);

            $response = $middleware->process($request, new TestRequestHandler());

            expect((string) $response->getBody())->toBe('Trop de requêtes. Réessayez plus tard.');
        });
    });

	describe('Méthode with()', function (): void {
        it("devrait générer une chaîne de middleware correcte", function (): void {
            $result = ThrottleRequests::with(
                maxAttempts: 5,
                decayMinutes: 15,
                prefix: 'login',
                userBased: true,
                blockDuration: 30,
                strategy: 'sliding_window',
                identifier: 'user',
                cost: 2,
            );

            expect($result)->toContain(ThrottleRequests::class);
            expect($result)->toContain('5');
            expect($result)->toContain('15');
            expect($result)->toContain('login');
            expect($result)->toContain('sliding_window');
        });

        it("devrait produire la même chaîne avec les mêmes paramètres", function (): void {
            $result1 = ThrottleRequests::with(60, 1, '', false, 0, 'token_bucket', 'ip', 1);
            $result2 = ThrottleRequests::with(60, 1, '', false, 0, 'token_bucket', 'ip', 1);

            expect($result1)->toBe($result2);
        });
    });

	describe('Résolution de stratégie', function (): void {
        it("devrait utiliser TokenBucket par défaut", function (): void {
            $cache = $this->createCache();
            $middleware = new ThrottleRequests($cache);
			$throttler = ReflectionClass::make($middleware)->getValue('throttler');

            expect($throttler->getStrategy())->toBeAnInstanceOf(TokenBucket::class);
        });

        it("devrait pouvoir changer de stratégie via la config", function (): void {
            $cache = $this->createCache();
            $middleware = new ThrottleRequests($cache);

            $reflection = new ReflectionClass($middleware);
            $reflection->invoke('resolveStrategy', ['strategy' => 'fixed_window']);

			$throttler = ReflectionClass::make($middleware)->getValue('throttler');

            expect($throttler->getStrategy())
                ->toBeAnInstanceOf(\BlitzPHP\RateLimiter\Strategies\FixedWindow::class);
        });
    });

	describe('Limiteurs nommés', function (): void {
        it("devrait utiliser un limiteur nommé depuis les attributs de route", function (): void {
            $cache   = $this->createCache();
            $request = $this->createRequest();

            // Configurer un limiteur nommé dans le Throttler
			$middleware = new ThrottleRequests($cache);
            $reflection = new ReflectionClass($middleware);
			$throttler  = $reflection->getValue('throttler');

            $throttler->for('api_premium', Limit::perMinute(300));

            // Simuler un attribut de route
            $request->allows('getAttribute')->with('throttler')->andReturn('api_premium');

            $config = $reflection->invoke('resolveConfig', $request);

            expect($config)->toContainKey('maxAttempts');
            expect($config)->toContainKey('decayMinutes');
        });
    });
});
