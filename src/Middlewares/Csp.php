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

use BlitzPHP\Exceptions\FrameworkException;
use BlitzPHP\Traits\InstanceConfigTrait;
use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Content Security Policy Middleware
 *
 * ### Options
 *
 * - `script_nonce` Permet d'ajouter une politique de nonce à la directive script-src.
 * - `style_nonce` Permet d'ajouter une politique de nonce à la directive style-src.
 */
class Csp implements MiddlewareInterface
{
    use InstanceConfigTrait;

    /**
     * CSP Builder
     */
    protected CSPBuilder $csp;

    /**
     * Options de configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'script_nonce' => false,
        'style_nonce'  => false,
    ];

    /**
     * Constructor
     *
     * @param array|CSPBuilder     $csp    Objet CSP ou tableau de configuration
     * @param array<string, mixed> $config options de configurations.
     */
    public function __construct(array|CSPBuilder $csp, array $config = [])
    {
        if (! class_exists(CSPBuilder::class)) {
            throw new FrameworkException('Vous devez installer paragonie/csp-builder pour utiliser le middleware Csp.');
        }

        $this->setConfig($config);

        if (! $csp instanceof CSPBuilder) {
            $csp = new CSPBuilder($csp);
        }

        $this->csp = $csp;
    }

    /**
     * Ajoute les nonces (s'ils sont activés) à la requete et applique l'en-tête CSP à la réponse.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->getConfig('script_nonce')) {
            $request = $request->withAttribute('cspScriptNonce', $this->csp->nonce('script-src'));
        }
        if ($this->getconfig('style_nonce')) {
            $request = $request->withAttribute('cspStyleNonce', $this->csp->nonce('style-src'));
        }

        $response = $handler->handle($request);

        return $this->csp->injectCSPHeader($response);
    }
}
