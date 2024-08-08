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

use BlitzPHP\Http\Request;
use BlitzPHP\Validation\ErrorBag;
use BlitzPHP\View\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShareErrorsFromSession implements MiddlewareInterface
{
    /**
     * Create a new error binder instance.
     */
    public function __construct(protected View $view)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @param Request $request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Si la session courante a une variable "errors" liée à elle,
        // nous partagerons sa valeur avec toutes les instances de vue afin que les vues puissent facilement accéder aux erreurs sans avoir à se lier.
        // Un sac vide est défini lorsqu'il n'y a pas d'erreurs.
        $this->view->share(
            'errors',
            new ErrorBag($request->session()->getFlashdata('errors') ?: [])
        );

        // Le fait de placer les erreurs dans la vue pour chaque vue permet au développeur de supposer que certaines erreurs sont toujours disponibles,
        // ce qui est pratique puisqu'il n'a pas à vérifier continuellement la présence d'erreurs.

        return $handler->handle($request);
    }
}
