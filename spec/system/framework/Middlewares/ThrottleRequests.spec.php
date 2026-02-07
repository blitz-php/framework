<?php
/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Contracts\Cache\CacheInterface;
use BlitzPHP\Http\Request;
use BlitzPHP\Middlewares\ThrottleRequests;
use BlitzPHP\Utilities\Reflection\ReflectionClass;
use Psr\Http\Message\UriInterface;
use Spec\BlitzPHP\Middlewares\TestRequestHandler;

use function Kahlan\expect;

describe('Middleware / ThrottleRequests', function (): void {
    beforeAll(function (): void {
        $this->getCache = function ($hasData = false, $counter = 1, $hasTimer = false) {
            $cacheData = [
                'throttle:key' => $counter,
                'throttle:key:timer' => time() + 60,
            ];

			$cache = Mockery::mock(CacheInterface::class);
			$cache->shouldReceive('get')->andReturnUsing(fn ($key) => $cacheData[$key] ?? null);
			$cache->shouldReceive('set')->andReturnTrue();
			$cache->shouldReceive('increment')->andReturn(2);
			$cache->shouldReceive('ttl')->andReturn(60);
			$cache->shouldReceive('has')->andReturnUsing(function ($key) use ($hasData, $hasTimer) {
				if (str_contains($key, 'block')) {
					return false;
				}
				if (str_contains($key, 'timer')) {
					return $hasTimer;
				}
				return $hasData;
			});

            return $cache;
        };

        $this->getRequest = function ($ip = '127.0.0.1', $path = '/test') {
           	$uri = Mockery::mock(UriInterface::class);
		   	$uri->shouldReceive('getPath')->andReturn($path);

			$request = Mockery::mock(Request::class);
		   	$request->shouldReceive('getUri')->andReturn($uri);
		   	$request->shouldReceive('clientIp')->andReturn($ip);

			return $request;
        };
    });

    it("devrait autoriser une requête dans les limites", function (): void {
        $cache = $this->getCache(false, 1, false);
        $request = $this->getRequest();

        $middleware = new ThrottleRequests($cache);
        $response = $middleware->process($request, new TestRequestHandler());

        expect($response->getStatusCode())->toBe(200);
        expect($response->hasHeader('X-RateLimit-Limit'))->toBeTruthy();
        expect($response->hasHeader('X-RateLimit-Remaining'))->toBeTruthy();
    });

    it("devrait bloquer une requête au-delà des limites", function (): void {
		$cache = Mockery::mock(CacheInterface::class);
		$cache->shouldReceive('has')->andReturnUsing(fn ($key) => str_ends_with($key, ':timer'));
		$cache->shouldReceive('set')->andReturnTrue();
		$cache->shouldReceive('increment')->andReturn(62);
		$cache->shouldReceive('get')->andReturnUsing(fn ($key) => str_ends_with($key, ':timer') ? time() + 60 : 61 /* Au-delà de maxAttempts (60) */);

		$request = $this->getRequest();

        $middleware = new ThrottleRequests($cache);
		ReflectionClass::make($middleware)->setValue('maxAttempts', 60);

        $response = $middleware->process($request, new TestRequestHandler());

        expect($response->getStatusCode())->toBe(429);
        expect($response->hasHeader('Retry-After'))->toBeTruthy();
        expect($response->hasHeader('X-RateLimit-Exceeded'))->toBeTruthy();
    });

    it("devrait bloquer un utilisateur lorsque blockDuration est défini", function (): void {
        $cache = Mockery::mock(CacheInterface::class);
		$cache->shouldReceive('has')->andReturnUsing(fn($key) => str_contains($key, 'block') /* L'utilisateur est déjà bloqué */);
		$cache->shouldReceive('set')->andReturnTrue();
		$cache->shouldReceive('get')->andReturn(time() + 300);

        $request = $this->getRequest();

        $middleware = new ThrottleRequests($cache);
		ReflectionClass::make($middleware)->setValue('blockDuration', 5);
        $response = $middleware->process($request, new TestRequestHandler());

        expect($response->getStatusCode())->toBe(429);
        expect($response->hasHeader('X-RateLimit-Blocked'))->toBeTruthy();
    });

    it("devrait générer une clé unique basée sur l'IP", function (): void {
        $cache = $this->getCache();
        $request = $this->getRequest('192.168.1.100', '/api/users');

        $middleware = new ThrottleRequests($cache);

        $key = null;
        $middleware->fill(['prefix' => 'api']);

        $reflection = new ReflectionClass($middleware);

        $key = $reflection->invoke('generateKey', $request);

        expect($key)->toMatch('/^throttle:api:/');
        expect($key)->toMatch('/' . sha1('ip:192.168.1.100|/api/users') . '/');
    });

    it("devrait formater une réponse JSON pour les erreurs", function (): void {
        $cache = $this->getCache();

        $middleware = new ThrottleRequests($cache);

        $reflection = new ReflectionClass($middleware);

        $response = service('response')->withHeader('Accept', 'application/json')->withStatus(429);
        $response = $reflection->invoke('formatErrorResponse', $response, 'Too Many Requests');

        expect($response->getHeaderLine('Content-Type'))->toBe('application/json');
        expect($response->getBody()->getContents())->toContain('Too Many Requests');
    });
});
