<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Middlewares;

use BlitzPHP\Container\Services;
use BlitzPHP\Http\Response;
use BlitzPHP\Utilities\Iterable\Arr;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionFunction;
use ReflectionParameter;

/**
 * Décorez les closure comme middleware PSR-15.
 *
 * Décorer les closure avec les signatures suivantes:
 *
 * ```
 * function (
 *     ServerRequestInterface $request,
 *     RequestHandlerInterface $handler
 * ): ResponseInterface
 *
 * function (
 *     ServerRequestInterface $request,
 *     ResponseInterface $response,
 * 	   Closure $next
 * ): ResponseInterface
 * ```
 *
 * tel qu'il fonctionnera comme PSR-15 middleware.
 */
class ClosureDecorator implements MiddlewareInterface
{
    /**
     * Constructor
     */
    public function __construct(protected Closure $callable, protected ?Response $response = null)
    {
		$this->response = $response ?: Services::response();
    }

    /**
     * Exécutez le callable lors d'une request de serveur entrante.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $reflector = new ReflectionFunction($this->callable);

        $parameters = collect($reflector->getParameters())->map(fn(ReflectionParameter $p) => $p->getName())->all();

        if (Arr::contains($parameters, ['request', 'response', 'next'])) {
            return ($this->callable)($request, $this->response, [$handler, 'handle']);
        } else if (Arr::contains($parameters, ['request', 'handler'])) {
            return ($this->callable)($request, $handler);
        } else {
            return $handler->handle($request);
        }
    }

    /**
     * @internal
     */
    public function getCallable(): Closure
    {
        return $this->callable;
    }
}
