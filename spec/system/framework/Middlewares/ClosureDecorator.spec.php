<?php
/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Middlewares\ClosureDecorator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spec\BlitzPHP\Middlewares\TestRequestHandler;

use function Kahlan\expect;

describe('Middleware / ClosureDecorator', function (): void {
    beforeAll(function (): void {
        $this->getRequest = (fn() => Mockery::mock(Request::class));
    });

    it("devrait décorer une closure avec la signature PSR-15 standard", function (): void {
        $req = $this->getRequest();
        $response = new Response();

        $called = false;
        $closure = function ($request, $handler) use (&$called, $req): Response {
            $called = true;
            expect($request)->toBe($req);
            expect($handler)->toBeAnInstanceOf(RequestHandlerInterface::class);
            return new Response();
        };

        $handler = new TestRequestHandler();
        $middleware = new ClosureDecorator($closure, $response);
        $result = $middleware->process($req, $handler);

        expect($called)->toBeTruthy();
        expect($result)->toBeAnInstanceOf(ResponseInterface::class);
    });

    it("devrait décorer une closure avec signature request, response, next", function (): void {
        $req = $this->getRequest();
        $res = new Response();

        $called = false;
        $closure = function ($request, $response, $next) use (&$called, $req, $res): Response {
            $called = true;
            expect($request)->toBe($req);
            expect($response)->toBe($res);
            expect($next)->toBeAnInstanceOf(Closure::class);
            return new Response();
        };

        $handler = new TestRequestHandler();
        $middleware = new ClosureDecorator($closure, $res);
        $result = $middleware->process($req, $handler);

        expect($called)->toBeTruthy();
        expect($result)->toBeAnInstanceOf(ResponseInterface::class);
    });

    it("devrait retourner la réponse du handler pour une closure avec signature inconnue", function (): void {
        $request = $this->getRequest();
        $response = new Response();

        $handlerResponse = new Response();
        $handler = Mockery::mock(RequestHandlerInterface::class, [
            'handle' => $handlerResponse,
        ]);

        $closure = (fn($unknownParam) => new Response());

        $middleware = new ClosureDecorator($closure, $response);
        $result = $middleware->process($request, $handler);

        expect($result)->toBe($handlerResponse);
    });

    it("devrait retourner la closure décorée via getCallable", function (): void {
        $closure = (fn() => new Response());

        $middleware = new ClosureDecorator($closure);

        expect($middleware->getCallable())->toBe($closure);
    });
});
