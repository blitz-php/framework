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

use BlitzPHP\Cache\ResponseCache;
use BlitzPHP\Container\Services;
use BlitzPHP\Http\Redirection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PageCache implements MiddlewareInterface
{
    public function __construct(private ResponseCache $pageCache)
    {
    }

    /**
     * Vérifie le cache de la page et revient si trouvé.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if (null !== $cachedResponse = $this->pageCache->get($request, Services::response())) {
            return $cachedResponse;
        }

		$response = $handler->handle($request);
        $content  = $response->getBody()->getContents();

		if (! $response instanceof Redirection) {
            // Mettez-le en cache sans remplacer les mesures de performances afin que nous puissions avoir des mises à jour de vitesse en direct en cours de route.
			// Doit être exécuté après les filtres pour conserver les en-têtes de réponse.
            $this->pageCache->make($request, $response->withBody(to_stream($content)));
        }

		return $response->withBody(to_stream($content));
    }
}
