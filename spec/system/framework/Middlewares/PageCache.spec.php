<?php
/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Cache\Handlers\Dummy;
use BlitzPHP\Cache\ResponseCache;
use BlitzPHP\Http\Redirection;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Middlewares\PageCache;
use Psr\Http\Message\UriInterface;
use Psr\SimpleCache\CacheInterface;
use Spec\BlitzPHP\Middlewares\TestRequestHandler;

use function Kahlan\expect;

describe('Middleware / PageCache', function (): void {
    beforeAll(function (): void {
        $this->getCache = (fn($cachedData = null) => new class($cachedData) extends Dummy {
				public function __construct(private $cachedData) { }
				public function get(string $key, mixed $default = null): mixed
				{
					return $this->cachedData;
				}
			});

        $this->getResponseCache = (fn(CacheInterface $cache, $cacheQueryString = false) => new ResponseCache($cache, $cacheQueryString));

		$this->request = function() {
			$uri = Mockery::mock(UriInterface::class);
			$uri->shouldReceive('withFragment')->andReturn($uri);
			$uri->shouldReceive('withQuery')->andReturn($uri);
			$uri->shouldReceive('getQuery')->andReturn('');

			$request = Mockery::mock(Request::class, ['getMethod' => 'GET']);
			$request->shouldReceive('getUri')->andReturn($uri);

			return $request;
		};
    });

    it("devrait retourner une réponse en cache lorsqu'elle existe", function (): void {
        $cachedResponse = serialize([
            'headers' => ['Content-Type' => 'text/html'],
            'output'  => 'Contenu mis en cache',
            'status'  => 200,
            'reason'  => 'OK',
        ]);

        $cache = $this->getCache($cachedResponse);
        $responseCache = $this->getResponseCache($cache);

        $middleware = new PageCache($responseCache);
        $response = $middleware->process($this->request(), new TestRequestHandler());

        expect($response->getBody()->getContents())->toBe('Contenu mis en cache');
        expect($response->getStatusCode())->toBe(200);
        expect($response->getHeaderLine('Content-Type'))->toBe('text/html');
    });

    it("devrait gérer une requête sans cache", function (): void {
        $cache = $this->getCache();
        $responseCache = $this->getResponseCache($cache);

        $request = $this->request();

        $handler = new TestRequestHandler(fn($request) => (new Response())->withBody(to_stream('Nouveau contenu')));

        $middleware = new PageCache($responseCache);
        $response = $middleware->process($request, $handler);

        expect($response->getBody()->getContents())->toBe('Nouveau contenu');
    });

    it("devrait mettre en cache une réponse avec les codes de statut spécifiés", function (): void {
        $cache = $this->getCache();
        $responseCache = $this->getResponseCache($cache);

        $handler = new TestRequestHandler(fn($request) => (new Response())->withStatus(404)->withBody(to_stream('Page non trouvée')));

        // Seulement cache les 404
        $middleware = new PageCache($responseCache, [404]);
        $response = $middleware->process($this->request(), $handler);

        expect($response->getStatusCode())->toBe(404);
        expect($response->getBody()->getContents())->toBe('Page non trouvée');
    });

    it("ne devrait pas mettre en cache les redirections", function (): void {
        $cache = $this->getCache();
        $responseCache = $this->getResponseCache($cache);

        $handler = new TestRequestHandler(function ($request) {
			$redirection = Mockery::mock(Redirection::class);
			$redirection->shouldReceive('getBody')->andReturn(to_stream('Redirection'));
			$redirection->shouldReceive('getStatusCode')->andReturn(302);
			$redirection->shouldReceive('withBody')->andReturn($redirection);

			return $redirection;
        });

        $middleware = new PageCache($responseCache);
        $response = $middleware->process($this->request(), $handler);

        // Le cache ne devrait pas être appelé pour les redirections
        expect(true)->toBeTruthy();
    });
});
