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

use BlitzPHP\Container\Services;
use BlitzPHP\Middlewares\BaseMiddleware;
use BlitzPHP\Middlewares\BodyParser;
use BlitzPHP\Middlewares\Cors;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements RequestHandlerInterface
{
    /**
     * Middlewares a executer pour la requete courante
     */
    protected array $middlewares = [];

    /**
     * Index du middleware actuellement executer
     */
    protected int $index = 0;

    /**
     * Aliases des middlewares
     */
    protected array $aliases = [
        'body-parser' => BodyParser::class,
        'cors'        => Cors::class,
    ];

    /**
     * Contructor
     */
    public function __construct(protected Response $response, protected string $path)
    {
    }

    /**
     * Ajoute un alias de middleware
     */
    public function alias(string $alias, callable|object|string $middleware): self
    {
        return $this->aliases([$alias => $middleware]);
    }

    /**
     * Ajoute des alias de middlewares
     */
    public function aliases(array $aliases): self
    {
        $this->aliases = array_merge($this->aliases, $aliases);

        return $this;
    }

    /**
     * Ajoute un middleware a la chaine d'execution
     *
     * @param array|callable|object|string $middlewares
     */
    public function add($middlewares, array $options = []): self
    {
        if (! is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        foreach ($middlewares as $middleware) {
            $this->append($middleware, $options);
        }

        return $this;
    }

    /**
     * Ajoute un middleware en bout de chaine
     *
     * @param callable|object|string $middleware
     */
    public function append($middleware, array $options = []): self
    {
        [$middleware, $options] = $this->getMiddlewareAndOptions($middleware, $options);

        $middleware          = $this->makeMiddleware($middleware);
        $this->middlewares[] = compact('middleware', 'options');

        return $this;
    }

    /**
     * Ajoute un middleware en debut de chaine
     *
     * @param callable|object|string $middleware
     */
    public function prepend($middleware, array $options = []): self
    {
        [$middleware, $options] = $this->getMiddlewareAndOptions($middleware, $options);

        $middleware = $this->makeMiddleware($middleware);
        array_unshift($this->middlewares, compact('middleware', 'options'));

        return $this;
    }

    /**
     * insert un middleware a une position donnee
     *
     * @param callable|object|string $middleware
     *
     * @alias insertAt
     */
    public function insert(int $index, $middleware, array $options = []): self
    {
        return $this->insertAt($index, $middleware, $options);
    }

    /**
     * Insérez un middleware appelable à un index spécifique.
     *
     * Si l'index existe déjà, le nouvel appelable sera inséré,
     * et l'élément existant sera décalé d'un indice supérieur.
     *
     * @param int                    $index      La position où le middleware doit être insérer.
     * @param callable|object|string $middleware Le middleware à inserer.
     */
    public function insertAt(int $index, $middleware, array $options = []): self
    {
        [$middleware, $options] = $this->getMiddlewareAndOptions($middleware, $options);

        $middleware = $this->makeMiddleware($middleware);
        array_splice($this->middlewares, $index, 0, compact('middleware', 'options'));

        return $this;
    }

    /**
     * Insérez un objet middleware avant la première classe correspondante.
     *
     * Trouve l'index du premier middleware qui correspond à la classe fournie,
     * et insère l'appelable fourni avant.
     *
     * @param string                 $class      Le nom de classe pour insérer le middleware avant.
     * @param callable|object|string $middleware Le middleware à inserer.
     *
     * @throws LogicException Si le middleware à insérer avant n'est pas trouvé.
     */
    public function insertBefore(string $class, $middleware, array $options = []): self
    {
        $found = false;
        $i     = 0;

        if (array_key_exists($class, $this->aliases)) {
            $class = $this->aliases[$class];
        }

        foreach ($this->middlewares as $i => $object) {
            if ((is_string($object) && $object === $class) || is_a($object, $class)) {
                $found = true;
                break;
            }
        }

        if ($found) {
            return $this->insertAt($i, $middleware, $options);
        }

        throw new LogicException(sprintf("No middleware matching '%s' could be found.", $class));
    }

    /**
     * Insérez un objet middleware après la première classe correspondante.
     *
     * Trouve l'index du premier middleware qui correspond à la classe fournie,
     * et insère le callback fourni après celui-ci. Si la classe n'est pas trouvée,
     * cette méthode se comportera comme add().
     *
     * @param string                 $class      Le nom de classe pour insérer le middleware après.
     * @param callable|object|string $middleware Le middleware à inserer.
     */
    public function insertAfter(string $class, $middleware, array $options = []): self
    {
        $found = false;
        $i     = 0;

        if (array_key_exists($class, $this->aliases)) {
            $class = $this->aliases[$class];
        }

        foreach ($this->middlewares as $i => $object) {
            if ((is_string($object) && $object === $class) || is_a($object, $class)) {
                $found = true;
                break;
            }
        }

        if ($found) {
            return $this->insertAt($i + 1, $middleware, $options);
        }

        return $this->add($middleware, $options);
    }

    /**
     * Execution du middleware
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($processing = $this->getMiddleware())) {
            return $this->response;
        }

        ['middleware' => $middleware, 'options' => $options] = $processing;

        if (empty($middleware)) {
            return $this->response;
        }

        if (isset($options['except']) && $this->pathApplies($this->path, $options['except'])) {
            return $this->handle($request);
        }

        unset($options['except']);

        if (is_callable($middleware)) {
            return $middleware($request, $this->response, [$this, 'handle']);
        }

        if ($middleware instanceof MiddlewareInterface) {
            if ($middleware instanceof BaseMiddleware) {
                $middleware = $middleware->init($options + ['path' => $this->path])->fill($options);
            }

            return $middleware->process($request, $this);
        }

        return $this->response;
    }

    /**
     * Enregistre les middlewares definis dans le gestionnaire des middlewares
     *
     * @internal
     */
    public function register(Request $request)
    {
        $config = (object) config('middlewares');

        $this->aliases($config->aliases);

        foreach ($config->globals as $middleware) {
            $this->add($middleware);
        }

        if (is_callable($build = $config->build)) {
            Services::container()->call($build, [
                'request'    => $request,
                'middleware' => $this,
            ]);
        }
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
        if (is_string($middleware) && array_key_exists($middleware, $this->aliases)) {
            $middleware = $this->aliases[$middleware];
        }

        return is_string($middleware)
            ? Services::container()->get($middleware)
            : $middleware;
    }

    /**
     * Recuperation du middleware actuel
     */
    private function getMiddleware(): array
    {
        $middleware = [];

        if (isset($this->middlewares[$this->index])) {
            $middleware = $this->middlewares[$this->index];
        }

        $this->index++;

        return $middleware;
    }

    /**
     * Recupere les options d'un middlewares de type string
     *
     * @param callable|object|string $middleware
     */
    private function getMiddlewareAndOptions($middleware, array $options = []): array
    {
        if (is_string($middleware)) {
            $parts      = explode(':', $middleware);
            $middleware = array_shift($parts);
            if (isset($parts[0]) && is_string($parts[0])) {
                $options = array_merge($options, explode(',', $parts[0]));
            }
        }

        return [$middleware, $options];
    }

    /**
     * Check paths for match for URI
     */
    private function pathApplies(string $uri, array|string $paths): bool
    {
        // empty path matches all
        if (empty($paths)) {
            return true;
        }

        // make sure the paths are iterable
        if (is_string($paths)) {
            $paths = [$paths];
        }

        // treat each paths as pseudo-regex
        foreach ($paths as $path) {
            // need to escape path separators
            $path = str_replace('/', '\/', trim($path, '/ '));
            // need to make pseudo wildcard real
            $path = strtolower(str_replace('*', '.*', $path));
            // Does this rule apply here?
            if (preg_match('#^' . $path . '$#', $uri, $match) === 1) {
                return true;
            }
        }

        return false;
    }
}
