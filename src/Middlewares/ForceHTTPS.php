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
use BlitzPHP\Exceptions\RedirectException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ForceHTTPS implements MiddlewareInterface
{

	/**
     * Forcer l'accès au site sécurisé ?
	 *
	 * Si la valeur de configuration « forceGlobalSecureRequests » est vrai, imposera que toutes
	 * les demandes adressées à ce site soient effectuées via HTTPS.
	 * Redirigera l'utilisateur vers la page actuelle avec HTTPS, ainsi que définira l'en-tête
	 * HTTP Strict Transport Security (HSTS) pour les navigateurs qui le prennent en charge.
     */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if (config('app.force_global_secure_requests') !== true) {
			return $handler->handle($request);
		}

		try {
            force_https(YEAR, $request, Services::redirection());
        } catch (RedirectException $e) {
            return $e->getResponse();
        }
	}
}
