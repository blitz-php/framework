<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Utilities\Iterable;

use ArrayAccess;
use ArrayIterator;
use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Contracts\Support\Enumerable;
use BlitzPHP\Traits\EnumeratesValues;
use BlitzPHP\Traits\Macroable;
use BlitzPHP\Utilities\String\Stringable;
use Closure;
use stdClass;
use Traversable;

/** @phpstan-consistent-constructor */
class Collection implements ArrayAccess, Enumerable
{
    use Macroable;
    use EnumeratesValues;

    /**
     * Les éléments contenus dans la collection.
     */
    protected array $items = [];

    /**
     * Création d'une nouvelle collection.
     *
     * @param mixed $items
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * {@inheritDoc}
     */
    public static function range($from, $to)
    {
        return new static(range($from, $to));
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Obtenez une collection paresseuse pour les articles de cette collection.
     */
    public function lazy(): LazyCollection
    {
        return new LazyCollection($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function avg($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        $items = $this->map(fn ($value) => $callback($value))->filter(fn ($value) => null !== $value);

        if ($count = $items->count()) {
            return $items->sum() / $count;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function median($key = null)
    {
        $values = (isset($key) ? $this->pluck($key) : $this)
            ->filter(static fn ($item) => null !== $item)
            ->sort()->values();

        if (0 === $count = $values->count()) {
            return;
        }

        $middle = (int) ($count / 2);

        if ($count % 2) {
            return $values->get($middle);
        }

        return static::make([$values->get($middle - 1), $values->get($middle)])->average();
    }

    /**
     * {@inheritDoc}
     */
    public function mode($key = null): ?array
    {
        if ($this->count() === 0) {
            return null;
        }

        $collection = isset($key) ? $this->pluck($key) : $this;

        $counts = new static();

        $collection->each(fn ($value) => $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1);

        $sorted = $counts->sort();

        $highestValue = $sorted->last();

        return $sorted->filter(fn ($value) => $value === $highestValue)->sort()->keys()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function collapse()
    {
        return new static(Arr::collapse($this->items));
    }

    /**
     * {@inheritDoc}
     */
    public function some($key, mixed $operator = null, mixed $value = null): bool
    {
        return $this->contains(...func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function contains($key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            if ($this->useAsCallable($key)) {
                $placeholder = new stdClass();

                return $this->first($key, $placeholder) !== $placeholder;
            }

            return in_array($key, $this->items, true);
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * {@inheritDoc}
     */
    public function containsStrict($key, mixed $value = null): bool
    {
        if (func_num_args() === 2) {
            return $this->contains(static fn ($item) => Arr::get($item, $key) === $value);
        }

        if ($this->useAsCallable($key)) {
            return null !== $this->first($key);
        }

        return in_array($key, $this->items, true);
    }

    /**
     * {@inheritDoc}
     */
    public function doesntContain(mixed $key, ?string $operator = null, mixed $value = null): bool
    {
        return ! $this->contains(...func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function crossJoin(...$lists)
    {
        return new static(Arr::crossJoin(
            $this->items,
            ...array_map([$this, 'getArrayableItems'], $lists)
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function diff($items)
    {
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }

    /**
     * {@inheritDoc}
     */
    public function diffUsing($items, callable $callback)
    {
        return new static(array_udiff($this->items, $this->getArrayableItems($items), $callback));
    }

    /**
     * {@inheritDoc}
     */
    public function diffAssoc($items)
    {
        return new static(array_diff_assoc($this->items, $this->getArrayableItems($items)));
    }

    /**
     * {@inheritDoc}
     */
    public function diffAssocUsing($items, callable $callback)
    {
        return new static(array_diff_uassoc($this->items, $this->getArrayableItems($items), $callback));
    }

    /**
     * {@inheritDoc}
     */
    public function diffKeys($items)
    {
        return new static(array_diff_key($this->items, $this->getArrayableItems($items)));
    }

    /**
     * {@inheritDoc}
     */
    public function diffKeysUsing($items, callable $callback)
    {
        return new static(array_diff_ukey($this->items, $this->getArrayableItems($items), $callback));
    }

    /**
     * {@inheritDoc}
     */
    public function duplicates($callback = null, bool $strict = false)
    {
        $items = $this->map($this->valueRetriever($callback));

        $uniqueItems = $items->unique(null, $strict);

        $compare = $this->duplicateComparator($strict);

        $duplicates = new static();

        foreach ($items as $key => $value) {
            if ($uniqueItems->isNotEmpty() && $compare($value, $uniqueItems->first())) {
                $uniqueItems->shift();
            } else {
                $duplicates[$key] = $value;
            }
        }

        return $duplicates;
    }

    /**
     * {@inheritDoc}
     */
    public function duplicatesStrict($callback = null)
    {
        return $this->duplicates($callback, true);
    }

    /**
     * Obtenez la fonction de comparaison pour détecter les doublons.
     *
     * @return callable(mixed $a, mixed $b): bool
     */
    protected function duplicateComparator(bool $strict): Closure
    {
        if ($strict) {
            return static fn ($a, $b) => $a === $b;
        }

        return static fn ($a, $b) => $a === $b;
    }

    /**
     * {@inheritDoc}
     */
    public function every($key, ?string $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            $callback = $this->valueRetriever($key);

            foreach ($this->items as $k => $v) {
                if (! $callback($v, $k)) {
                    return false;
                }
            }

            return true;
        }

        return $this->every($this->operatorForWhere(...func_get_args()));
    }

    /**
     * {@inheritDoc}
     */
    public function except($keys)
    {
        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        } elseif (! is_array($keys)) {
            $keys = func_get_args();
        }

        return new static(Arr::except($this->items, $keys));
    }

    /**
     * {@inheritDoc}
     */
    public function filter(?callable $callback = null)
    {
        if ($callback) {
            return new static(Arr::where($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    /**
     * {@inheritDoc}
     */
    public function when(bool $value, ?callable $callback = null, ?callable $default = null)
    {
        if ($value) {
            return $callback($this, $value);
        }
        if ($default) {
            return $default($this, $value);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function first(?callable $callback = null, $default = null)
    {
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function flatten(int $depth = INF)
    {
        return new static(Arr::flatten($this->items, $depth));
    }

    /**
     * {@inheritDoc}
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * Supprimer un élément de la collection par clé.
     */
    public function forget(array|string $keys): self
    {
        foreach ((array) $keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get(int|string|null $key, mixed $default = null): mixed
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }

        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * Obtenez un élément de la collection par clé ou ajoutez-le à la collection s'il n'existe pas.
     */
    public function getOrPut(mixed $key, mixed $value): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        $this->offsetSet($key, $value = value($value));

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        if (! $this->useAsCallable($groupBy) && is_array($groupBy)) {
            $nextGroups = $groupBy;

            $groupBy = array_shift($nextGroups);
        }

        $groupBy = $this->valueRetriever($groupBy);

        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);

            if (! is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }

            foreach ($groupKeys as $groupKey) {
                $groupKey = match (true) {
                    is_bool($groupKey)              => (int) $groupKey,
                    $groupKey instanceof Stringable => (string) $groupKey,
                    default                         => $groupKey,
                };

                if (! array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static();
                }

                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }

        $result = new static($results);

        if (! empty($nextGroups)) {
            return $result->map->groupBy($nextGroups, $preserveKeys);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function keyBy($keyBy): self
    {
        $keyBy = $this->valueRetriever($keyBy);

        $results = [];

        foreach ($this->items as $key => $item) {
            $resolvedKey = $keyBy($item, $key);

            if (is_object($resolvedKey)) {
                $resolvedKey = (string) $resolvedKey;
            }

            $results[$resolvedKey] = $item;
        }

        return new static($results);
    }

    /**
     * {@inheritDoc}
     *
     * @param array|mixed $key
     */
    public function has(mixed $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if (! $this->offsetExists($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @param array|mixed $key
     */
    public function hasAny(mixed $key): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if ($this->has($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function implode(callable|string $value, ?string $glue = null): string
    {
        if ($this->useAsCallable($value)) {
            return implode($glue ?? '', $this->map($value)->all());
        }

        $first = $this->first();

        if (is_array($first) || (is_object($first) && ! $first instanceof Stringable)) {
            return implode($glue ?? '', $this->pluck($value)->all());
        }

        return implode($value ?? '', $this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function intersect($items)
    {
        return new static(array_intersect($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Intersecter la collection avec les éléments donnés, en utilisant le callback.
     *
     * @param Arrayable|iterable          $items
     * @param callable(mixed, mixed): int $callback
     *
     * @return static
     */
    public function intersectUsing($items, callable $callback): self
    {
        return new static(array_uintersect($this->items, $this->getArrayableItems($items), $callback));
    }

    /**
     * Croisez la collection avec les éléments donnés avec une vérification d'index supplémentaire.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function intersectAssoc($items): self
    {
        return new static(array_intersect_assoc($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Intersect the collection with the given items with additional index check, using the callback.
     *
     * @param iterable<array-key, TValue>|\Tightenco\Collect\Contracts\Support\Arrayable<array-key, TValue> $items
     * @param callable(TValue, TValue): int                                                                 $callback
     *
     * @return static
     */
    public function intersectAssocUsing($items, callable $callback)
    {
        return new static(array_intersect_uassoc($this->items, $this->getArrayableItems($items), $callback));
    }

    /**
     * {@inheritDoc}
     */
    public function intersectByKeys($items)
    {
        return new static(array_intersect_key(
            $this->items,
            $this->getArrayableItems($items)
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function containsOneItem(): bool
    {
        return $this->count() === 1;
    }

    /**
     * {@inheritDoc}
     */
    public function join(string $glue, string $finalGlue = ''): string
    {
        if ($finalGlue === '') {
            return $this->implode($glue);
        }

        if (0 === $count = $this->count()) {
            return '';
        }

        if ($count === 1) {
            return $this->last();
        }

        $collection = new static($this->items);

        $finalItem = $collection->pop();

        return $collection->implode($glue) . $finalGlue . $finalItem;
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * {@inheritDoc}
     */
    public function last(?callable $callback = null, $default = null): mixed
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function pluck($value, ?string $key = null)
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    /**
     * {@inheritDoc}
     */
    public function map(callable $callback)
    {
        return new static(Arr::map($this->items, $callback));
    }

    /**
     * {@inheritDoc}
     */
    public function mapToDictionary(callable $callback)
    {
        $dictionary = [];

        foreach ($this->items as $key => $item) {
            $pair = $callback($item, $key);

            $key = key($pair);

            $value = reset($pair);

            if (! isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }

            $dictionary[$key][] = $value;
        }

        return new static($dictionary);
    }

    /**
     * {@inheritDoc}
     */
    public function mapWithKeys(callable $callback)
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);

            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return new static($result);
    }

    /**
     * {@inheritDoc}
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * {@inheritDoc}
     */
    public function mergeRecursive($items)
    {
        return new static(array_merge_recursive($this->items, $this->getArrayableItems($items)));
    }

    /**
     * {@inheritDoc}
     */
    public function combine($values)
    {
        return new static(array_combine($this->all(), $this->getArrayableItems($values)));
    }

    /**
     * {@inheritDoc}
     */
    public function union($items)
    {
        return new static($this->items + $this->getArrayableItems($items));
    }

    /**
     * {@inheritDoc}
     */
    public function nth($step, $offset = 0)
    {
        $new = [];

        $position = 0;

        foreach ($this->items as $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }

            $position++;
        }

        return new static($new);
    }

    /**
     * {@inheritDoc}
     */
    public function only($keys)
    {
        if (null === $keys) {
            return new static($this->items);
        }

        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        return new static(Arr::only($this->items, $keys));
    }

    /**
     * Obtenez et supprimez les N derniers éléments de la collection.
     *
     * @return mixed|static|null
     */
    public function pop(int $count = 1)
    {
        if ($count === 1) {
            return array_pop($this->items);
        }

        if ($this->isEmpty()) {
            return new static();
        }

        $results = [];

        $collectionCount = $this->count();

        foreach (range(1, min($count, $collectionCount)) as $item) {
            $results[] = array_pop($this->items);
        }

        return new static($results);
    }

    /**
     * Poussez un élément au début de la collection.
     */
    public function prepend(mixed $value, mixed $key = null): self
    {
        $this->items = Arr::prepend($this->items, ...func_get_args());

        return $this;
    }

    /**
     * Poussez un élément à la fin de la collection.
     */
    public function push(...$values): self
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function concat(iterable $source)
    {
        $result = new static($this);

        foreach ($source as $item) {
            $result->push($item);
        }

        return $result;
    }

    /**
     * Obtenir et supprimer un élément de la collection.
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * Mettre un élément dans la collection par clé.
     */
    public function put(string $key, mixed $value): self
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function random($number = null)
    {
        if (null === $number) {
            return Arr::random($this->items);
        }

        if (is_callable($number)) {
            return new static(Arr::random($this->items, $number($this)));
        }

        return new static(Arr::random($this->items, $number));
    }

    /**
     * {@inheritDoc}
     */
    public function replace($items)
    {
        return new static(array_replace($this->items, $this->getArrayableItems($items)));
    }

    /**
     * {@inheritDoc}
     */
    public function replaceRecursive($items)
    {
        return new static(array_replace_recursive($this->items, $this->getArrayableItems($items)));
    }

    /**
     * {@inheritDoc}
     */
    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * {@inheritDoc}
     */
    public function search($value, bool $strict = false)
    {
        if (! $this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }

        foreach ($this->items as $key => $item) {
            if ($value($item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Obtenez et supprimez les N premiers éléments de la collection.
     *
     * @return mixed|static<int, mixed>|null
     */
    public function shift(int $count = 1)
    {
        if ($count === 1) {
            return array_shift($this->items);
        }

        if ($this->isEmpty()) {
            return new static();
        }

        $results = [];

        $collectionCount = $this->count();

        foreach (range(1, min($count, $collectionCount)) as $item) {
            $results[] = array_shift($this->items);
        }

        return new static($results);
    }

    /**
     * {@inheritDoc}
     */
    public function shuffle(?int $seed = null)
    {
        return new static(Arr::shuffle($this->items, $seed));
    }

    /**
     * {@inheritDoc}
     */
    public function sliding(int $size = 2, int $step = 1)
    {
        $chunks = floor(($this->count() - $size) / $step) + 1;

        return static::times($chunks, fn ($number) => $this->slice(($number - 1) * $step, $size));
    }

    /**
     * {@inheritDoc}
     */
    public function skip(int $count)
    {
        return $this->slice($count);
    }

    /**
     * {@inheritDoc}
     */
    public function skipUntil($value)
    {
        return new static($this->lazy()->skipUntil($value)->all());
    }

    /**
     * {@inheritDoc}
     */
    public function skipWhile($value)
    {
        return new static($this->lazy()->skipWhile($value)->all());
    }

    /**
     * {@inheritDoc}
     */
    public function slice(int $offset, ?int $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * {@inheritDoc}
     */
    public function split(int $numberOfGroups)
    {
        if ($this->isEmpty()) {
            return new static();
        }

        $groups = new static();

        $groupSize = floor($this->count() / $numberOfGroups);

        $remain = $this->count() % $numberOfGroups;

        $start = 0;

        for ($i = 0; $i < $numberOfGroups; $i++) {
            $size = $groupSize;

            if ($i < $remain) {
                $size++;
            }

            if ($size) {
                $groups->push(new static(array_slice($this->items, $start, $size)));

                $start += $size;
            }
        }

        return $groups;
    }

    /**
     * {@inheritDoc}
     */
    public function splitIn(int $numberOfGroups)
    {
        return $this->chunk(ceil($this->count() / $numberOfGroups));
    }

    /**
     * {@inheritDoc}
     */
    public function sole($key = null, ?string $operator = null, mixed $value = null): mixed
    {
        $filter = func_num_args() > 1
            ? $this->operatorForWhere(...func_get_args())
            : $key;

        $items = $this->unless($filter === null)->filter($filter);

        $count = $items->count();

        if ($count === 0) {
            // throw new ItemNotFoundException;
        }

        if ($count > 1) {
            // throw new MultipleItemsFoundException($count);
        }

        return $items->first();
    }

    /**
     * {@inheritDoc}
     */
    public function firstOrFail($key = null, ?string $operator = null, mixed $value = null): mixed
    {
        $filter = func_num_args() > 1
            ? $this->operatorForWhere(...func_get_args())
            : $key;

        $placeholder = new stdClass();

        $item = $this->first($filter, $placeholder);

        if ($item === $placeholder) {
            // throw new ItemNotFoundException;
        }

        return $item;
    }

    /**
     * {@inheritDoc}
     */
    public function chunk(int $size)
    {
        if ($size <= 0) {
            return new static();
        }

        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * {@inheritDoc}
     */
    public function chunkWhile(callable $callback)
    {
        return new static(
            $this->lazy()->chunkWhile($callback)->mapInto(static::class)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function sort(?callable $callback = null)
    {
        $items = $this->items;

        $callback && is_callable($callback)
            ? uasort($items, $callback)
            : asort($items, $callback ?? SORT_REGULAR);

        return new static($items);
    }

    /**
     * {@inheritDoc}
     */
    public function sortDesc(int $options = SORT_REGULAR)
    {
        $items = $this->items;

        arsort($items, $options);

        return new static($items);
    }

    /**
     * {@inheritDoc}
     */
    public function sortBy($callback, int $options = SORT_REGULAR, bool $descending = false)
    {
        if (is_array($callback) && ! is_callable($callback)) {
            return $this->sortByMany($callback);
        }

        $results = [];

        $callback = $this->valueRetriever($callback);

        // Nous allons d'abord parcourir les éléments et obtenir le comparateur à partir d'une fonction de callback qui nous a été donnée. Ensuite, nous allons trier les valeurs renvoyées et récupérer toutes les valeurs correspondantes pour les clés triées de ce tableau.
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        $descending ? arsort($results, $options)
            : asort($results, $options);

        // Une fois que nous avons trié toutes les clés du tableau, nous les parcourons en boucle et récupérons le modèle correspondant afin de pouvoir définir la liste des éléments sous-jacents sur la version triée. Ensuite, nous renverrons simplement l'instance de collection.
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new static($results);
    }

    /**
     * Triez la collection à l'aide de plusieurs comparaisons.
     *
     * @param  array<int|string, (callable(mixed, mixed): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>  $comparisons
     *
     * @return static
     */
    protected function sortByMany(array $comparisons = [])
    {
        $items = $this->items;

        uasort($items, function ($a, $b) use ($comparisons) {
            foreach ($comparisons as $comparison) {
                $comparison = Arr::wrap($comparison);

                $prop = $comparison[0];

                $ascending = Arr::get($comparison, 1, true) === true
                             || Arr::get($comparison, 1, true) === 'asc';

                if (! is_string($prop) && is_callable($prop)) {
                    $result = $prop($a, $b);
                } else {
                    $values = [Arr::getRecursive($a, $prop), Arr::getRecursive($b, $prop)];

                    if (! $ascending) {
                        $values = array_reverse($values);
                    }

                    $result = $values[0] <=> $values[1];
                }

                if ($result === 0) {
                    continue;
                }

                return $result;
            }
        });

        return new static($items);
    }

    /**
     * {@inheritDoc}
     */
    public function sortByDesc($callback, int $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, true);
    }

    /**
     * {@inheritDoc}
     */
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false)
    {
        $items = $this->items;

        $descending ? krsort($items, $options) : ksort($items, $options);

        return new static($items);
    }

    /**
     * {@inheritDoc}
     */
    public function sortKeysDesc(int $options = SORT_REGULAR)
    {
        return $this->sortKeys($options, true);
    }

    /**
     * {@inheritDoc}
     */
    public function sortKeysUsing(callable $callback)
    {
        $items = $this->items;

        uksort($items, $callback);

        return new static($items);
    }

    /**
     * Splice une partie du tableau de collection sous-jacent.
     *
     * @return static
     */
    public function splice(int $offset, ?int $length = null, array $replacement = []): self
    {
        if (func_num_args() === 1) {
            return new static(array_splice($this->items, $offset));
        }

        return new static(array_splice($this->items, $offset, $length, $replacement));
    }

    /**
     * {@inheritDoc}
     */
    public function take(int $limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * {@inheritDoc}
     */
    public function takeUntil($value)
    {
        return new static($this->lazy()->takeUntil($value)->all());
    }

    /**
     * {@inheritDoc}
     */
    public function takeWhile($value)
    {
        return new static($this->lazy()->takeWhile($value)->all());
    }

    /**
     * Transformez chaque élément de la collection à l'aide d'un callback.
     *
     * @param  callable(mixed $value, int|string $key): mixed  $callback
     */
    public function transform(callable $callback)
    {
        $this->items = $this->map($callback)->all();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function undot()
    {
        return new static(Arr::undot($this->all()));
    }

    /**
     * {@inheritDoc}
     */
    public function unique($key = null, bool $strict = false)
    {
        if (null === $key && $strict === false) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

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
     * {@inheritDoc}
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * {@inheritDoc}
     */
    public function zip($items)
    {
        $arrayableItems = array_map(fn ($items) => $this->getArrayableItems($items), func_get_args());

        $params = array_merge([static fn () => new static(func_get_args()), $this->items], $arrayableItems);

        return new static(array_map(...$params));
    }

    /**
     * {@inheritDoc}
     */
    public function pad(int $size, mixed $value)
    {
        return new static(array_pad($this->items, $size, $value));
    }

    /**
     * Obtenez un itérateur pour les éléments.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function countBy($countBy = null)
    {
        return new static($this->lazy()->countBy($countBy)->all());
    }

    /**
     * Ajoute un élément à la collection.
     */
    public function add(mixed $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Obtenez une instance de collection Support de base à partir de cette collection.
     */
    public function toBase(): self
    {
        return new self($this);
    }

    /**
     * Détermine si un élément existe à une position donnee.
     */
    public function offsetExists(mixed $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * Obtenir un élément se trouvant à une position donnée.
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->items[$key];
    }

    /**
     * Définir l'élément à une position donnée.
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        if (null === $key) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Supprime l'élément à une position donnée.
     */
    public function offsetUnset(mixed $key): void
    {
        unset($this->items[$key]);
    }
}
