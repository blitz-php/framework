<@php

namespace {namespace};

use BlitzPHP\Http\Request;
use BlitzPHP\Middlewares\BaseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
<?php if ($standard === 'psr15'): ?>
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class {class} extends BaseMiddleware implements MiddlewareInterface
<?php else: ?>

class {class} extends BaseMiddleware
<?php endif; ?>
{
    /**
     * Traitez une demande de serveur entrante.
     *
     * Traite une demande de serveur entrante afin de produire une réponse.
     * S'il est incapable de produire la réponse lui-même, il peut déléguer au gestionnaire de requêtes fourni le soin de le faire.
     *
     * @param Request $request
     */
<?php if ($standard === 'psr15'): ?>
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
<?php else: ?>
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
<?php endif; ?>
    {
        //

<?php if ($standard === 'psr15'): ?>
        return $handler->handle($request);
<?php else: ?>
        return $next($request, $response);
<?php endif; ?>
    }
}
