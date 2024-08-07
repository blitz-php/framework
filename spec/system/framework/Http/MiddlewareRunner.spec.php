<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\Services;
use BlitzPHP\Http\MiddlewareQueue;
use BlitzPHP\Http\MiddlewareRunner;
use BlitzPHP\Http\Response;
use Psr\Http\Message\ResponseInterface;

describe('Http / MiddlewareRunner', function (): void {
    beforeAll(function (): void {
		$this->request    = Services::request();
		$this->response   = Services::response();
		$this->container  = Services::container();
		$this->middleware = fn (array $middlewares = []) => new MiddlewareQueue($this->container, $middlewares, $this->request, $this->response);

        $this->ok   = fn ($request, $handler) => $handler->handle($request);
        $this->pass = fn ($request, $response, $next) => $next($request, $response, $next);
        $this->fail = function ($request, $handler): void {
            throw new RuntimeException('A bad thing');
        };
    });

	beforeEach(function(): void {
		$this->queue = $this->middleware();
	});

	it("Execution d'un seul middleware", function (): void {
		$this->queue->add($this->ok);

        $runner = new MiddlewareRunner();
        $result = $runner->run($this->queue, $this->request);

		expect($result)->toBeAnInstanceOf(ResponseInterface::class);
	});

	it("Execution de middlewares en sequence", function (): void {
		$log = [];
        $one = function ($request, $handler) use (&$log) {
            $log[] = 'one';

            return $handler->handle($request);
        };
        $two = function ($request, $handler) use (&$log) {
            $log[] = 'two';

            return $handler->handle($request);
        };
        $three = function ($request, $handler) use (&$log) {
            $log[] = 'three';

            return $handler->handle($request);
        };
        $this->queue->add($one)->add($two)->add($three);

		$runner = new MiddlewareRunner();
        $result = $runner->run($this->queue, $this->request);
        expect($result)->toBeAnInstanceOf(Response::class);

		expect($log)->toBe(['one', 'two', 'three']);
	});

	it("Groupe de middlewares", function (): void {
		$log = [];
        $one = function ($request, $handler) use (&$log) {
            $log[] = 'one';

            return $handler->handle($request);
        };
        $two = function ($request, $handler) use (&$log) {
            $log[] = 'two';

            return $handler->handle($request);
        };
        $three = function ($request, $handler) use (&$log) {
            $log[] = 'three';

            return $handler->handle($request);
        };
        $four = function ($request, $handler) use (&$log) {
            $log[] = 'four';

            return $handler->handle($request);
        };

		$groups = [
			'web' => [$two, $three],
			'api' => [$four],
		];

        $this->queue->groups($groups)->add('web')->add($one)->add(['api']);

		$runner = new MiddlewareRunner();
        $result = $runner->run($this->queue, $this->request);
        expect($result)->toBeAnInstanceOf(Response::class);

		expect($log)->toBe(['two', 'three', 'one', 'four']);
	});
});
