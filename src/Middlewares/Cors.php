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
 * Cors
 *  Middleware cors pour gerer les requetes d'origine croisees
 */
class Cors extends BaseMiddleware implements MiddlewareInterface
{
    protected $config = [
        'AllowOrigin'      => true,
        'AllowCredentials' => true,
        'AllowMethods'     => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        'AllowHeaders'     => true,
        'ExposeHeaders'    => false,
        'MaxAge'           => 86400,                                       // 1 day
    ];

    /**
     * Constructor
     */
    public function init(array $config = []): static
    {
        $this->config = array_merge($this->config, $config);

        return parent::init($config);
    }

    /**
     * Modifie le MaxAge
     *
     * @param float|int $maxAge
     */
    public function setMaxAge($maxAge): self
    {
        $this->config['MaxAge'] = $maxAge;

        return $this;
    }

    /**
     * Modifie les entetes exposes
     *
     * @param bool|string|string[] $exposeHeaders
     */
    public function setExposeHeaders($exposeHeaders): self
    {
        $this->config['ExposeHeaders'] = $exposeHeaders;

        return $this;
    }

    /**
     * Modifie les entetes autorises
     *
     * @param bool|string|string[] $headers
     */
    public function setHeaders($headers): self
    {
        $this->config['AllowHeaders'] = $headers;

        return $this;
    }

    /**
     * Modifie les methodes autorisees
     *
     * @param string|string[] $methods
     */
    public function setMethods($methods): self
    {
        $this->config['AlloMethods'] = $methods;

        return $this;
    }

    /**
     * Defini si on doit utiliser les informations d'identifications ou pas
     */
    public function setCredentials(bool $credentials): self
    {
        $this->config['AllowCredentials'] = $credentials;

        return $this;
    }

    /**
     * Modifie les origines autorisees
     *
     * @param bool|string|string[] $origin
     */
    public function setOrigin($origin): self
    {
        $this->config['AllowOrigin'] = $origin;

        return $this;
    }

    /**
     * Execution du middleware
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($request->getHeaderLine('Origin')) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $this->_allowOrigin($request))
                ->withHeader('Access-Control-Allow-Credentials', $this->_allowCredentials())
                ->withHeader('Access-Control-Max-Age', $this->_maxAge())
                ->withHeader('Access-Control-Expose-Headers', $this->_exposeHeaders());

            if (strtoupper($request->getMethod()) === 'OPTIONS') {
                $response = $response
                    ->withHeader('Access-Control-Allow-Headers', $this->_allowHeaders($request))
                    ->withHeader('Access-Control-Allow-Methods', $this->_allowMethods())
                    ->withStatus(200);
            }
        }

        return $response;
    }

    /**
     * Recupere les origines autorisees
     */
    private function _allowOrigin(ServerRequestInterface $request)
    {
        $allowOrigin = $this->config['AllowOrigin'];
        $origin      = $request->getHeaderLine('Origin');

        if ($allowOrigin === true || $allowOrigin === '*') {
            return $origin;
        }

        if (is_array($allowOrigin)) {
            $origin = (array) $origin;

            foreach ($origin as $o) {
                if (in_array($o, $allowOrigin, true)) {
                    return $origin;
                }
            }

            return '';
        }

        return (string) $allowOrigin;
    }

    /**
     * Autorise t-on les identifications ?
     */
    private function _allowCredentials(): string
    {
        return ($this->config['AllowCredentials']) ? 'true' : 'false';
    }

    /**
     * Recupere les methodes autorisees
     */
    private function _allowMethods(): string
    {
        return implode(', ', (array) $this->config['AllowMethods']);
    }

    /**
     * Recupere les entetes autorises
     */
    private function _allowHeaders(ServerRequestInterface $request): string
    {
        $allowHeaders = $this->config['AllowHeaders'];

        if ($allowHeaders === true) {
            return $request->getHeaderLine('Access-Control-Request-Headers');
        }

        return implode(', ', (array) $allowHeaders);
    }

    /**
     * Recupere les entetes exposes par l'application
     */
    private function _exposeHeaders(): string
    {
        $exposeHeaders = $this->config['ExposeHeaders'];

        if (is_string($exposeHeaders) || is_array($exposeHeaders)) {
            return implode(', ', (array) $exposeHeaders);
        }

        return '';
    }

    /**
     * Recupere la duree de mise en cache des donnees
     */
    private function _maxAge(): string
    {
        $maxAge = (string) $this->config['MaxAge'];

        return ($maxAge) ?: '0';
    }
}
