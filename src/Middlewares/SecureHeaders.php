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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Ajoute les entete de securites communs
 */
class SecureHeaders implements MiddlewareInterface
{
    /**
     * @var array<string, string>
     */
    protected array $headers = [
        // https://owasp.org/www-project-secure-headers/#x-frame-options
        'X-Frame-Options' => 'SAMEORIGIN',

        // https://owasp.org/www-project-secure-headers/#x-content-type-options
        'X-Content-Type-Options' => 'nosniff',

        // https://docs.microsoft.com/en-us/previous-versions/windows/internet-explorer/ie-developer/compatibility/jj542450(v=vs.85)#the-noopen-directive
        'X-Download-Options' => 'noopen',

        // https://owasp.org/www-project-secure-headers/#x-permitted-cross-domain-policies
        'X-Permitted-Cross-Domain-Policies' => 'none',

        // https://owasp.org/www-project-secure-headers/#referrer-policy
        'Referrer-Policy' => 'same-origin',

        // https://owasp.org/www-project-secure-headers/#x-xss-protection
        // Si vous n'avez pas besoin de prendre en charge les navigateurs existants, il est recommandé d'utiliser
        // Content-Security-Policy sans autoriser les scripts en ligne non sécurisés à la place.
        // 'X-XSS-Protection' => '1; mode=block',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        foreach ($this->headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response;
    }
}
