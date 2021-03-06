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

use BlitzPHP\Loader\Services;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements RequestHandlerInterface
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @var int
     */
    private $index = 0;

    /**
     * Contructor
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Ajoute un middleware a la chaine d'execution
     *
     * @param array|callable|object|string $middlewares
     */
    public function add($middlewares): self
    {
        if (! is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        foreach ($middlewares as $middleware) {
            $this->append($middleware);
        }

        return $this;
    }

    /**
     * Ajoute un middleware en bout de chaine
     *
     * @param callable|object|string $middleware
     */
    public function append($middleware): self
    {
        $middleware          = $this->makeMiddleware($middleware);
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Ajoute un middleware en debut de chaine
     *
     * @param callable|object|string $middleware
     */
    public function prepend($middleware): self
    {
        $middleware = $this->makeMiddleware($middleware);
        array_unshift($this->middlewares, $middleware);

        return $this;
    }

    /**
     * insert un middleware a une position donnee
     *
     * @param callable|object|string $middleware
     * @alias insertAt
     */
    public function insert(int $index, $middleware): self
    {
        return $this->insertAt($index, $middleware);
    }

    /**
     * Ins??rez un middleware appelable ?? un index sp??cifique.
     *
     * Si l'index existe d??j??, le nouvel appelable sera ins??r??,
     * et l'??l??ment existant sera d??cal?? d'un indice sup??rieur.
     *
     * @param int                    $index      La position o?? le middleware doit ??tre ins??rer.
     * @param callable|object|string $middleware Le middleware ?? inserer.
     */
    public function insertAt(int $index, $middleware): self
    {
        $middleware = $this->makeMiddleware($middleware);
        array_splice($this->middlewares, $index, 0, $middleware);

        return $this;
    }

    /**
     * Ins??rez un objet middleware avant la premi??re classe correspondante.
     *
     * Trouve l'index du premier middleware qui correspond ?? la classe fournie,
     * et ins??re l'appelable fourni avant.
     *
     * @param string                 $class      Le nom de classe pour ins??rer le middleware avant.
     * @param callable|object|string $middleware Le middleware ?? inserer.
     *
     * @throws LogicException Si le middleware ?? ins??rer avant n'est pas trouv??.
     */
    public function insertBefore(string $class, $middleware): self
    {
        $found = false;
        $i     = 0;

        foreach ($this->middlewares as $i => $object) {
            if ((is_string($object) && $object === $class) || is_a($object, $class)) {
                $found = true;
                break;
            }
        }
        if ($found) {
            return $this->insertAt($i, $middleware);
        }

        throw new LogicException(sprintf("No middleware matching '%s' could be found.", $class));
    }

    /**
     * Ins??rez un objet middleware apr??s la premi??re classe correspondante.
     *
     * Trouve l'index du premier middleware qui correspond ?? la classe fournie,
     * et ins??re le callback fourni apr??s celui-ci. Si la classe n'est pas trouv??e,
     * cette m??thode se comportera comme add().
     *
     * @param string                 $class      Le nom de classe pour ins??rer le middleware apr??s.
     * @param callable|object|string $middleware Le middleware ?? inserer.
     */
    public function insertAfter(string $class, $middleware): self
    {
        $found = false;
        $i     = 0;

        foreach ($this->middlewares as $i => $object) {
            if ((is_string($object) && $object === $class) || is_a($object, $class)) {
                $found = true;
                break;
            }
        }
        if ($found) {
            return $this->insertAt($i + 1, $middleware);
        }

        return $this->add($middleware);
    }

    /**
     * Execution du middleware
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->getMiddleware();

        if (empty($middleware)) {
            return $this->response;
        }
        if (is_callable($middleware)) {
            return $middleware($request, $this->response, [$this, 'handle']);
        }

        return $middleware->process($request, $this);
    }

    /**
     * Fabrique un middleware
     *
     * @param callable|object|string $middleware
     *
     * @return callable|object
     */
    private function makeMiddleware($middleware)
    {
        if (is_string($middleware)) {
            return Services::container()->get($middleware);
        }

        return $middleware;
    }

    /**
     * Recuperation du middleware actuel
     *
     * @return callable|object|null
     */
    private function getMiddleware()
    {
        $middleware = null;

        if (isset($this->middlewares[$this->index])) {
            $middleware = $this->middlewares[$this->index];
        }
        $this->index++;

        return $middleware;
    }
}
