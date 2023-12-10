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

use BlitzPHP\Container\Container;
use BlitzPHP\Middlewares\BaseMiddleware;
use BlitzPHP\Middlewares\ClosureDecorator;
use Closure;
use Countable;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use Psr\Http\Server\MiddlewareInterface;
use SeekableIterator;

class MiddlewareQueue implements Countable, SeekableIterator
{
    /**
     * Middlewares a executer pour la requete courante
     *
     * @var array<int, mixed>
     */
    protected array $queue = [];

    /**
     * Index du middleware actuellement executer
     */
    protected int $position = 0;

    /**
     * Aliases des middlewares
     */
    protected array $aliases = [];

    /**
     * Constructor
     *
     * @param array $middleware Liste des middlewares initiaux
     */
    public function __construct(protected Container $container, array $middleware = [], protected ?Request $request = null, protected ?Response $response = null)
    {
        $this->queue    = $middleware;
        $this->request  = $request ?: $this->container->get(Request::class);
        $this->response = $response ?: $this->container->get(Response::class);
    }

    /**
     * Ajoute un alias de middleware
     */
    public function alias(string $alias, Closure|MiddlewareInterface|string $middleware): static
    {
        return $this->aliases([$alias => $middleware]);
    }

    /**
     * Ajoute des alias de middlewares
     *
     * @param array<string, Closure|MiddlewareInterface|string> $aliases
     */
    public function aliases(array $aliases): static
    {
        $this->aliases = array_merge($this->aliases, $aliases);

        return $this;
    }

    /**
     * Ajoute un middleware a la chaine d'execution
     */
    public function add(array|Closure|MiddlewareInterface|string $middleware): static
    {
        if (is_array($middleware)) {
            $this->queue = array_merge($this->queue, $middleware);

            return $this;
        }
        $this->queue[] = $middleware;

        return $this;
    }

    /**
     * Alias pour MiddlewareQueue::add().
     *
     * @see MiddlewareQueue::add()
     */
    public function push(array|Closure|MiddlewareInterface|string $middleware): static
    {
        return $this->add($middleware);
    }

    /**
     * Ajoute un middleware en bout de chaine
     *
     * Alias pour MiddlewareQueue::add().
     *
     * @see MiddlewareQueue::add()
     */
    public function append(array|Closure|MiddlewareInterface|string $middleware): static
    {
        return $this->add($middleware);
    }

    /**
     * Ajoute un middleware en debut de chaine
     */
    public function prepend(array|Closure|MiddlewareInterface|string $middleware): static
    {
        if (is_array($middleware)) {
            $this->queue = array_merge($middleware, $this->queue);

            return $this;
        }
        array_unshift($this->queue, $middleware);

        return $this;
    }

    /**
     * insert un middleware a une position donnee.
     *
     * Alias pour MiddlewareQueue::add().
     *
     * @param int $index La position où le middleware doit être insérer.
     *
     * @see MiddlewareQueue::add()
     */
    public function insert(int $index, Closure|MiddlewareInterface|string $middleware): static
    {
        return $this->insertAt($index, $middleware);
    }

    /**
     * Insérez un middleware appelable à un index spécifique.
     *
     * Si l'index existe déjà, le nouvel appelable sera inséré,
     * et l'élément existant sera décalé d'un indice supérieur.
     *
     * @param int $index La position où le middleware doit être insérer.
     */
    public function insertAt(int $index, Closure|MiddlewareInterface|string $middleware): static
    {
        array_splice($this->queue, $index, 0, [$middleware]);

        return $this;
    }

    /**
     * Insérez un objet middleware avant la première classe correspondante.
     *
     * Trouve l'index du premier middleware qui correspond à la classe fournie,
     * et insère le middleware fourni avant.
     *
     * @param string $class Le nom de classe pour insérer le middleware avant.
     *
     * @throws LogicException Si le middleware à insérer avant n'est pas trouvé.
     */
    public function insertBefore(string $class, Closure|MiddlewareInterface|string $middleware): static
    {
        $found = false;
        $i     = 0;

        if (array_key_exists($class, $this->aliases) && is_string($this->aliases[$class])) {
            $class = $this->aliases[$class];
        }

        foreach ($this->queue as $i => $object) {
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
     * Insérez un objet middleware après la première classe correspondante.
     *
     * Trouve l'index du premier middleware qui correspond à la classe fournie,
     * et insère le callback fourni après celui-ci. Si la classe n'est pas trouvée,
     * cette méthode se comportera comme add().
     *
     * @param string $class Le nom de classe pour insérer le middleware après.
     */
    public function insertAfter(string $class, Closure|MiddlewareInterface|string $middleware): static
    {
        $found = false;
        $i     = 0;

        if (array_key_exists($class, $this->aliases) && is_string($this->aliases[$class])) {
            $class = $this->aliases[$class];
        }

        foreach ($this->queue as $i => $object) {
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
     * Obtenir le nombre de couches middleware connectés.
     */
    public function count(): int
    {
        return count($this->queue);
    }

    /**
     * {@inheritDoc}
     */
    public function seek(int $position): void
    {
        if (! isset($this->queue[$position])) {
            throw new OutOfBoundsException(sprintf('Invalid seek position (%s).', $position));
        }

        $this->position = $position;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     *  {@inheritDoc}
     */
    public function current(): MiddlewareInterface
    {
        if (! isset($this->queue[$this->position])) {
            throw new OutOfBoundsException(sprintf('Position actuelle non valide (%s).', $this->position));
        }

        if ($this->queue[$this->position] instanceof MiddlewareInterface) {
            return $this->queue[$this->position];
        }

        return $this->queue[$this->position] = $this->resolve($this->queue[$this->position]);
    }

    /**
     * {@inheritDoc}
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Passe la position actuelle au middleware suivant.
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * Vérifie si la position actuelle est valide.
     */
    public function valid(): bool
    {
        return isset($this->queue[$this->position]);
    }

    /**
     * Enregistre les middlewares definis dans le gestionnaire des middlewares
     *
     * @internal
     */
    public function register(array $config)
    {
        $config += [
            'aliases' => [],
            'globals' => [],
            'build'   => static fn () => null,
        ];

        $this->aliases($config['aliases']);

        foreach ($config['globals'] as $middleware) {
            $this->add($middleware);
        }

        if (is_callable($build = $config['build'])) {
            $this->container->call($build, [
                'request' => $this->request,
                'queue'   => $this,
            ]);
        }
    }

    /**
     * {@internal}
     */
    public function response(): Response
    {
        return $this->response;
    }

    /**
     * Résoudre le nom middleware à une instance de middleware compatible PSR 15.
     *
     * @throws InvalidArgumentException si Middleware introuvable.
     */
    protected function resolve(Closure|MiddlewareInterface|string $middleware): MiddlewareInterface
    {
        if (is_string($middleware)) {
            [$middleware, $options] = explode(':', $middleware) + [1 => null];

            if (isset($this->aliases[$middleware])) {
                $middleware = $this->aliases[$middleware];
            }

            if ($this->container->has($middleware)) {
                $middleware = $this->container->get($middleware);
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Middleware, `%s` n\'a pas été trouvé.',
                    $middleware
                ));
            }

            if ($middleware instanceof BaseMiddleware) {
                $middleware->fill(explode(',', $options))->init($this->request->getPath());
            }
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        return new ClosureDecorator($middleware, $this->response);
    }
}
