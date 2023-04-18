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

use ArrayIterator;
use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Contracts\Support\Enumerable;
use BlitzPHP\Traits\EnumeratesValues;
use BlitzPHP\Traits\Macroable;
use Closure;
use DateTimeInterface;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use stdClass;
use Traversable;

/**
 * @credit <a href="https://github.com/tighten/collect">Tightenco\Collect\Support\LazyCollection</a>
 */
class LazyCollection implements Enumerable
{
    use EnumeratesValues;
    use Macroable;

    /**
     * The source from which to generate items.
     *
     * @var (Closure(): Generator<int|string, mixed, mixed, void>)|static|array<int|string, mixed>
     */
    public $source;

    /**
     * Create a new lazy collection instance.
     *
     * @param  Arrayable|iterable|(Closure(): Generator<int|string, mixed, mixed, void>)|self<int|string, mixed>|array<int|string, mixed>|null  $source
     */
    public function __construct($source = null)
    {
        if ($source instanceof Closure || $source instanceof self) {
            $this->source = $source;
        } elseif (null === $source) {
            $this->source = static::empty();
        } elseif ($source instanceof Generator) {
            throw new InvalidArgumentException(
                'Les générateurs ne doivent pas être transmis directement à LazyCollection. Au lieu de cela, passez une fonction de générateur.'
            );
        } else {
            $this->source = $this->getArrayableItems($source);
        }
    }

    /**
     *{@inheritDoc}
     *
     * @param  Arrayable|iterable|(Closure(): Generator<int|string, mixed, mixed, void>)|self<int|string, mixed>|array<int|string, mixed>|null  $items
     */
    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * {@inheritDoc}
     *
     * @param int $from
     * @param int $to
     */
    public static function range($from, $to)
    {
        return new static(function () use ($from, $to) {
            if ($from <= $to) {
                for (; $from <= $to; $from++) {
                    yield $from;
                }
            } else {
                for (; $from >= $to; $from--) {
                    yield $from;
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        if (is_array($this->source)) {
            return $this->source;
        }

        return iterator_to_array($this->getIterator());
    }

    /**
     * charge tous les éléments dans une nouvelle collection paresseuse soutenue par un tableau.
     *
     * @return static
     */
    public function eager()
    {
        return new static($this->all());
    }

    /**
     * Cachez les valeurs telles qu'elles sont énumérées.
     *
     * @return static
     */
    public function remember()
    {
        $iterator = $this->getIterator();

        $iteratorIndex = 0;

        $cache = [];

        return new static(function () use ($iterator, &$iteratorIndex, &$cache) {
            for ($index = 0; true; $index++) {
                if (array_key_exists($index, $cache)) {
                    yield $cache[$index][0] => $cache[$index][1];

                    continue;
                }

                if ($iteratorIndex < $index) {
                    $iterator->next();

                    $iteratorIndex++;
                }

                if (! $iterator->valid()) {
                    break;
                }

                $cache[$index] = [$iterator->key(), $iterator->current()];

                yield $cache[$index][0] => $cache[$index][1];
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function avg($callback = null)
    {
        return $this->collect()->avg($callback);
    }

    /**
     * {@inheritDoc}
     */
    public function median($key = null)
    {
        return $this->collect()->median($key);
    }

    /**
     * {@inheritDoc}
     */
    public function mode($key = null): ?array
    {
        return $this->collect()->mode($key);
    }

    /**
     * {@inheritDoc}
     */
    public function collapse()
    {
        return new static(function () {
            foreach ($this as $values) {
                if (is_array($values) || $values instanceof Enumerable) {
                    foreach ($values as $value) {
                        yield $value;
                    }
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function contains($key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1 && $this->useAsCallable($key)) {
            $placeholder = new stdClass();

            /** @var callable $key */
            return $this->first($key, $placeholder) !== $placeholder;
        }

        if (func_num_args() === 1) {
            $needle = $key;

            foreach ($this as $value) {
                if ($value === $needle) {
                    return true;
                }
            }

            return false;
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * {@inheritDoc}
     */
    public function containsStrict($key, mixed $value = null): bool
    {
        if (func_num_args() === 2) {
            return $this->contains(fn ($item) => Arr::getRecursive($item, $key) === $value);
        }

        if ($this->useAsCallable($key)) {
            return null !== $this->first($key);
        }

        foreach ($this as $item) {
            if ($item === $key) {
                return true;
            }
        }

        return false;
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
    public function crossJoin(...$arrays)
    {
        return $this->passthru('crossJoin', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function countBy($countBy = null)
    {
        $countBy = null === $countBy
            ? $this->identity()
            : $this->valueRetriever($countBy);

        return new static(function () use ($countBy) {
            $counts = [];

            foreach ($this as $key => $value) {
                $group = $countBy($value, $key);

                if (empty($counts[$group])) {
                    $counts[$group] = 0;
                }

                $counts[$group]++;
            }

            yield from $counts;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function diff($items)
    {
        return $this->passthru('diff', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function diffUsing($items, callable $callback)
    {
        return $this->passthru('diffUsing', func_get_args());
    }

    /**
     *{@inheritDoc}
     */
    public function diffAssoc($items)
    {
        return $this->passthru('diffAssoc', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function diffAssocUsing($items, callable $callback)
    {
        return $this->passthru('diffAssocUsing', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function diffKeys($items)
    {
        return $this->passthru('diffKeys', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function diffKeysUsing($items, callable $callback)
    {
        return $this->passthru('diffKeysUsing', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function duplicates($callback = null, $strict = false)
    {
        return $this->passthru('duplicates', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function duplicatesStrict($callback = null)
    {
        return $this->passthru('duplicatesStrict', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function except($keys)
    {
        return $this->passthru('except', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function filter(?callable $callback = null)
    {
        if (null === $callback) {
            $callback = fn ($value) => (bool) $value;
        }

        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                if ($callback($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function first(?callable $callback = null, $default = null)
    {
        $iterator = $this->getIterator();

        if (null === $callback) {
            if (! $iterator->valid()) {
                return value($default);
            }

            return $iterator->current();
        }

        foreach ($iterator as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * {@inheritDoc}
     */
    public function flatten(int $depth = INF)
    {
        $instance = new static(function () use ($depth) {
            foreach ($this as $item) {
                if (! is_array($item) && ! $item instanceof Enumerable) {
                    yield $item;
                } elseif ($depth === 1) {
                    yield from $item;
                } else {
                    yield from (new static($item))->flatten($depth - 1);
                }
            }
        });

        return $instance->values();
    }

    /**
     * {@inheritDoc}
     */
    public function flip()
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $value => $key;
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function get(int|string|null $key, mixed $default = null): mixed
    {
        if (null === $key) {
            return null;
        }

        foreach ($this as $outerKey => $outerValue) {
            if ($outerKey === $key) {
                return $outerValue;
            }
        }

        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function groupBy($groupBy, bool $preserveKeys = false)
    {
        return $this->passthru('groupBy', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function keyBy($keyBy)
    {
        return new static(function () use ($keyBy) {
            $keyBy = $this->valueRetriever($keyBy);

            foreach ($this as $key => $item) {
                $resolvedKey = $keyBy($item, $key);

                if (is_object($resolvedKey)) {
                    $resolvedKey = (string) $resolvedKey;
                }

                yield $resolvedKey => $item;
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function has(mixed $key): bool
    {
        $keys  = array_flip(is_array($key) ? $key : func_get_args());
        $count = count($keys);

        foreach ($this as $key => $value) {
            if (array_key_exists($key, $keys) && --$count === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAny(mixed $key): bool
    {
        $keys = array_flip(is_array($key) ? $key : func_get_args());

        foreach ($this as $key => $value) {
            if (array_key_exists($key, $keys)) {
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
        return $this->collect()->implode(...func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function intersect($items)
    {
        return $this->passthru('intersect', func_get_args());
    }

    /**
     * Intersect the collection with the given items, using the callback.
     *
     * @param Arrayable|iterable          $items
     * @param callable(mixed, mixed): int $callback
     *
     * @return static
     */
    public function intersectUsing()
    {
        return $this->passthru('intersectUsing', func_get_args());
    }

    /**
     * Intersect the collection with the given items with additional index check.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function intersectAssoc($items)
    {
        return $this->passthru('intersectAssoc', func_get_args());
    }

    /**
     * Intersect the collection with the given items with additional index check, using the callback.
     *
     * @param Arrayable|iterable          $items
     * @param callable(mixed, mixed): int $callback
     *
     * @return static
     */
    public function intersectAssocUsing($items, callable $callback)
    {
        return $this->passthru('intersectAssocUsing', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function intersectByKeys($items)
    {
        return $this->passthru('intersectByKeys', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        return ! $this->getIterator()->valid();
    }

    /**
     * {@inheritDoc}
     */
    public function containsOneItem(): bool
    {
        return $this->take(2)->count() === 1;
    }

    /**
     * {@inheritDoc}
     */
    public function join(string $glue, string $finalGlue = ''): string
    {
        return $this->collect()->join(...func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $key;
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function last(?callable $callback = null, $default = null): mixed
    {
        $needle = $placeholder = new stdClass();

        foreach ($this as $key => $value) {
            if (null === $callback || $callback($value, $key)) {
                $needle = $value;
            }
        }

        return $needle === $placeholder ? value($default) : $needle;
    }

    /**
     * {@inheritDoc}
     */
    public function pluck($value, ?string $key = null)
    {
        return new static(function () use ($value, $key) {
            [$value, $key] = $this->explodePluckParameters($value, $key);

            foreach ($this as $item) {
                $itemValue = Arr::getRecursive($item, $value);

                if (null === $key) {
                    yield $itemValue;
                } else {
                    $itemKey = Arr::getRecursive($item, $key);

                    if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                        $itemKey = (string) $itemKey;
                    }

                    yield $itemKey => $itemValue;
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function map(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function mapToDictionary(callable $callback)
    {
        return $this->passthru('mapToDictionary', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function mapWithKeys(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield from $callback($value, $key);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function merge($items)
    {
        return $this->passthru('merge', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function mergeRecursive($items)
    {
        return $this->passthru('mergeRecursive', func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * @param  \IteratorAggregate|array|(callable(): Generator)  $values
     */
    public function combine($values)
    {
        return new static(function () use ($values) {
            $values = $this->makeIterator($values);

            $errorMessage = 'Both parameters should have an equal number of elements';

            foreach ($this as $key) {
                if (! $values->valid()) {
                    trigger_error($errorMessage, E_USER_WARNING);

                    break;
                }

                yield $key => $values->current();

                $values->next();
            }

            if ($values->valid()) {
                trigger_error($errorMessage, E_USER_WARNING);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function union($items)
    {
        return $this->passthru('union', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function nth(int $step, int $offset = 0)
    {
        return new static(function () use ($step, $offset) {
            $position = 0;

            foreach ($this->slice($offset) as $item) {
                if ($position % $step === 0) {
                    yield $item;
                }

                $position++;
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function only($keys)
    {
        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        } elseif (null !== $keys) {
            $keys = is_array($keys) ? $keys : func_get_args();
        }

        return new static(function () use ($keys) {
            if (null === $keys) {
                yield from $this;
            } else {
                $keys = array_flip($keys);

                foreach ($this as $key => $value) {
                    if (array_key_exists($key, $keys)) {
                        yield $key => $value;

                        unset($keys[$key]);

                        if (empty($keys)) {
                            break;
                        }
                    }
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function concat(iterable $source)
    {
        return (new static(function () use ($source) {
            yield from $this;

            yield from $source;
        }))->values();
    }

    /**
     * {@inheritDoc}
     *
     * @param int|null $number
     */
    public function random($number = null)
    {
        $result = $this->collect()->random(...func_get_args());

        return null === $number ? $result : new static($result);
    }

    /**
     * {@inheritDoc}
     */
    public function replace($items)
    {
        return new static(function () use ($items) {
            $items = $this->getArrayableItems($items);

            foreach ($this as $key => $value) {
                if (array_key_exists($key, $items)) {
                    yield $key => $items[$key];

                    unset($items[$key]);
                } else {
                    yield $key => $value;
                }
            }

            foreach ($items as $key => $value) {
                yield $key => $value;
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function replaceRecursive($items)
    {
        return $this->passthru('replaceRecursive', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function reverse()
    {
        return $this->passthru('reverse', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function search($value, bool $strict = false)
    {
        /** @var (callable(mixed,int|string): bool) $predicate */
        $predicate = $this->useAsCallable($value)
            ? $value
            : fn ($item) => $strict ? $item === $value : $item === $value;

        foreach ($this as $key => $item) {
            if ($predicate($item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function shuffle(?int $seed = null)
    {
        return $this->passthru('shuffle', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function sliding(int $size = 2, int $step = 1)
    {
        return new static(function () use ($size, $step) {
            $iterator = $this->getIterator();

            $chunk = [];

            while ($iterator->valid()) {
                $chunk[$iterator->key()] = $iterator->current();

                if (count($chunk) === $size) {
                    yield (new static($chunk))->tap(function () use (&$chunk, $step) {
                        $chunk = array_slice($chunk, $step, null, true);
                    });

                    // If the $step between chunks is bigger than each chunk's $size
                    // we will skip the extra items (which should never be in any
                    // chunk) before we continue to the next chunk in the loop.
                    if ($step > $size) {
                        $skip = $step - $size;

                        for ($i = 0; $i < $skip && $iterator->valid(); $i++) {
                            $iterator->next();
                        }
                    }
                }

                $iterator->next();
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function skip(int $count)
    {
        return new static(function () use ($count) {
            $iterator = $this->getIterator();

            while ($iterator->valid() && $count--) {
                $iterator->next();
            }

            while ($iterator->valid()) {
                yield $iterator->key() => $iterator->current();

                $iterator->next();
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function skipUntil($value)
    {
        $callback = $this->useAsCallable($value) ? $value : $this->equality($value);

        return $this->skipWhile($this->negate($callback));
    }

    /**
     * {@inheritDoc}
     */
    public function skipWhile($value)
    {
        $callback = $this->useAsCallable($value) ? $value : $this->equality($value);

        return new static(function () use ($callback) {
            $iterator = $this->getIterator();

            while ($iterator->valid() && $callback($iterator->current(), $iterator->key())) {
                $iterator->next();
            }

            while ($iterator->valid()) {
                yield $iterator->key() => $iterator->current();

                $iterator->next();
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function slice(int $offset, ?int $length = null)
    {
        if ($offset < 0 || $length < 0) {
            return $this->passthru('slice', func_get_args());
        }

        $instance = $this->skip($offset);

        return null === $length ? $instance : $instance->take($length);
    }

    /**
     * {@inheritDoc}
     */
    public function split(int $numberOfGroups)
    {
        return $this->passthru('split', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function sole($key = null, ?string $operator = null, mixed $value = null): mixed
    {
        $filter = func_num_args() > 1
            ? $this->operatorForWhere(...func_get_args())
            : $key;

        return $this
            ->unless($filter === null)
            ->filter($filter)
            ->take(2)
            ->collect()
            ->sole();
    }

    /**
     * {@inheritDoc}
     */
    public function firstOrFail($key = null, ?string $operator = null, mixed $value = null): mixed
    {
        $filter = func_num_args() > 1
            ? $this->operatorForWhere(...func_get_args())
            : $key;

        return $this
            ->unless($filter === null)
            ->filter($filter)
            ->take(1)
            ->collect()
            ->firstOrFail();
    }

    /**
     * {@inheritDoc}
     */
    public function chunk(int $size)
    {
        if ($size <= 0) {
            return static::empty();
        }

        return new static(function () use ($size) {
            $iterator = $this->getIterator();

            while ($iterator->valid()) {
                $chunk = [];

                while (true) {
                    $chunk[$iterator->key()] = $iterator->current();

                    if (count($chunk) < $size) {
                        $iterator->next();

                        if (! $iterator->valid()) {
                            break;
                        }
                    } else {
                        break;
                    }
                }

                yield new static($chunk);

                $iterator->next();
            }
        });
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
     *
     * @param callable(mixed, int|string, Collection<int|string, mixed>): bool $callback
     */
    public function chunkWhile(callable $callback)
    {
        return new static(function () use ($callback) {
            $iterator = $this->getIterator();

            $chunk = new Collection();

            if ($iterator->valid()) {
                $chunk[$iterator->key()] = $iterator->current();

                $iterator->next();
            }

            while ($iterator->valid()) {
                if (! $callback($iterator->current(), $iterator->key(), $chunk)) {
                    yield new static($chunk);

                    $chunk = new Collection();
                }

                $chunk[$iterator->key()] = $iterator->current();

                $iterator->next();
            }

            if ($chunk->isNotEmpty()) {
                yield new static($chunk);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function sort($callback = null)
    {
        return $this->passthru('sort', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function sortDesc(int $options = SORT_REGULAR)
    {
        return $this->passthru('sortDesc', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function sortBy($callback, int $options = SORT_REGULAR, bool $descending = false)
    {
        return $this->passthru('sortBy', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function sortByDesc($callback, int $options = SORT_REGULAR)
    {
        return $this->passthru('sortByDesc', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false)
    {
        return $this->passthru('sortKeys', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function sortKeysDesc(int $options = SORT_REGULAR)
    {
        return $this->passthru('sortKeysDesc', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function sortKeysUsing(callable $callback)
    {
        return $this->passthru('sortKeysUsing', func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function take(int $limit)
    {
        if ($limit < 0) {
            return $this->passthru('take', func_get_args());
        }

        return new static(function () use ($limit) {
            $iterator = $this->getIterator();

            while ($limit--) {
                if (! $iterator->valid()) {
                    break;
                }

                yield $iterator->key() => $iterator->current();

                if ($limit) {
                    $iterator->next();
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function takeUntil($value)
    {
        /** @var callable(mixed, int|string): bool $callback */
        $callback = $this->useAsCallable($value) ? $value : $this->equality($value);

        return new static(function () use ($callback) {
            foreach ($this as $key => $item) {
                if ($callback($item, $key)) {
                    break;
                }

                yield $key => $item;
            }
        });
    }

    /**
     * Prenez les éléments de la collection jusqu'à un moment donné.
     *
     * @return static
     */
    public function takeUntilTimeout(DateTimeInterface $timeout)
    {
        $timeout = $timeout->getTimestamp();

        return new static(function () use ($timeout) {
            if ($this->now() >= $timeout) {
                return;
            }

            foreach ($this as $key => $value) {
                yield $key => $value;

                if ($this->now() >= $timeout) {
                    break;
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function takeWhile($value)
    {
        /** @var callable(mixed, int|string): bool $callback */
        $callback = $this->useAsCallable($value) ? $value : $this->equality($value);

        return $this->takeUntil(fn ($item, $key) => ! $callback($item, $key));
    }

    /**
     * {@inheritDoc}
     */
    public function tapEach(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                $callback($value, $key);

                yield $key => $value;
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function undot()
    {
        return $this->passthru('undot', []);
    }

    /**
     * {@inheritDoc}
     */
    public function unique($key = null, bool $strict = false)
    {
        $callback = $this->valueRetriever($key);

        return new static(function () use ($callback, $strict) {
            $exists = [];

            foreach ($this as $key => $item) {
                if (! in_array($id = $callback($item, $key), $exists, $strict)) {
                    yield $key => $item;

                    $exists[] = $id;
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function values()
    {
        return new static(function () {
            foreach ($this as $item) {
                yield $item;
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function zip($items)
    {
        $iterables = func_get_args();

        return new static(function () use ($iterables) {
            $iterators = Collection::make($iterables)->map(fn ($iterable) => $this->makeIterator($iterable))->prepend($this->getIterator());

            while ($iterators->contains->valid()) {
                yield new static($iterators->map->current());

                $iterators->each->next();
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function pad(int $size, mixed $value)
    {
        if ($size < 0) {
            return $this->passthru('pad', func_get_args());
        }

        return new static(function () use ($size, $value) {
            $yielded = 0;

            foreach ($this as $index => $item) {
                yield $index => $item;

                $yielded++;
            }

            while ($yielded++ < $size) {
                yield $value;
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): Traversable
    {
        return $this->makeIterator($this->source);
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        if (is_array($this->source)) {
            return count($this->source);
        }

        return iterator_count($this->getIterator());
    }

    /**
     * Créez un itérateur à partir de la source donnée.
     *
     * @param  IteratorAggregate|array|(callable(): Generator)  $source
     */
    protected function makeIterator($source): Traversable
    {
        if ($source instanceof IteratorAggregate) {
            return $source->getIterator();
        }

        if (is_array($source)) {
            return new ArrayIterator($source);
        }

        if (is_callable($source)) {
            $maybeTraversable = $source();

            return $maybeTraversable instanceof Traversable
                ? $maybeTraversable
                : new ArrayIterator(Arr::wrap($maybeTraversable));
        }

        return new ArrayIterator((array) $source);
    }

    /**
     * Décomposez les arguments "value" et "key" passés à "pluck".
     *
     * @param string|string[]      $value
     * @param string|string[]|null $key
     *
     * @return array{string[],string[]|null}
     */
    protected function explodePluckParameters($value, $key): array
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = null === $key || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Passez cette collection paresseuse via une méthode sur la classe de collection.
     *
     * @return static
     */
    protected function passthru(string $method, array $params)
    {
        return new static(function () use ($method, $params) {
            yield from $this->collect()->{$method}(...$params);
        });
    }

    /**
     * Obtenez l'heure actuelle.
     */
    protected function now(): int
    {
        return time();
    }
}
