<?php
/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Http\Request;
use BlitzPHP\Middlewares\BodyParser;
use Psr\Http\Message\StreamInterface;
use Spec\BlitzPHP\Middlewares\TestRequestHandler;

use function Kahlan\expect;

describe('Middleware / BodyParser', function (): void {
    beforeAll(function (): void {
        $this->getRequest = function ($method = 'POST', $contentType = 'application/json', $body = '') {
            $request = Mockery::mock(Request::class, [
				'getMethod' => $method,
				'getBody' => Mockery::mock(StreamInterface::class, [
					'getContents' => $body,
				]),
			]);
			$request->shouldReceive('getHeaderLine')->andReturnUsing(fn($header) => $header === 'Content-Type' ? $contentType : '');
			$request->shouldReceive('withParsedBody')->andReturnUsing(function ($data) use($request) {
				$request->parsedBody = $data;
				return $request;
			});

			return $request;
        };
    });

    it("devrait parser le corps JSON d'une requête", function (): void {
        $jsonBody = '{"name": "John", "age": 30}';
        $request = $this->getRequest('POST', 'application/json', $jsonBody);

        $handler = new TestRequestHandler(function ($request) {
            expect($request->parsedBody)->toBe(['name' => 'John', 'age' => 30]);
            return service('response');
        });

        $middleware = new BodyParser(['json' => true]);
        $middleware->process($request, $handler);
    });

    it("devrait parser le corps XML d'une requête", function (): void {
        $xmlBody = '<?xml version="1.0"?><user><name>John</name><age>30</age></user>';
        $request = $this->getRequest('POST', 'application/xml', $xmlBody);

        $handler = new TestRequestHandler(function ($request) {
            expect($request->parsedBody)->toBe(['name' => 'John', 'age' => '30']);
            return service('response');
        });

        $middleware = new BodyParser(['json' => true, 'xml' => true]);
        $middleware->process($request, $handler);
    });

    it("devrait ignorer les méthodes HTTP non configurées", function (): void {
        $request = $this->getRequest('GET', 'application/json', '{"test": "data"}');

        $handler = new TestRequestHandler(function ($request) {
			expect(get_object_vars($request))->not->toContain('parsedBody');
            return service('response');
        });

        $middleware = new BodyParser(['json' => true]);
        $middleware->setMethods(['POST', 'PUT']);
        $middleware->process($request, $handler);
    });

    it("devrait lancer une exception pour du JSON invalide", function (): void {
        $invalidJson = '{"name": "John", "age": }';
        $request = $this->getRequest('POST', 'application/json', $invalidJson);

        $handler = new TestRequestHandler();
        $middleware = new BodyParser(['json' => true]);

        expect(function () use ($middleware, $request, $handler): void {
            $middleware->process($request, $handler);
        })->toThrow(HttpException::badRequest());
    });

    it("devrait gérer un corps JSON vide", function (): void {
        $request = $this->getRequest('POST', 'application/json', '');

        $handler = new TestRequestHandler(function ($request) {
            expect($request->parsedBody)->toBe([]);
            return service('response');
        });

        $middleware = new BodyParser(['json' => true]);
        $middleware->process($request, $handler);
    });

    it("devrait ajouter un parser personnalisé", function (): void {
        $request = $this->getRequest('POST', 'text/csv', 'John,Doe,30');

        $handler = new TestRequestHandler(function ($request) {
            expect($request->parsedBody)->toBe(['John', 'Doe', '30']);
            return service('response');
        });

        $middleware = new BodyParser(['json' => false]);
        $middleware->addParser(['text/csv'], fn($body) => str_getcsv($body));

        $middleware->process($request, $handler);
    });
});
