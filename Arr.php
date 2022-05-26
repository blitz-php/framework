<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Utilities;

use ArrayAccess;
use BlitzPHP\Contracts\Traits\Macroable;
use Exception;
use InvalidArgumentException;

class Arr
{
    use Macroable;

    public const SORT_ASC  = 1;
    public const SORT_DESC = 2;

    /**
     * Déterminez si la valeur donnée est un tableau accessible.
     *
     * @param mixed $value
     */
    public static function accessible($value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Ajoutez un élément à un tableau en utilisant la notation "point" s'il n'existe pas.
     *
     * @param mixed $value
     */
    public static function add(array $array, string $key, $value): array
    {
        if (null === static::get($array, $key)) {
            static::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * Teste si oui ou non un chemin donné existe dans $data.
     * Cette méthode utilise la même syntaxe de chemin que Arr::extract()
     *
     * La vérification des chemins qui pourraient cibler plus d'un élément
     * assurez-vous qu'au moins un élément correspondant existe.
     *
     * @param array  $data La donnée à vérifier
     * @param string $path Le chemin à vérifier.
     *
     * @see self::extract()
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::check
     */
    public static function check(array $data, string $path): bool
    {
        $results = self::extract($data, $path);
        if (! is_array($results)) {
            return false;
        }

        return count($results) > 0;
    }

    /**
     * Réduire un tableau de tableaux en un seul tableau.
     */
    public static function collapse(array $array): array
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return array_merge([], ...$results);
    }

    /**
     * Crée un tableau associatif en utilisant `$keyPath` comme chemin pour construire ses clés, et éventuellement
     * `$valuePath` comme chemin pour obtenir les valeurs. Si `$valuePath` n'est pas spécifié, toutes les valeurs seront initialisées
     * à null (utile pour Arr::merge). Vous pouvez éventuellement regrouper les valeurs en fonction de ce qui est obtenu lorsque
     * le chemin est spécifié dans `$groupPath`.
     *
     * @param array  $data      Array from where to extract keys and values
     * @param string $keyPath   A dot-separated string.
     * @param string $valuePath A dot-separated string.
     * @param string $groupPath A dot-separated string.
     *
     * @return array Combined array
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::combine
     */
    public static function combine(array $data, string $keyPath, ?string $valuePath = null, ?string $groupPath = null): array
    {
        if (empty($data)) {
            return [];
        }

        if (is_array($keyPath)) {
            $format = array_shift($keyPath);
            $keys   = self::format($data, $keyPath, $format);
        } else {
            $keys = self::extract($data, $keyPath);
        }
        if (empty($keys)) {
            return [];
        }

        if (! empty($valuePath) && is_array($valuePath)) {
            $format = array_shift($valuePath);
            $vals   = self::format($data, $valuePath, $format);
        } elseif (! empty($valuePath)) {
            $vals = self::extract($data, $valuePath);
        }
        if (empty($vals)) {
            $vals = array_fill(0, count($keys), null);
        }

        if (count($keys) !== count($vals)) {
            throw new Exception('Arr::combine() needs an equal number of keys + values.');
        }

        if ($groupPath !== null) {
            $group = self::extract($data, $groupPath);
            if (! empty($group)) {
                $c = count($keys);

                for ($i = 0; $i < $c; $i++) {
                    if (! isset($group[$i])) {
                        $group[$i] = 0;
                    }
                    if (! isset($out[$group[$i]])) {
                        $out[$group[$i]] = [];
                    }
                    $out[$group[$i]][$keys[$i]] = $vals[$i];
                }

                return $out;
            }
        }
        if (empty($vals)) {
            return [];
        }

        return array_combine($keys, $vals);
    }

    /**
     * Determines if one array contains the exact keys and values of another.
     *
     * @param array $data   The data to search through.
     * @param array $needle The values to file in $data
     *
     * @return bool true if $data contains $needle, false otherwise
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::contains
     */
    public static function contains(array $data, array $needle): bool
    {
        if (empty($data) || empty($needle)) {
            return false;
        }
        $stack = [];

        while (! empty($needle)) {
            $key = key($needle);
            $val = $needle[$key];
            unset($needle[$key]);

            if (array_key_exists($key, $data) && is_array($val)) {
                $next = $data[$key];
                unset($data[$key]);

                if (! empty($val)) {
                    $stack[] = [$val, $next];
                }
            } elseif (! array_key_exists($key, $data) || $data[$key] !== $val) {
                return false;
            }

            if (empty($needle) && ! empty($stack)) {
                [$needle, $data] = array_pop($stack);
            }
        }

        return true;
    }

    /**
     * Cross join the given arrays, returning all possible permutations.
     */
    public static function crossJoin(array ...$arrays): array
    {
        $results = [[]];

        foreach ($arrays as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;

                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    /**
     * Counts the dimensions of an array.
     * Only considers the dimension of the first element in the array.
     *
     * If you have an un-even or heterogenous array, consider using Hash::maxDimensions()
     * to get the dimensions of the array.
     *
     * @param array $data Array to count dimensions on
     *
     * @return int The number of dimensions in $data
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::dimensions
     */
    public static function dimensions(array $data): int
    {
        if (empty($data)) {
            return 0;
        }
        reset($data);
        $depth = 1;

        while ($elem = array_shift($data)) {
            if (is_array($elem)) {
                $depth++;
                $data = &$elem;
            } else {
                break;
            }
        }

        return $depth;
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     */
    public static function divide(array $array): array
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param array|string $keys
     */
    public static function except(array $array, $keys): array
    {
        static::forget($array, $keys);

        return $array;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param array|ArrayAccess $array
     * @param int|string        $key
     */
    public static function exists($array, $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     *
     * @param array|string      $value
     * @param array|string|null $key
     */
    protected static function explodePluckParameters($value, $key): array
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = null === $key || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Gets the values from an array matching the $path expression.
     * The path expression is a dot separated expression, that can contain a set
     * of patterns and expressions:
     *
     * - `{n}` Matches any numeric key, or integer.
     * - `{s}` Matches any string key.
     * - `Foo` Matches any key with the exact same value.
     *
     * There are a number of attribute operators:
     *
     *  - `=`, `!=` Equality.
     *  - `>`, `<`, `>=`, `<=` Value comparison.
     *  - `=/.../` Regular expression pattern match.
     *
     * Given a set of User array data, from a `$User->find('all')` call:
     *
     * - `1.User.name` Get the name of the user at index 1.
     * - `{n}.User.name` Get the name of every user in the set of users.
     * - `{n}.User[id]` Get the name of every user with an id key.
     * - `{n}.User[id>=2]` Get the name of every user with an id key greater than or equal to 2.
     * - `{n}.User[username=/^paul/]` Get User elements with username matching `^paul`.
     *
     * @param array  $data The data to extract from.
     * @param string $path The path to extract.
     *
     * @return array An array of the extracted values. Returns an empty array
     *               if there are no matches.
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::extract
     */
    public static function extract(array $data, string $path): array
    {
        if (empty($path)) {
            return $data;
        }

        // Simple paths.
        if (! preg_match('/[{\[]/', $path)) {
            return (array) self::get($data, $path);
        }

        if (strpos($path, '[') === false) {
            $tokens = explode('.', $path);
        } else {
            $tokens = Str::tokenize($path, '.', '[', ']');
        }

        $_key = '__set_item__';

        $context = [$_key => [$data]];

        foreach ($tokens as $token) {
            $next = [];

            [$token, $conditions] = self::_splitConditions($token);

            foreach ($context[$_key] as $item) {
                foreach ((array) $item as $k => $v) {
                    if (self::_matchToken($k, $token)) {
                        $next[] = $v;
                    }
                }
            }

            // Filter for attributes.
            if ($conditions) {
                $filter = [];

                foreach ($next as $item) {
                    if (is_array($item) && self::_matches($item, $conditions)) {
                        $filter[] = $item;
                    }
                }
                $next = $filter;
            }
            $context = [$_key => $next];
        }

        return $context[$_key];
    }

    /**
     * Expands a flat array to a nested array.
     *
     * For example, unflattens an array that was collapsed with `Hash::flatten()`
     * into a multi-dimensional array. So, `array('0.Foo.Bar' => 'Far')` becomes
     * `array(array('Foo' => array('Bar' => 'Far')))`.
     *
     * @param array  $data      Flattened array
     * @param string $separator The delimiter used
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::expand
     */
    public static function expand(array $data, string $separator = '.'): array
    {
        $result = [];

        foreach ($data as $flat => $value) {
            $keys  = explode($separator, $flat);
            $keys  = array_reverse($keys);
            $child = [$keys[0] => $value];
            array_shift($keys);

            foreach ($keys as $k) {
                $child = [$k => $child];
            }
            $result = self::merge($result, $child);
        }

        return $result;
    }

    /**
     * Recursively filters a data set.
     *
     * @param array    $data     Either an array to filter, or value when in callback
     * @param callable $callback A function to filter the data with. Defaults to
     *                           `self::_filter()` Which strips out all non-zero empty values.
     *
     * @return array Filtered array
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::filter
     */
    public static function filter(array $data, $callback = ['self', '_filter']): array
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::filter($v, $callback);
            }
        }

        return array_filter($data, $callback);
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public static function first(array $array, ?callable $callback = null, $default = null)
    {
        if (null === $callback) {
            if (empty($array)) {
                return $default;
            }

            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Collapses a multi-dimensional array into a single dimension, using a delimited array path for
     * each array element's key, i.e. array(array('Foo' => array('Bar' => 'Far'))) becomes
     * array('0.Foo.Bar' => 'Far').)
     *
     * @param array  $data      Array to flatten
     * @param string $separator String used to separate array key elements in a path, defaults to '.'
     * @credit http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::flatten
     */
    public static function flatten(array $data, string $separator = '.'): array
    {
        $result = [];
        $stack  = [];
        $path   = null;

        reset($data);

        while (! empty($data)) {
            $key     = key($data);
            $element = $data[$key];
            unset($data[$key]);

            if (is_array($element) && ! empty($element)) {
                if (! empty($data)) {
                    $stack[] = [$data, $path];
                }
                $data = $element;
                reset($data);
                $path .= $key . $separator;
            } else {
                $result[$path . $key] = $element;
            }

            if (empty($data) && ! empty($stack)) {
                [$data, $path] = array_pop($stack);
                reset($data);
            }
        }

        return $result;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param array|string $keys
     *
     * @return void
     */
    public static function forget(array &$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Returns a formatted series of values extracted from `$data`, using
     * `$format` as the format and `$paths` as the values to extract.
     *
     * Usage:
     *
     * {{{
     * $result = Hash::format($users, array('{n}.User.id', '{n}.User.name'), '%s : %s');
     * }}}
     *
     * The `$format` string can use any format options that `vsprintf()` and `sprintf()` do.
     *
     * @param array  $data   Source array from which to extract the data
     * @param array  $paths  An array containing one or more Hash::extract()-style key paths
     * @param string $format Format string into which values will be inserted, see sprintf()
     *
     * @return array|null An array of strings extracted from `$path` and formatted with `$format`
     *
     * @see http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::format
     * @see sprintf()
     * @see Tableau::extract()
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::format
     */
    public static function format(array $data, array $paths, string $format)
    {
        $extracted = [];
        $count     = count($paths);

        if (! $count) {
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $extracted[] = self::extract($data, $paths[$i]);
        }
        $out   = [];
        $data  = $extracted;
        $count = count($data[0]);

        $countTwo = count($data);

        for ($j = 0; $j < $count; $j++) {
            $args = [];

            for ($i = 0; $i < $countTwo; $i++) {
                if (array_key_exists($j, $data[$i])) {
                    $args[] = $data[$i][$j];
                }
            }
            $out[] = vsprintf($format, $args);
        }

        return $out;
    }

    /**
     * Get a single value specified by $path out of $data.
     * Does not support the full dot notation feature set,
     * but is faster for simple read operations.
     *
     * @param array|ArrayAccess $data    Array of data to operate on.
     * @param array|string      $path    The path being searched for. Either a dot
     *                                   separated string, or an array of path segments.
     * @param mixed             $default The return value when the path does not exist
     *
     * @return mixed The value fetched from the array, or null.
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::get
     */
    public static function get($data, $path, $default = null)
    {
        if (! static::accessible($data)) {
            return $default;
        }
        if (is_string($path) || is_numeric($path)) {
            $parts = explode('.', $path);
        } else {
            $parts = $path;
        }

        foreach ($parts as $key) {
            if (is_array($data) && isset($data[$key])) {
                $data = &$data[$key];
            } else {
                return $default;
            }
        }

        return $data;
    }

    /**
     * @return mixed
     */
    public static function getRecursive(?array $data, ?string $key = null)
    {
        if (empty($data)) {
            return null;
        }
        if (empty($key)) {
            return $data;
        }

        $key   = explode('.', $key);
        $count = count($key);

        if ($count === 1) {
            return $data[$key[0]] ?? null;
        }

        $sub_key = $key[1];

        for ($i = 2; $i < $count; $i++) {
            $sub_key .= '.' . $key[$i];
        }

        return self::getRecursive($data[$key[0]] ?? null, $sub_key);
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param array|ArrayAccess $array
     * @param array|string      $keys
     */
    public static function has($array, $keys): bool
    {
        $keys = (array) $keys;

        if (! $array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Insert $values into an array with the given $path. You can use
     * `{n}` and `{s}` elements to insert $data multiple times.
     *
     * @param array  $data   The data to insert into.
     * @param string $path   The path to insert at.
     * @param array  $values The values to insert.
     *
     * @return array The data with $values inserted.
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::insert
     */
    public static function insert(array $data, $path, $values = null)
    {
        if (strpos($path, '[') === false) {
            $tokens = explode('.', $path);
        } else {
            $tokens = Str::tokenize($path, '.', '[', ']');
        }
        if (strpos($path, '{') === false && strpos($path, '[') === false) {
            return self::_simpleOp('insert', $data, $tokens, $values);
        }

        $token    = array_shift($tokens);
        $nextPath = implode('.', $tokens);

        [$token, $conditions] = self::_splitConditions($token);

        foreach ($data as $k => $v) {
            if (self::_matchToken($k, $token)) {
                if ($conditions && self::_matches($v, $conditions)) {
                    $data[$k] = array_merge($v, $values);

                    continue;
                }
                if (! $conditions) {
                    $data[$k] = self::insert($v, $nextPath, $values);
                }
            }
        }

        return $data;
    }

    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     */
    public static function isAssoc(array $array): bool
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public static function last(array $array, ?callable $callback = null, $default = null)
    {
        if (null === $callback) {
            return empty($array) ? $default : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Counts the dimensions of *all* array elements. Useful for finding the maximum
     * number of dimensions in a mixed array.
     *
     * @param array $data Array to count dimensions on
     *
     * @return int The maximum number of dimensions in $data
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::maxDimensions
     */
    public static function maxDimensions(array $data): int
    {
        $depth = [];
        if (is_array($data) && reset($data) !== false) {
            foreach ($data as $value) {
                $depth[] = self::dimensions((array) $value) + 1;
            }
        }

        return max($depth);
    }

    /**
     * This function can be thought of as a hybrid between PHP's `array_merge` and `array_merge_recursive`.
     *
     * The difference between this method and the built-in ones, is that if an array key contains another array, then
     * Hash::merge() will behave in a recursive fashion (unlike `array_merge`). But it will not act recursively for
     * keys that contain scalar values (unlike `array_merge_recursive`).
     *
     * Note: This function will work with an unlimited amount of arguments and typecasts non-array parameters into arrays.
     *
     * @param array $data  Array to be merged
     * @param mixed $merge Array to merge with. The argument and all trailing arguments will be array cast when merged
     *
     * @return array Merged array
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::merge
     */
    public static function merge(array $data, $merge)
    {
        $args   = func_get_args();
        $return = current($args);

        while (($arg = next($args)) !== false) {
            foreach ((array) $arg as $key => $val) {
                if (! empty($return[$key]) && is_array($return[$key]) && is_array($val)) {
                    $return[$key] = self::merge($return[$key], $val);
                } elseif (is_int($key) && isset($return[$key])) {
                    $return[] = $val;
                } else {
                    $return[$key] = $val;
                }
            }
        }

        return $return;
    }

    /**
     * Checks to see if all the values in the array are numeric
     *
     * @param array $data The array to check.
     *
     * @return bool true if values are numeric, false otherwise
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::numeric
     */
    public static function numeric(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        return $data === array_filter($data, 'is_numeric');
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param array|string $keys
     */
    public static function only(array $array, $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param mixed $value
     * @param mixed $key
     */
    public static function prepend(array $array, $value, $key = null): array
    {
        if (null === $key) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public static function pull(array &$array, string $key, $default = null)
    {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }

    /**
     * Convert the array into a query string.
     */
    public static function query(array $array): string
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Get one or a specified number of random values from an array.
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public static function random(array $array, ?int $number = null)
    {
        $requested = null === $number ? 1 : $number;

        $count = count($array);

        if ($requested > $count) {
            throw new InvalidArgumentException(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }

        if (null === $number) {
            return $array[array_rand($array)];
        }

        if ((int) $number === 0) {
            return [];
        }

        $keys = array_rand($array, $number);

        $results = [];

        foreach ((array) $keys as $key) {
            $results[] = $array[$key];
        }

        return $results;
    }

    /**
     * Remove data matching $path from the $data array.
     * You can use `{n}` and `{s}` to remove multiple elements
     * from $data.
     *
     * @param array  $data The data to operate on
     * @param string $path A path expression to use to remove.
     *
     * @return array The modified array.
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-utility-libraries/hash.html#Hash::remove
     */
    public static function remove(array $data, $path)
    {
        if (strpos($path, '[') === false) {
            $tokens = explode('.', $path);
        } else {
            $tokens = Str::tokenize($path, '.', '[', ']');
        }

        if (strpos($path, '{') === false && strpos($path, '[') === false) {
            return self::_simpleOp('remove', $data, $tokens);
        }

        $token    = array_shift($tokens);
        $nextPath = implode('.', $tokens);

        [$token, $conditions] = self::_splitConditions($token);

        foreach ($data as $k => $v) {
            $match = self::_matchToken($k, $token);
            if ($match && is_array($v)) {
                if ($conditions && self::_matches($v, $conditions)) {
                    unset($data[$k]);

                    continue;
                }
                $data[$k] = self::remove($v, $nextPath);
                if (empty($data[$k])) {
                    unset($data[$k]);
                }
            } elseif ($match) {
                unset($data[$k]);
            }
        }

        return $data;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param mixed $value
     */
    public static function set(array &$array, string $key, $value): array
    {
        if (null === $key) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    public static function setRecursive(array &$data, ?string $key = null, $value = null)
    {
        if (empty($data) && empty($key)) {
            return;
        }

        $key   = explode('.', $key);
        $count = count($key);

        if ($count === 1) {
            $data[$key[0]] = $value;

            return;
        }

        $sub_key = $key[1];

        for ($i = 2; $i < $count; $i++) {
            $sub_key .= '.' . $key[$i];
        }

        if (! isset($data[$key[0]])) {
            $data[$key[0]] = [];
        }

        self::setRecursive($data[$key[0]], $sub_key, $value);
    }

    /**
     * Shuffle the given array and return the result.
     */
    public static function shuffle(array $array, ?int $seed = null): array
    {
        if (null === $seed) {
            shuffle($array);
        } else {
            mt_srand($seed);
            shuffle($array);
            mt_srand();
        }

        return $array;
    }

    /**
     * Sort the array using the given callback or "dot" notation.
     *
     * @param array                $array
     * @param callable|string|null $callback
     */
    public static function sort($array, $callback = null): array
    {
        return Collection::make($array)->sortBy($callback)->all();
    }

    /**
     * Sort an array in ASC/DESC order relativly to a specific position
     *
     * @param array  $data      Array to sort
     * @param string $field     String to describe field position
     * @param int    $direction Direction of sort based on class constants
     *
     * @return array Sorted array
     */
    public static function sortField(array $data, string $field, int $direction = self::SORT_ASC): array
    {
        usort($data, static function ($a, $b) use ($field, $direction) {
            $cmp1 = self::_getSortField_($a, $field);
            $cmp2 = self::_getSortField_($b, $field);

            if ($cmp1 === $cmp2) {
                return 0;
            }
            if ($direction === self::SORT_ASC) {
                return ($cmp1 < $cmp2) ? -1 : 1;
            }

            return ($cmp1 < $cmp2) ? 1 : -1;
        });

        return $data;
    }

    private static function _getSortField_($element, $field)
    {
        $field = explode('.', $field);

        foreach ($field as $key) {
            if (is_object($element) && isset($element->{$key})) {
                $element = $element->{$key};
            } elseif (isset($element[$key])) {
                $element = $element[$key];
            } else {
                break;
            }
        }

        return $element;
    }

    /**
     * Recursively sort an array by keys and values.
     */
    public static function sortRecursive(array $array): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::sortRecursive($value);
            }
        }

        if (static::isAssoc($array)) {
            ksort($array);
        } else {
            sort($array);
        }

        return $array;
    }

    /**
     * Filter the array using the given callback.
     */
    public static function where(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * @param mixed $value
     */
    public static function wrap($value): array
    {
        if (null === $value) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Callback function for filtering.
     *
     * @param mixed $var Array to filter.
     */
    protected static function _filter($var): bool
    {
        return (bool) ($var === 0 || $var === '0' || ! empty($var));
    }

    /**
     * Split token conditions
     *
     * @param string $token the token being splitted.
     *
     * @return array array(token, conditions) with token splitted
     */
    protected static function _splitConditions(string $token): array
    {
        $conditions = false;
        $position   = strpos($token, '[');
        if ($position !== false) {
            $conditions = substr($token, $position);
            $token      = substr($token, 0, $position);
        }

        return [$token, $conditions];
    }

    /**
     * Check a key against a token.
     *
     * @param string $key   The key in the array being searched.
     * @param string $token The token being matched.
     */
    protected static function _matchToken(string $key, string $token): bool
    {
        if ($token === '{n}') {
            return is_numeric($key);
        }
        if ($token === '{s}') {
            return is_string($key);
        }
        if (is_numeric($token)) {
            return $key === $token;
        }

        return $key === $token;
    }

    /**
     * Checks whether or not $data matches the attribute patterns
     *
     * @param array  $data     Array of data to match.
     * @param string $selector The patterns to match.
     *
     * @return bool Fitness of expression.
     */
    protected static function _matches(array $data, string $selector): bool
    {
        preg_match_all(
            '/(\[ (?P<attr>[^=><!]+?) (\s* (?P<op>[><!]?[=]|[><]) \s* (?P<val>(?:\/.*?\/ | [^\]]+)) )? \])/x',
            $selector,
            $conditions,
            PREG_SET_ORDER
        );

        foreach ($conditions as $cond) {
            $attr = $cond['attr'];
            $op   = $cond['op'] ?? null;
            $val  = $cond['val'] ?? null;

            // Presence test.
            if (empty($op) && empty($val) && ! isset($data[$attr])) {
                return false;
            }
            // Empty attribute = fail.
            if (! (isset($data[$attr]) || array_key_exists($attr, $data))) {
                return false;
            }
            $prop = null;
            if (isset($data[$attr])) {
                $prop = $data[$attr];
            }
            $isBool = is_bool($prop);
            if ($isBool && is_numeric($val)) {
                $prop = $prop ? '1' : '0';
            } elseif ($isBool) {
                $prop = $prop ? 'true' : 'false';
            }
            // Pattern matches and other operators.
            if ($op === '=' && $val && $val[0] === '/') {
                if (! preg_match($val, $prop)) {
                    return false;
                }
            } elseif (
                ($op === '=' && $prop !== $val)
                || ($op === '!=' && $prop === $val)
                || ($op === '>' && $prop <= $val)
                || ($op === '<' && $prop >= $val)
                || ($op === '>=' && $prop < $val)
                || ($op === '<=' && $prop > $val)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Perform a simple insert/remove operation.
     *
     * @param string $op     The operation to do.
     * @param array  $data   The data to operate on.
     * @param array  $path   The path to work on.
     * @param mixed  $values The values to insert when doing inserts.
     *
     * @return array|void data.
     */
    protected static function _simpleOp(string $op, array $data, array $path, $values = null)
    {
        $_list = &$data;

        $count = count($path);
        $last  = $count - 1;

        foreach ($path as $i => $key) {
            if (is_numeric($key) && (int) $key > 0 || $key === '0') {
                $key = (int) $key;
            }
            if ($op === 'insert') {
                if ($i === $last) {
                    $_list[$key] = $values;

                    return $data;
                }
                if (! isset($_list[$key])) {
                    $_list[$key] = [];
                }
                $_list = &$_list[$key];
                if (! is_array($_list)) {
                    $_list = [];
                }
            } elseif ($op === 'remove') {
                if ($i === $last) {
                    unset($_list[$key]);

                    return $data;
                }
                if (! isset($_list[$key])) {
                    return $data;
                }
                $_list = &$_list[$key];
            }
        }
    }
}
