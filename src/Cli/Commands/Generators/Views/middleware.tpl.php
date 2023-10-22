<@php

namespace {namespace};

use BlitzPHP\Middlewares\BaseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class {class} extends BaseMiddleware implements MiddlewareInterface
{
    /**
     * Traitez une demande de serveur entrante.
     *
     * Traite une demande de serveur entrante afin de produire une réponse.
     * S'il est incapable de produire la réponse lui-même, il peut déléguer au gestionnaire de requêtes fourni le soin de le faire.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //

        return $handler->process($request);
    }
}
