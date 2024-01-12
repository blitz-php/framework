<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Exécute la file d'attente middleware et fournit le prochain qui permet de parcourir la file d'attente.
 */
class MiddlewareRunner implements RequestHandlerInterface
{
    /**
     * La file d'attente middleware à exécuter.
     */
    protected MiddlewareQueue $queue;

    /**
     * Gestionnaire de Fallback à utiliser si file d'attente middleware ne génère pas de réponse.
     */
    protected ?RequestHandlerInterface $fallback = null;

    /**
     * {@internal}
     */
    public function run(MiddlewareQueue $queue, ServerRequestInterface $request, ?RequestHandlerInterface $fallback = null): ResponseInterface
    {
        $this->queue    = $queue;
        $this->fallback = $fallback;
        $this->queue->rewind();
		$this->queue->resolveGroups();

        return $this->handle($request);
    }

    /**
     * Execution du middleware
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->queue->valid()) {
            $middleware = $this->queue->current();
            $this->queue->next();

            return $middleware->process($request, $this);
        }

        if ($this->fallback) {
            return $this->fallback->handle($request);
        }

        return $this->queue->response();
    }
}
