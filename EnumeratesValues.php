<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Traits;

use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Contracts\Support\Enumerable;
use BlitzPHP\Contracts\Support\Jsonable;
use BlitzPHP\Traits\Mixins\HigherOrderCollectionProxy;
use BlitzPHP\Utilities\Iterable\Arr;
use BlitzPHP\Utilities\Iterable\Collection;
use CachingIterator;
use Closure;
use Exception;
use JsonSerializable;
use Kint\Kint;
use Traversable;
use UnexpectedValueException;
use UnitEnum;

/** *
 * @property HigherOrderCollectionProxy $average
 * @property HigherOrderCollectionProxy $avg
 * @property HigherOrderCollectionProxy $contains
 * @property HigherOrderCollectionProxy $doesntContain
 * @property HigherOrderCollectionProxy $each
 * @property HigherOrderCollectionProxy $every
 * @property HigherOrderCollectionProxy $filter
 * @property HigherOrderCollectionProxy $first
 * @property HigherOrderCollectionProxy $flatMap
 * @property HigherOrderCollectionProxy $groupBy
 * @property HigherOrderCollectionProxy $keyBy
 * @property HigherOrderCollectionProxy $map
 * @property HigherOrderCollectionProxy $max
 * @property HigherOrderCollectionProxy $min
 * @property HigherOrderCollectionProxy $partition
 * @property HigherOrderCollectionProxy $reject
 * @property HigherOrderCollectionProxy $skipUntil
 * @property HigherOrderCollectionProxy $skipWhile
 * @property HigherOrderCollectionProxy $some
 * @property HigherOrderCollectionProxy $sortBy
 * @property HigherOrderCollectionProxy $sortByDesc
 * @property HigherOrderCollectionProxy $sum
 * @property HigherOrderCollectionProxy $takeUntil
 * @property HigherOrderCollectionProxy $takeWhile
 * @property HigherOrderCollectionProxy $unique
 * @property HigherOrderCollectionProxy $unless
 * @property HigherOrderCollectionProxy $until
 * @property HigherOrderCollectionProxy $when
 */
trait EnumeratesValues
{
    use Conditionable;

    /**
     * Indique que la représentation sous forme de chaîne de l'objet doit être échappée lorsque __toString est invoqué.
     */
    protected bool $escapeWhenCastingToString = false;

    /**
     * Les méthodes qui peuvent être proxy.
     *
     * @var string[]
     */
    protected static array $proxies = [
        'average',
        'avg',
        'contains',
        'doesntContain',
        'each',
        'every',
        'filter',
        'first',
        'flatMap',
        'groupBy',
        'keyBy',
        'map',
        'max',
        'min',
        'partition',
        'reject',
        'skipUntil',
        'skipWhile',
        'some',
        'sortBy',
        'sortByDesc',
        'sum',
        'takeUntil',
        'takeWhile',
        'unique',
        'unless',
        'until',
        'when',
    ];

    /**
     * Créez une nouvelle instance de collection si la valeur n'en est pas déjà une.
     *
     * @param Arrayable|iterable|null $items
     *
     * @return static
     */
    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * Enveloppez la valeur donnée dans une collection, le cas échéant.
     *
     * @param iterable|mixed $value
     *
     * @return static
     */
    public static function wrap($value)
    {
        return $value instanceof Enumerable
            ? new static($value)
            : new static(Arr::wrap($value));
    }

    /**
     * Obtenez les éléments sous-jacents de la collection donnée, le cas échéant.
     *
     * @param array|static $value
     */
    public static function unwrap($value): array
    {
        return $value instanceof Enumerable ? $value->all() : $value;
    }

    /**
     * Créez une nouvelle instance sans éléments.
     *
     * @return static
     */
    public static function empty()
    {
        return new static([]);
    }

    /**
     * Créez une nouvelle instance en appelant le callback un certain nombre de fois.
     *
     * @return static
     */
    public static function times(int $number, ?callable $callback = null)
    {
        if ($number < 1) {
            return new static();
        }

        return static::range(1, $number)
            ->unless($callback === null)
            ->map($callback);
    }

    /**
     * Alias pour la méthode "avg".
     *
     * @param  (callable(mixed): float|int)|string|null  $callback
     *
     * @return float|int|null
     */
    public function average($callback = null)
    {
        return $this->avg($callback);
    }

    /**
     * Alias pour la méthode "contains".
     *
     * @param  (callable(mixed, mixed): bool)|mixed|string  $key
     */
    public function some($key, mixed $operator = null, mixed $value = null)
    {
        return $this->contains(...func_get_args());
    }

    /**
     * Videz la collection et terminez le script.
     *
     * @param mixed ...$args
     *
     * @return never
     */
    public function dd(...$args)
    {
        $this->dump(...$args);

        exit(1);
    }

    /**
     * Videz la collection.
     *
     * @return $this
     */
    public function dump()
    {
        Collection::make(func_get_args())->push($this->all())->each(function ($item) {
            Kint::dump($item);
        });

        return $this;
    }

    /**
     * Exécutez un callback sur chaque élément.
     *
     * @param callable(mixed, mixed): mixed $callback
     *
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Exécutez un rappel sur chaque bloc d'éléments imbriqué.
     *
     * @return static
     */
    public function eachSpread(callable $callback)
    {
        return $this->each(function ($chunk, $key) use ($callback) {
            $chunk[] = $key;

            return $callback(...$chunk);
        });
    }

    /**
     * Déterminez si tous les éléments réussissent le test de vérité donné.
     *
     * @param  (callable(mixed, mixed): bool)|mixed|string  $key
     */
    public function every($key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            $callback = $this->valueRetriever($key);

            foreach ($this as $k => $v) {
                if (! $callback($v, $k)) {
                    return false;
                }
            }

            return true;
        }

        return $this->every($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Obtenez le premier élément par la paire clé-valeur donnée.
     *
     * @param mixed      $key
     * @param mixed|null $operator
     * @param mixed|null $value
     *
     * @return mixed|null
     */
    public function firstWhere($key, $operator = null, $value = null)
    {
        return $this->first($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Obtenez la valeur d'une clé unique à partir du premier élément correspondant de la collection.
     *
     * @return mixed
     */
    public function value(string $key, mixed $default = null)
    {
        if ($value = $this->firstWhere($key)) {
            return Arr::get($value, $key, $default);
        }

        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * Déterminez si la collection n'est pas vide.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Exécutez une carte sur chaque bloc d'éléments imbriqué.
     *
     * @param callable(mixed): mixed $callback
     *
     * @return static
     */
    public function mapSpread(callable $callback)
    {
        return $this->map(function ($chunk, $key) use ($callback) {
            $chunk[] = $key;

            return $callback(...$chunk);
        });
    }

    /**
     * Exécutez une carte de regroupement sur les éléments.
     *
     * Le callback doit renvoyer un tableau associatif avec une seule paire clé/valeur.
     *
     * @param callable(mixed, mixed): array $callback
     *
     * @return static
     */
    public function mapToGroups(callable $callback)
    {
        $groups = $this->mapToDictionary($callback);

        return $groups->map([$this, 'make']);
    }

    /**
     * Mappez une collection et aplatissez le résultat d'un seul niveau.
     *
     * @param  callable(mixed $key, mixed $value): Collection|array)  $callback
     *
     * @return static
     */
    public function flatMap(callable $callback)
    {
        return $this->map($callback)->collapse();
    }

    /**
     * Mappez les valeurs dans une nouvelle classe.
     *
     * @return static
     */
    public function mapInto(string $class)
    {
        return $this->map(fn ($value, $key) => new $class($value, $key));
    }

    /**
     * Obtenir la valeur minimale d'une clé donnée.
     *
     * @param  (callable(mixed $value):mixed)|string|null  $callback
     * @param mixed|null $callback
     *
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->map(fn ($value) => $callback($value))
            ->filter(fn ($value) => null !== $value)
            ->reduce(fn ($result, $value) => null === $result || $value < $result ? $value : $result);
    }

    /**
     * Obtenir la valeur maximale d'une clé donnée.
     *
     * @param  (callable(mixed $value):mixed)|string|null  $callback
     * @param mixed|null $callback
     *
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(fn ($value) => null !== $value)->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return null === $result || $value > $result ? $value : $result;
        });
    }

    /**
     * "Pagine" la collection en la découpant en une plus petite collection.
     *
     * @return static
     */
    public function forPage(int $page, int $perPage)
    {
        $offset = max(0, ($page - 1) * $perPage);

        return $this->slice($offset, $perPage);
    }

    /**
     * Partitionnez la collection en deux tableaux à l'aide du callback ou de la clé donnés.
     *
     * @param  (callable(mixed $value, mixed $key): bool)|mixed|string  $key
     * @param mixed $key
     *
     * @return static<int<0, 1>, static>
     */
    public function partition($key, ?string $operator = null, mixed $value = null)
    {
        $passed = [];
        $failed = [];

        $callback = func_num_args() === 1
                ? $this->valueRetriever($key)
                : $this->operatorForWhere(...func_get_args());

        foreach ($this as $key => $item) {
            if ($callback($item, $key)) {
                $passed[$key] = $item;
            } else {
                $failed[$key] = $item;
            }
        }

        return new static([new static($passed), new static($failed)]);
    }

    /**
     * Obtenir la somme des valeurs données.
     *
     * @param  (callable(mixed $value): mixed)|string|null  $callback
     * @param mixed|null $callback
     *
     * @return mixed
     */
    public function sum($callback = null)
    {
        $callback = null === $callback
            ? $this->identity()
            : $this->valueRetriever($callback);

        return $this->reduce(fn ($result, $item) => $result + $callback($item), 0);
    }

    /**
     * Appliquez le callback si la collection est vide.
     *
     * @param  (callable($this): mixed)  $callback
     * @param  (callable($this): mixed)|null  $default
     *
     * @return $this|mixed
     */
    public function whenEmpty(callable $callback, ?callable $default = null)
    {
        return $this->when($this->isEmpty(), $callback, $default);
    }

    /**
     * Appliquez le callback si la collection n'est pas vide.
     *
     * @param  (callable($this): mixed)  $callback
     * @param  (callable($this): mixed)|null  $default
     *
     * @return $this|mixed
     */
    public function whenNotEmpty(callable $callback, ?callable $default = null)
    {
        return $this->when($this->isNotEmpty(), $callback, $default);
    }

    /**
     * Appliquez le callback seulement si la collection est vide.
     *
     * @param callable($this): mixed $callback
     * @param  (callable($this): mixed)|null  $default
     *
     * @return $this|mixed
     */
    public function unlessEmpty(callable $callback, ?callable $default = null)
    {
        return $this->whenNotEmpty($callback, $default);
    }

    /**
     * Appliquez le callback seulement si la collection n'est pas vide.
     *
     * @param callable($this): mixed $callback
     * @param  (callable($this): mixed)|null  $default
     *
     * @return $this|mixed
     */
    public function unlessNotEmpty(callable $callback, ?callable $default = null)
    {
        return $this->whenEmpty($callback, $default);
    }

    /**
     * Filtrez les éléments par la paire clé-valeur donnée.
     *
     * @return static
     */
    public function where(string $key, ?string $operator = null, mixed $value = null)
    {
        return $this->filter($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Filtrer les éléments où la valeur de la clé donnée est nulle.
     *
     * @return static
     */
    public function whereNull(?string $key = null)
    {
        return $this->whereStrict($key, null);
    }

    /**
     * Filtre les éléments où la valeur de la clé donnée n'est pas nulle.
     *
     * @return static
     */
    public function whereNotNull(?string $key = null)
    {
        return $this->where($key, '!==', null);
    }

    /**
     * Filtrez les éléments en fonction de la paire clé-valeur donnée à l'aide d'une comparaison stricte.
     *
     * @return static
     */
    public function whereStrict(string $key, mixed $value)
    {
        return $this->where($key, '===', $value);
    }

    /**
     * Filtrez les éléments par la paire clé-valeur donnée.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereIn(string $key, $values, bool $strict = false)
    {
        $values = $this->getArrayableItems($values);

        return $this->filter(fn ($item) => in_array(Arr::get($item, $key), $values, $strict));
    }

    /**
     * Filtrez les éléments en fonction de la paire clé-valeur donnée à l'aide d'une comparaison stricte.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereInStrict(string $key, $values)
    {
        return $this->whereIn($key, $values, true);
    }

    /**
     * Filtrez les éléments de sorte que la valeur de la clé donnée se situe entre les valeurs données.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereBetween(string $key, $values)
    {
        return $this->where($key, '>=', reset($values))->where($key, '<=', end($values));
    }

    /**
     * Filtrez les éléments de sorte que la valeur de la clé donnée ne soit pas comprise entre les valeurs données.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereNotBetween(string $key, $values)
    {
        return $this->filter(
            fn ($item) => Arr::get($item, $key) < reset($values) || Arr::get($item, $key) > end($values)
        );
    }

    /**
     * Filtrez les éléments par la paire clé-valeur donnée.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereNotIn(string $key, $values, bool $strict = false)
    {
        $values = $this->getArrayableItems($values);

        return $this->reject(fn ($item) => in_array(Arr::get($item, $key), $values, $strict));
    }

    /**
     * Filtrez les éléments en fonction de la paire clé-valeur donnée à l'aide d'une comparaison stricte.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereNotInStrict(string $key, $values)
    {
        return $this->whereNotIn($key, $values, true);
    }

    /**
     * Filtrez les éléments, en supprimant tous les éléments qui ne correspondent pas au(x) type(s) donné(s).
     *
     * @template TWhereInstanceOf
     *
     * @param array<class-string>|class-string $type
     *
     * @return static
     */
    public function whereInstanceOf($type)
    {
        return $this->filter(function ($value) use ($type) {
            if (is_array($type)) {
                foreach ($type as $classType) {
                    if ($value instanceof $classType) {
                        return true;
                    }
                }

                return false;
            }

            return $value instanceof $type;
        });
    }

    /**
     * Passez la collection au callback donné et renvoyez le résultat.
     *
     * @param callable($this): mixed $callback
     *
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * Passez la collection dans une nouvelle classe.
     *
     * @param class-string $class
     *
     * @return mixed
     */
    public function pipeInto(string $class)
    {
        return new $class($this);
    }

    /**
     * Passez la collection à travers une série de canaux appelables et renvoyez le résultat.
     *
     * @param callable[] $callbacks
     *
     * @return mixed
     */
    public function pipeThrough(array $callbacks)
    {
        return Collection::make($callbacks)->reduce(
            fn ($carry, $callback) => $callback($carry),
            $this,
        );
    }

    /**
     * Réduisez la collection à une seule valeur.
     *
     * @param  callable(mixed $result, mixed $value, mixed $key): mixed  $callback
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $result = $initial;

        foreach ($this as $key => $value) {
            $result = $callback($result, $value, $key);
        }

        return $result;
    }

    /**
     * Réduisez la collection à plusieurs valeurs agrégées.
     *
     * @throws UnexpectedValueException
     */
    public function reduceSpread(callable $callback, ...$initial): array
    {
        $result = $initial;

        $class_basename = function ($class) {
            $class = is_object($class) ? get_class($class) : $class;

            return basename(str_replace('\\', '/', $class));
        };

        foreach ($this as $key => $value) {
            $result = $callback(...array_merge($result, [$value, $key]));

            if (! is_array($result)) {
                throw new UnexpectedValueException(sprintf(
                    "%s::reduceSpread s'attend à ce que le réducteur renvoie un tableau, mais a obtenu un '%s' à la place.",
                    $class_basename(static::class),
                    gettype($result)
                ));
            }
        }

        return $result;
    }

    /**
     * Créez une collection de tous les éléments qui ne réussissent pas un test de vérité donné.
     *
     * @param  (callable(mixed $value, mixed $key): bool)|bool|mixed  $callback
     * @param mixed $callback
     *
     * @return static
     */
    public function reject($callback = true)
    {
        $useAsCallable = $this->useAsCallable($callback);

        return $this->filter(function ($value, $key) use ($callback, $useAsCallable) {
            return $useAsCallable
                ? ! $callback($value, $key)
                : $value !== $callback;
        });
    }

    /**
     * Passez la collection au rappel donné, puis renvoyez-la.
     *
     * @param callable($this): mixed $callback
     *
     * @return $this
     */
    public function tap(callable $callback)
    {
        $callback($this);

        return $this;
    }

    /**
     * Renvoie uniquement les éléments uniques du tableau de collection.
     *
     * @param  (callable(mixed $value, mixed $key): mixed)|string|null  $key
     * @param mixed|null $key
     *
     * @return static
     */
    public function unique($key = null, bool $strict = false)
    {
        $callback = $this->valueRetriever($key);

        $exists = [];

        return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }

            $exists[] = $id;
        });
    }

    /**
     * Renvoie uniquement les éléments uniques du tableau de collection en utilisant une comparaison stricte.
     *
     * @param  (callable(mixed $value, mixed $key): mixed)|string|null  $key
     * @param mixed|null $key
     *
     * @return static
     */
    public function uniqueStrict($key = null)
    {
        return $this->unique($key, true);
    }

    /**
     * Rassemblez les valeurs dans une collection.
     */
    public function collect(): Collection
    {
        return Collection::make($this->all());
    }

    /**
     * Obtenez la collection d'éléments sous la forme d'un tableau simple.
     */
    public function toArray(): array
    {
        return $this->map(fn ($value) => $value instanceof Arrayable ? $value->toArray() : $value)->all();
    }

    /**
     * Convertissez l'objet en quelque chose de JSON sérialisable.
     */
    public function jsonSerialize(): array
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }
            if ($value instanceof Jsonable) {
                return json_decode($value->toJson(), true);
            }
            if ($value instanceof Arrayable) {
                return $value->toArray();
            }

            return $value;
        }, $this->all());
    }

    /**
     * Obtenez la collection d'éléments au format JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Obtenez une instance de CachingIterator.
     */
    public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING): CachingIterator
    {
        return new CachingIterator($this->getIterator(), $flags);
    }

    /**
     * Convertissez la collection en sa représentation sous forme de chaîne.
     */
    public function __toString(): string
    {
        if (! $this->escapeWhenCastingToString) {
            return $this->toJson();
        }

        if (function_exists('e')) {
            return e($this->toJson());
        }

        if (function_exists('esc')) {
            return esc($this->toJson());
        }

        return $this->toJson();
    }

    /**
     * Indique que la représentation sous forme de chaîne du modèle doit être échappée lorsque __toString est invoqué.
     *
     * @return $this
     */
    public function escapeWhenCastingToString(bool $escape = true)
    {
        $this->escapeWhenCastingToString = $escape;

        return $this;
    }

    /**
     * Ajoutez une méthode à la liste des méthodes proxy.
     */
    public static function proxy(string $method): void
    {
        static::$proxies[] = $method;
    }

    /**
     * Accédez dynamiquement aux proxys de collecte.
     *
     * @throws Exception
     */
    public function __get(string $key): mixed
    {
        if (! in_array($key, static::$proxies, true)) {
            throw new Exception("La propriété [{$key}] n'existe pas sur cette instance de collection.");
        }

        return new HigherOrderCollectionProxy($this, $key);
    }

    /**
     * Tableau de résultats des éléments de Collection ou Arrayable.
     */
    protected function getArrayableItems(mixed $items): array
    {
        if (is_array($items)) {
            return $items;
        }
        if ($items instanceof Enumerable) {
            return $items->all();
        }
        if ($items instanceof Arrayable) {
            return $items->toArray();
        }
        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }
        if ($items instanceof Jsonable) {
            return json_decode($items->toJson(), true);
        }
        if ($items instanceof JsonSerializable) {
            return (array) $items->jsonSerialize();
        }
        if ($items instanceof UnitEnum) {
            return [$items];
        }

        return (array) $items;
    }

    /**
     * Obtenez un callback du vérificateur de l'opérateur.
     *
     * @param callable|string $key
     *
     * @return Closure
     */
    protected function operatorForWhere($key, ?string $operator = null, mixed $value = null)
    {
        if ($this->useAsCallable($key)) {
            return $key;
        }

        if (func_num_args() === 1) {
            $value = true;

            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;

            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = Arr::get($item, $key);

            $strings = array_filter([$retrieved, $value], fn ($value) => is_string($value) || (is_object($value) && method_exists($value, '__toString')));

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) === 1) {
                return in_array($operator, ['!=', '<>', '!=='], true);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved === $value;

                case '!=':
                case '<>':  return $retrieved !== $value;

                case '<':   return $retrieved < $value;

                case '>':   return $retrieved > $value;

                case '<=':  return $retrieved <= $value;

                case '>=':  return $retrieved >= $value;

                case '===': return $retrieved === $value;

                case '!==': return $retrieved !== $value;

                case '<=>': return $retrieved <=> $value;
            }
        };
    }

    /**
     * Détermine si la valeur donnée est appelable, mais pas une chaîne.
     */
    protected function useAsCallable(mixed $value): bool
    {
        return ! is_string($value) && is_callable($value);
    }

    /**
     * Obtenez un rappel de récupération de valeur.
     *
     * @param callable|string|null $value
     */
    protected function valueRetriever($value): callable
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return fn ($item) => Arr::get($item, $value);
    }

    /**
     * Créer une fonction pour vérifier l'égalité d'un élément.
     *
     * @return Closure(mixed $item): bool
     */
    protected function equality(mixed $value): Closure
    {
        return fn ($item) => $item === $value;
    }

    /**
     * Faire une fonction en utilisant une autre fonction, en annulant son résultat.
     */
    protected function negate(Closure $callback): Closure
    {
        return fn (...$params) => ! $callback(...$params);
    }

    /**
     * Créez une fonction qui renvoie ce qui lui est transmis.
     *
     * @return Closure(mixed $value): mixed
     */
    protected function identity(): Closure
    {
        return fn ($value) => $value;
    }
}
