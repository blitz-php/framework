<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use Psr\Http\Message\ResponseInterface;
use BlitzPHP\Http\CorsBuilder;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Http\ServerRequestFactory;
use BlitzPHP\Middlewares\Cors;
use Spec\BlitzPHP\Middlewares\TestRequestHandler;

use function Kahlan\expect;

describe('Middleware / Cors', function (): void {
    describe('CorsBuilder', function(): void {
		beforeAll(function (): void {
			$this->request  = fn() => new Request();
			$this->response = fn() => new Response();
			$this->config   = [
				'allowedOrigins'         => ['*'],
				'allowedOriginsPatterns' => [],
				'supportsCredentials'    => false,
				'allowedHeaders'         => ['*'],
				'exposedHeaders'         => [],
				'allowedMethods'         => ['*'],
				'maxAge'                 => 0,
			];
		});

		it("Teste si la requete est Cors", function (): void {
			/** @var Request $request */
			$request = $this->request()->withHeader('Origin', 'http://foo-bar.test');

			$cors = new CorsBuilder($this->config);
			expect($cors->isCorsRequest($request))->toBeTruthy();

			/** @var Request $request */
			$request = $this->request()->withHeader('Foo', 'http://foo-bar.test');

			$cors = new CorsBuilder($this->config);
			expect($cors->isCorsRequest($request))->toBeFalsy();
		});

		it("Teste si c'est une requete Preflight", function (): void {
			/** @var Request $request */
			$request = $this->request()->withMethod('OPTIONS')
				->withHeader('Access-Control-Request-Method', 'GET');

			$cors = new CorsBuilder($this->config);
			expect($cors->isPreflightRequest($request))->toBeTruthy();

			/** @var Request $request */
			$request = $this->request()->withMethod('GET')
				->withHeader('Access-Control-Request-Method', 'GET');

			$cors = new CorsBuilder($this->config);
			expect($cors->isPreflightRequest($request))->toBeFalsy();
		});

		it("Vary Header", function (): void {
			/** @var Response $response */
			$response = $this->response()
				->withHeader('Vary', 'Access-Control-Request-Method');

			$cors = new CorsBuilder($this->config);
			$vary = $cors->varyHeader($response, 'Access-Control-Request-Method');

			expect($response->getHeaderLine('Vary'))->toBe($vary->getHeaderLine('Vary'));
		});

		it("Gere une requete Preflight", function (): void {
			/** @var Request $request */
			$request = $this->request()
				->withMethod('OPTIONS')
				->withHeader('Origin', 'http://foobar.com')
				->withHeader('Access-Control-Request-Method', 'GET')
				->withHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

			$cors = new CorsBuilder($this->config);
			$expected = $cors->handlePreflightRequest($request);

			expect($expected->getHeaderLine('Access-Control-Allow-Credentials'))->toBeEmpty();
			expect($expected->getHeaderLine('Access-Control-Expose-Headers'))->toBeEmpty();
			expect($expected->getHeaderLine('Access-Control-Allow-Methods'))->toBe('GET');
			expect($expected->hasHeader('Vary'))->toBeTruthy();
			expect($expected->getHeaderLine('Vary'))->toBe('Access-Control-Request-Method, Access-Control-Request-Headers');
			expect($expected->getHeaderLine('Access-Control-Allow-Headers'))->toBe('X-CSRF-TOKEN');
			expect($expected->getHeaderLine('Access-Control-Max-Age'))->toBe('0');
			expect($expected->getStatusCode())->toBe(204);
		});

		it("Gere une requete", function (): void {
			/** @var Request $request */
			$request = $this->request()
				->withMethod('GET')
				->withHeader('Origin', 'http://foobar.com');

			/** @var Response $response */
			$response = $this->response()
				->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));

			$cors = new CorsBuilder($this->config);
			$expected = $cors->addPreflightRequestHeaders($request, $response);

			expect($expected->hasHeader('Access-Control-Allow-Origin'))->toBeTruthy();
			expect($expected->getHeaderLine('Access-Control-Allow-Origin'))->toBe('*');
		});

		it("Gere une requete Preflight avec des restrictions AllowesHeaders", function (): void {
			/** @var Request $request */
			$request = $this->request()
				->withMethod('OPTIONS')
				->withHeader('Origin', 'http://foobar.com')
				->withHeader('Access-Control-Request-Method', 'GET')
				->withHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

			$cors = new CorsBuilder(array_merge($this->config, [
				'allowedHeaders' => ['SAMPLE-RESTRICT-HEADER'],
			]));
			$expected = $cors->handlePreflightRequest($request);

			expect($request->getHeaderLine('Access-Control-Request-Headers'))
				->not->toBe($expected->getHeaderLine('Access-Control-Allow-Headers'));
		});

		it("Gere une requete Preflight avec des memes restrictions AllowedHeaders", function (): void {
			/** @var Request $request */
			$request = $this->request()
				->withMethod('OPTIONS')
				->withHeader('Origin', 'http://foobar.com')
				->withHeader('Access-Control-Request-Method', 'GET')
				->withHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

			$cors = new CorsBuilder(array_merge($this->config, [
				'allowedHeaders' => ['X-CSRF-TOKEN'],
			]));
			$expected = $cors->handlePreflightRequest($request);

			expect($request->getHeaderLine('Access-Control-Request-Headers'))
				->toBe($expected->getHeaderLine('Access-Control-Allow-Headers'));
		});

		it("Gere une requete Preflight avec des restrictions AllowedOrigins", function (): void {
			/** @var Request $request */
			$request = $this->request()
				->withMethod('OPTIONS')
				->withHeader('Origin', 'http://foobar.com')
				->withHeader('Access-Control-Request-Method', 'GET')
				->withHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

			$cors = new CorsBuilder(array_merge($this->config, [
				'allowedOrigins' => ['http://foo.com'],
			]));
			$expected = $cors->handlePreflightRequest($request);

			expect($request->getHeaderLine('Origin'))
				->not->toBe($expected->getHeaderLine('Access-Control-Allow-Origin'));
		});

		it("Gere une requete Preflight avec des memes restrictions AllowedOrigins", function (): void {
			/** @var Request $request */
			$request = $this->request()
				->withMethod('OPTIONS')
				->withHeader('Origin', 'http://foo.com')
				->withHeader('Access-Control-Request-Method', 'GET')
				->withHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

			$cors = new CorsBuilder(array_merge($this->config, [
				'allowedOrigins' => ['http://foo.com'],
			]));
			$expected = $cors->handlePreflightRequest($request);

			expect($request->getHeaderLine('Origin'))
				->toBe($expected->getHeaderLine('Access-Control-Allow-Origin'));
		});

		it("Gere une requete Preflight avec ExposeHeaders", function (): void {
			/** @var Request $request */
			$request = $this->request()
				->withMethod('GET')
				->withHeader('Origin', 'http://foo.com')
				->withHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

			$cors = new CorsBuilder(array_merge($this->config, [
				'exposedHeaders' => ['X-My-Custom-Header', 'X-Another-Custom-Header'],
			]));
			$expected = $cors->addActualRequestHeaders($request, $this->response());

			expect($expected->getHeaderLine('Access-Control-Expose-Headers'))
				->toBe("X-My-Custom-Header, X-Another-Custom-Header");
		});

		it("Gere une requete Preflight avec ExposeHeaders non definis", function (): void {
			/** @var Request $request */
			$request = $this->request()
				->withMethod('GET')
				->withHeader('Origin', 'http://foo.com')
				->withHeader('Access-Control-Request-Headers', 'X-CSRF-TOKEN');

			$cors = new CorsBuilder($this->config);
			$expected = $cors->addActualRequestHeaders($request, $this->response());

			expect($expected->getHeaderLine('Access-Control-Expose-Headers'))->toBeEmpty();
		});
	});

	describe('CorsMiddleware', function(): void {
		require_once TEST_PATH . '/support/Middlewares/TestRequestHandler.php';

		beforeAll(function (): void {
			config()->ghost('cors')->set('cors', [
				'allowedOrigins'      => ['http://localhost'],
				'supportsCredentials' => true,
				'allowedMethods'      => ['GET', 'POST', 'PUT', 'DELETE'],
				'allowedHeaders'      => ['x-allowed-header', 'x-other-allowed-header'],
				'exposedHeaders'      => [],
				'maxAge'              => 86400,    // 1 day
			]);

			$this->origin   = 'http://localhost';

			$this->setServer = function(array $server): void {
				$this->server = array_merge($this->server, $server);
			};

			$this->sendRequest = function (array $config = []): ResponseInterface {
				$request    = ServerRequestFactory::fromGlobals($this->server);
				$handler    = new TestRequestHandler();
				$middleware = new Cors($config);

				return $middleware->process($request, $handler);
			};

			$this->sendRequestForOrigin = function(string $originUrl, $allowUrl) {
				$this->setServer(['HTTP_ORIGIN' => $originUrl]);

        		return $this->sendRequest(['allowedOrigins' => $allowUrl])->getHeaderLine('Access-Control-Allow-Origin');
    		};
		});

		beforeEach(function (): void {
			$this->server   = [
				'REQUEST_URI' => '/test',
				'HTTP_ORIGIN' => $this->origin,
			];
		});

		it("modifie une requete sans origine", function (): void {
			unset($this->server['HTTP_ORIGIN']);

			/** @var Response $response */
			$response = $this->sendRequest();

			expect($response->getHeaderLine('Access-Control-Allow-Origin'))->toBe('http://localhost');

			$this->server['HTTP_ORIGIN'] = $this->origin;
		});

		it("modifie une requete ayant la même origine", function (): void {
			$this->setServer([
				'HTTP_HOST'   => 'foo.com',
				'HTTP_ORIGIN' => 'http://foo.com',
			]);

			/** @var Response $response */
			$response = $this->sendRequest([
				'allowedOrigins' => ['*']
			]);

			expect($response->getHeaderLine('Access-Control-Allow-Origin'))->toBe('http://foo.com');
		});

		it("renvoie l'en-tête `Allow Origin` en cas de requete réelle valide.", function (): void {
			/** @var Response $response */
			$response = $this->sendRequest();

			expect($response->hasHeader('Access-Control-Allow-Origin'))->toBeTruthy();
			expect($response->getHeaderLine('Access-Control-Allow-Origin'))->toBe('http://localhost');
		});

		it("renvoie l'en-tête `Allow Origin` à la requete `Autoriser toutes les origines`.", function (): void {
			/** @var Response $response */
			$response = $this->sendRequest([
				'allowedOrigins' => ['*']
			]);

			expect($response->getStatusCode())->toBe(200);
			expect($response->hasHeader('Access-Control-Allow-Origin'))->toBeTruthy();
			expect($response->getHeaderLine('Access-Control-Allow-Origin'))->toBe('http://localhost');
		});

		it("renvoie l'en-tête Allow Headers sur la demande Allow All Headers.", function (): void {
			$this->setServer([
				'HTTP_ORIGIN'                    => 'http://localhost',
				'Access-Control-Request-Method'  => 'GET',
				'REQUEST_METHOD'                 => 'OPTIONS',
				'Access-Control-Request-Headers' => 'Foo, BAR'
			]);

			/** @var Response $response */
			$response = $this->sendRequest([
				'allowedHeaders' => ['*']
			]);

			expect($response->getStatusCode())->toBe(204);
			expect($response->getHeaderLine('Access-Control-Allow-Headers'))->toBe('Foo, BAR');
			expect($response->getHeaderLine('Vary'))->toBe('Access-Control-Request-Headers, Access-Control-Request-Method');
		});

		it("définit l'en-tête AllowCredentials lorsque l'indicateur est défini dans une demande réelle valide.", function (): void {
			/** @var Response $response */
			$response = $this->sendRequest([
				'supportsCredentials' => true
			]);

			expect($response->hasHeader('Access-Control-Allow-Credentials'))->toBeTruthy();
			expect($response->getHeaderLine('Access-Control-Allow-Credentials'))->toBe('true');
		});
	});
});
