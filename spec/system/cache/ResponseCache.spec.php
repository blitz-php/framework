<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Cache\ResponseCache;
use BlitzPHP\Contracts\Cache\CacheInterface;
use Kahlan\Plugin\Double;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

use function Kahlan\expect;

describe('Cache / ResponseCache', function (): void {

    describe('Génération de clés de cache', function (): void {
        it('Génère une clé MD5 à partir de l\'URI', function (): void {
			$uri = Double::instance([
				'implements' => [UriInterface::class],
				'stubMethods' => [
					'__toString' => 'https://example.com/test'
				]
			]);
			allow($uri)->toReceive('withFragment')->with('')->andReturn($uri);
			allow($uri)->toReceive('withQuery')->with('')->andReturn($uri);

			$request = Double::instance([
				'implements' => [ServerRequestInterface::class],
				'stubMethods' => ['getUri' => $uri, 'getMethod' => 'POST']
			]);

			$cache = Double::instance([
				'implements' => [\Psr\SimpleCache\CacheInterface::class],
			]);

            $responseCache = new ResponseCache($cache);

            $key = $responseCache->generateCacheKey($request);

            expect($key)->toBe(md5('POST:https://example.com/test'));
            expect(strlen($key))->toBe(32);
        });

        it('Prend en compte les query strings lorsque configuré', function (): void {
            $uri = Double::instance([
				'implements' => [UriInterface::class],
				'stubMethods' => [
					'__toString' => 'https://example.com/test?param=value',
					'getQuery' => 'param=value',
				],
			]);
			allow($uri)->toReceive('withFragment')->with('')->andReturn($uri);
			allow($uri)->toReceive('withQuery')->andReturn($uri);

			$request = Double::instance([
				'implements' => [ServerRequestInterface::class],
				'stubMethods' => ['getUri' => $uri, 'getMethod' => 'POST'],
			]);

			$cache = Double::instance([
				'implements' => [\Psr\SimpleCache\CacheInterface::class],
			]);

            $responseCache = new ResponseCache($cache, true);

            $key = $responseCache->generateCacheKey($request);

            expect($key)->toBe(md5('POST:https://example.com/test?param=value'));
        });
    });

    describe('Mise en cache des réponses', function (): void {
        it('Ne met pas en cache si TTL est 0', function (): void {
            $uri = Double::instance([
				'implements' => [UriInterface::class],
				'stubMethods' => [
					'__toString' => 'https://example.com/test?param=value',
					'getQuery' => 'param=value',
				],
			]);
			allow($uri)->toReceive('withFragment')->with('')->andReturn($uri);
			allow($uri)->toReceive('withQuery')->andReturn($uri);

			$request = Double::instance([
				'implements' => [ServerRequestInterface::class],
			]);
			$body = Double::instance([
				'implements' => [\Psr\Http\Message\StreamInterface::class],
				'stubMethods' => [
					'getContents' => 'response content',
				],
			]);
			$response = Double::instance([
				'implements' => [ResponseInterface::class],
				'stubMethods' => [
					'getBody' => $body,
				],
			]);

			$cache = Double::instance([
				'implements' => [\Psr\SimpleCache\CacheInterface::class],
			]);

			$responseCache = new ResponseCache($cache);
            $responseCache->setTtl(0);

            $result = $responseCache->make($request, $response);

            expect($result)->toBeTruthy();
        });

        it('Met en cache la réponse avec les headers', function (): void {
            $uri = Double::instance([
				'implements' => [UriInterface::class],
				'stubMethods' => [
					'__toString' => 'https://example.com/test',
				],
			]);
			allow($uri)->toReceive('withFragment')->with('')->andReturn($uri);
			allow($uri)->toReceive('withQuery')->with('')->andReturn($uri);

			$request = Double::instance([
				'implements' => [ServerRequestInterface::class],
				'stubMethods' => ['getUri' => $uri, 'getMethod' => 'POST'],
			]);
			$body = Double::instance([
				'implements' => [\Psr\Http\Message\StreamInterface::class],
				'stubMethods' => [
					'getContents' => '{"data": "test"}',
				],
			]);
			$response = Double::instance([
				'implements' => [ResponseInterface::class],
				'stubMethods' => [
					'getBody' => $body,
					'getHeaders' => ['Content-Type' => ['application/json']],
					'getStatusCode' => 200,
					'getReasonPhrase' => 'Ok',
				],
			]);
			allow($response)->toReceive('getHeaderLine')->with('Content-Type')->andReturn('application/json');

			$cache = Double::instance([
				'implements' => [\Psr\SimpleCache\CacheInterface::class],
			]);
            $expectedData = serialize([
                'headers' => ['Content-Type' => 'application/json'],
                'output' => '{"data": "test"}'
            ]);
			allow($cache)->toReceive('set')/*->with($expectedData, 3600)*/->andReturn(true);
			// allow($cache)->toReceive('set')/*->with(Mockery::type('string'), $expectedData, 3600)*/->andReturn(true);

			$responseCache = new ResponseCache($cache);
            $responseCache->setTtl(3600);

            $result = $responseCache->make($request, $response);

            expect($result)->toBeTruthy();
        });
    });

    describe('Récupération des réponses mises en cache', function (): void {
        it('Retourne null si aucune réponse n\'est en cache', function (): void {
            $uri = Double::instance([
				'implements' => [UriInterface::class],
				'stubMethods' => [
					'__toString' => 'https://example.com/test',
				],
			]);
			allow($uri)->toReceive('withFragment')->with('')->andReturn($uri);
			allow($uri)->toReceive('withQuery')->with('')->andReturn($uri);

			$request = Double::instance([
				'implements' => [ServerRequestInterface::class],
				'stubMethods' => ['getUri' => $uri, 'getMethod' => 'GET'],
			]);
			$body = Double::instance([
				'implements' => [\Psr\Http\Message\StreamInterface::class],
				'stubMethods' => [
					'getContents' => '{"data": "test"}',
				],
			]);
			$response = Double::instance([
				'implements' => [ResponseInterface::class],
				'stubMethods' => [
					'getBody' => $body,
					'getHeaders' => ['Content-Type' => ['application/json']],
					'getStatusCode' => 200,
					'getReasonPhrase' => 'Ok',
				],
			]);
			allow($response)->toReceive('getHeaderLine')->with('Content-Type')->andReturn('application/json');

			$cache = Double::instance([
				'implements' => [\Psr\SimpleCache\CacheInterface::class],
			]);
            $expectedData = serialize([
                'headers' => ['Content-Type' => 'application/json'],
                'output' => '{"data": "test"}'
            ]);
			allow($cache)->toReceive('get')->andReturn(null);

			$responseCache = new ResponseCache($cache);

            $result = $responseCache->get($request, $response);

            expect($result)->toBeNull();
        });

        it('Reconstitue la réponse à partir du cache', function (): void {
            $uri = Double::instance([
				'implements' => [UriInterface::class],
				'stubMethods' => [
					'__toString' => 'https://example.com/test',
				],
			]);
			allow($uri)->toReceive('withFragment')->with('')->andReturn($uri);
			allow($uri)->toReceive('withQuery')->with('')->andReturn($uri);

			$request = Double::instance([
				'implements' => [ServerRequestInterface::class],
				'stubMethods' => ['getUri' => $uri, 'getMethod' => 'GET'],
			]);

			$newResponse = Double::instance([
				'implements' => [ResponseInterface::class],
			]);
			allow($newResponse)->toReceive('withHeader')->with('X-Custom', 'value')->andReturn($newResponse);
			allow($newResponse)->toReceive('withBody')->andReturn($newResponse);
			allow($newResponse)->toReceive('withStatus')->andReturn($newResponse);

			$response = Double::instance([
				'implements' => [ResponseInterface::class],
				'stubMethods' => [
					'getHeaders' => ['Existing' => ['header']],
				],
			]);
			allow($response)->toReceive('withoutHeader')->with('Existing')->andReturn($response);
			allow($response)->toReceive('withHeader')->with('Content-Type', 'application/json')->andReturn($newResponse);

			$cache = Double::instance([
				'implements' => [\Psr\SimpleCache\CacheInterface::class],
			]);
            allow($cache)->toReceive('get')->andReturn($cachedData = serialize([
                'headers' => ['Content-Type' => 'application/json', 'X-Custom' => 'value'],
                'output' => 'cached content'
            ]));

			$responseCache = new ResponseCache($cache);

            $result = $responseCache->get($request, $response);

            expect($result)->toBe($newResponse);
        });

        it('Lève une exception si les données désérialisées sont corrompues', function (): void {
            $uri = Double::instance([
				'implements' => [UriInterface::class],
				'stubMethods' => [
					'__toString' => 'https://example.com/test',
				],
			]);
			allow($uri)->toReceive('withFragment')->with('')->andReturn($uri);
			allow($uri)->toReceive('withQuery')->with('')->andReturn($uri);

			$request = Double::instance([
				'implements' => [ServerRequestInterface::class],
				'stubMethods' => ['getUri' => $uri, 'getMethod' => 'GET'],
			]);

			$response = Double::instance([
				'implements' => [ResponseInterface::class],
			]);

			$cache = Double::instance([
				'implements' => [\Psr\SimpleCache\CacheInterface::class],
			]);
            allow($cache)->toReceive('get')->andReturn(serialize('invalid_data'));


			$responseCache = new ResponseCache($cache);


            expect(fn() => $responseCache->get($request, $response))
                ->toThrow(new Exception());
        });
    });
});
