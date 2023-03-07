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

use Closure;
use Countable;
use Exception;
use JsonSerializable;

class Stringable implements JsonSerializable
{
    /**
     * The underlying string value.
     */
    protected string $value;

    /**
     * Create a new instance of the class.
     *
     * @param string $value
     *
     * @return void
     */
    public function __construct($value = '')
    {
        $this->value = (string) $value;
    }

    /**
     * Return the remainder of a string after the first occurrence of a given value.
     *
     * @return static
     */
    public function after(string $search)
    {
        return new static(Str::after($this->value, $search));
    }

    /**
     * Return the remainder of a string after the last occurrence of a given value.
     *
     * @return static
     */
    public function afterLast(string $search)
    {
        return new static(Str::afterLast($this->value, $search));
    }

    /**
     * Append the given values to the string.
     *
     * @param array ...$values
     *
     * @return static
     */
    public function append(...$values)
    {
        return new static($this->value . implode('', $values));
    }

    /**
     * Append a new line to the string.
     */
    public function newLine(int $count = 1): self
    {
        return $this->append(str_repeat(PHP_EOL, $count));
    }

    /**
     * Transliterate a UTF-8 value to ASCII.
     *
     * @return static
     */
    public function ascii(string $language = 'en')
    {
        return new static(Str::ascii($this->value, $language));
    }

    /**
     * Get the trailing name component of the path.
     *
     * @return static
     */
    public function basename(string $suffix = '')
    {
        return new static(basename($this->value, $suffix));
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     *
     * @return static
     */
    public function before(string $search)
    {
        return new static(Str::before($this->value, $search));
    }

    /**
     * Get the portion of a string before the last occurrence of a given value.
     *
     * @return static
     */
    public function beforeLast(string $search)
    {
        return new static(Str::beforeLast($this->value, $search));
    }

    /**
     * Get the portion of a string between two given values.
     *
     * @return static
     */
    public function between(string $from, string $to)
    {
        return new static(Str::between($this->value, $from, $to));
    }

    /**
     * Get the smallest possible portion of a string between two given values.
     *
     * @return static
     */
    public function betweenFirst(string $from, string $to)
    {
        return new static(Str::betweenFirst($this->value, $from, $to));
    }

    /**
     * Convert a value to camel case.
     *
     * @return static
     */
    public function camel()
    {
        return new static(Str::camel($this->value));
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param iterable<string>|string $needles
     */
    public function contains($needles, bool $ignoreCase = false): bool
    {
        return Str::contains($this->value, $needles, $ignoreCase);
    }

    /**
     * Determine if a given string contains all array values.
     *
     * @param iterable<string> $needles
     */
    public function containsAll($needles, bool $ignoreCase = false): bool
    {
        return Str::containsAll($this->value, $needles, $ignoreCase);
    }

    /**
     * Get the parent directory's path.
     *
     * @return static
     */
    public function dirname(int $levels = 1)
    {
        return new static(dirname($this->value, $levels));
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param iterable<string>|string $needles
     */
    public function endsWith($needles): bool
    {
        return Str::endsWith($this->value, $needles);
    }

    /**
     * Determine if the string is an exact match with the given value.
     */
    public function exactly(Stringable|string $value): bool
    {
        if ($value instanceof Stringable) {
            $value = $value->toString();
        }

        return $this->value === $value;
    }

    /**
     * Extracts an excerpt from text that matches the first instance of a phrase.
     */
    public function excerpt(string $phrase = '', array $options = []): ?string
    {
        return Str::excerpt($this->value, $phrase, $options);
    }

    /**
     * Explode the string into an array.
     */
    public function explode(string $delimiter, int $limit = PHP_INT_MAX): Collection
    {
        return new Collection(explode($delimiter, $this->value, $limit));
    }

    /**
     * Split a string using a regular expression or by length.
     */
    public function split(string|int $pattern, int $limit = -1, int $flags = 0): Collection
    {
        if (filter_var($pattern, FILTER_VALIDATE_INT) !== false) {
            return new Collection(mb_str_split($this->value, $pattern));
        }

        $segments = preg_split($pattern, $this->value, $limit, $flags);

        return ! empty($segments) ? new Collection($segments) : new Collection();
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @return static
     */
    public function finish(string $cap)
    {
        return new static(Str::finish($this->value, $cap));
    }

    /**
     * Determine if a given string matches a given pattern.
     *
     * @param iterable<string>|string $pattern
     */
    public function is($pattern): bool
    {
        return Str::is($pattern, $this->value);
    }

    /**
     * Determine if a given string is 7 bit ASCII.
     */
    public function isAscii(): bool
    {
        return Str::isAscii($this->value);
    }

    /**
     * Determine if a given string is valid JSON.
     */
    public function isJson(): bool
    {
        return Str::isJson($this->value);
    }

    /**
     * Determine if a given string is a valid UUID.
     */
    public function isUuid(): bool
    {
        return Str::isUuid($this->value);
    }

    /**
     * Determine if a given string is a valid ULID.
     */
    public function isUlid(): bool
    {
        return Str::isUlid($this->value);
    }

    /**
     * Determine if the given string is empty.
     */
    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    /**
     * Determine if the given string is not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Convert a string to kebab case.
     *
     * @return static
     */
    public function kebab()
    {
        return new static(Str::kebab($this->value));
    }

    /**
     * Return the length of the given string.
     */
    public function length(?string $encoding = null): int
    {
        return Str::length($this->value, $encoding);
    }

    /**
     * Limit the number of characters in a string.
     *
     * @return static
     */
    public function limit(int $limit = 100, string $end = '...')
    {
        return new static(Str::limit($this->value, $limit, $end));
    }

    /**
     * Convert the given string to lower-case.
     *
     * @return static
     */
    public function lower()
    {
        return new static(Str::lower($this->value));
    }

    /**
     * Convert GitHub flavored Markdown into HTML.
     *
     * @return static
     */
    public function markdown(array $options = [])
    {
        return new static(Str::markdown($this->value, $options));
    }

    /**
     * Convert inline Markdown into HTML.
     *
     * @return static
     */
    public function inlineMarkdown(array $options = [])
    {
        return new static(Str::inlineMarkdown($this->value, $options));
    }

    /**
     * Masks a portion of a string with a repeated character.
     *
     * @return static
     */
    public function mask(string $character, int $index, ?int $length = null, string $encoding = 'UTF-8')
    {
        return new static(Str::mask($this->value, $character, $index, $length, $encoding));
    }

    /**
     * Get the string matching the given pattern.
     *
     * @return static
     */
    public function match(string $pattern)
    {
        return new static(Str::match($pattern, $this->value));
    }

    /**
     * Get the string matching the given pattern.
     */
    public function matchAll(string $pattern): Collection
    {
        return Str::matchAll($pattern, $this->value);
    }

    /**
     * Determine if the string matches the given pattern.
     */
    public function test(string $pattern): bool
    {
        return $this->match($pattern)->isNotEmpty();
    }

    /**
     * Pad both sides of the string with another.
     *
     * @return static
     */
    public function padBoth(int $length, string $pad = ' ')
    {
        return new static(Str::padBoth($this->value, $length, $pad));
    }

    /**
     * Pad the left side of the string with another.
     *
     * @param int    $length
     * @param string $pad
     *
     * @return static
     */
    public function padLeft($length, $pad = ' ')
    {
        return new static(Str::padLeft($this->value, $length, $pad));
    }

    /**
     * Pad the right side of the string with another.
     *
     * @param int    $length
     * @param string $pad
     *
     * @return static
     */
    public function padRight($length, $pad = ' ')
    {
        return new static(Str::padRight($this->value, $length, $pad));
    }

    /**
     * Parse a Class@method style callback into class and method.
     *
     * @param string|null $default
     *
     * @return array<int, string|null>
     */
    public function parseCallback($default = null)
    {
        return Str::parseCallback($this->value, $default);
    }

    /**
     * Call the given callback and return a new string.
     *
     * @return static
     */
    public function pipe(callable $callback)
    {
        return new static($callback($this));
    }

    /**
     * Get the plural form of an English word.
     *
     * @param array|Countable|int $count
     *
     * @return static
     */
    public function plural($count = 2)
    {
        return new static(Str::plural($this->value, $count));
    }

    /**
     * Pluralize the last word of an English, studly caps case string.
     *
     * @param array|Countable|int $count
     *
     * @return static
     */
    public function pluralStudly($count = 2)
    {
        return new static(Str::pluralStudly($this->value, $count));
    }

    /**
     * Prepend the given values to the string.
     *
     * @param array ...$values
     *
     * @return static
     */
    public function prepend(...$values)
    {
        return new static(implode('', $values) . $this->value);
    }

    /**
     * Remove any occurrence of the given string in the subject.
     *
     * @param iterable<string>|string $search
     * @param bool                    $caseSensitive
     *
     * @return static
     */
    public function remove($search, $caseSensitive = true)
    {
        return new static(Str::remove($search, $this->value, $caseSensitive));
    }

    /**
     * Reverse the string.
     *
     * @return static
     */
    public function reverse()
    {
        return new static(Str::reverse($this->value));
    }

    /**
     * Repeat the string.
     *
     * @return static
     */
    public function repeat(int $times)
    {
        return new static(str_repeat($this->value, $times));
    }

    /**
     * Replace the given value in the given string.
     *
     * @param iterable<string>|string $search
     * @param iterable<string>|string $replace
     *
     * @return static
     */
    public function replace($search, $replace)
    {
        return new static(Str::replace($search, $replace, $this->value));
    }

    /**
     * Replace a given value in the string sequentially with an array.
     *
     * @param string           $search
     * @param iterable<string> $replace
     *
     * @return static
     */
    public function replaceArray($search, $replace)
    {
        return new static(Str::replaceArray($search, $replace, $this->value));
    }

    /**
     * Replace the first occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $replace
     *
     * @return static
     */
    public function replaceFirst($search, $replace)
    {
        return new static(Str::replaceFirst($search, $replace, $this->value));
    }

    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $replace
     *
     * @return static
     */
    public function replaceLast($search, $replace)
    {
        return new static(Str::replaceLast($search, $replace, $this->value));
    }

    /**
     * Replace the patterns matching the given regular expression.
     *
     * @param string         $pattern
     * @param Closure|string $replace
     * @param int            $limit
     *
     * @return static
     */
    public function replaceMatches($pattern, $replace, $limit = -1)
    {
        if ($replace instanceof Closure) {
            return new static(preg_replace_callback($pattern, $replace, $this->value, $limit));
        }

        return new static(preg_replace($pattern, $replace, $this->value, $limit));
    }

    /**
     * Parse input from a string to a collection, according to a format.
     */
    public function scan(string $format): Collection
    {
        return new Collection(sscanf($this->value, $format));
    }

    /**
     * Remove all "extra" blank space from the given string.
     *
     * @return static
     */
    public function squish()
    {
        return new static(Str::squish($this->value));
    }

    /**
     * Begin a string with a single instance of a given value.
     *
     * @param string $prefix
     *
     * @return static
     */
    public function start($prefix)
    {
        return new static(Str::start($this->value, $prefix));
    }

    /**
     * Strip HTML and PHP tags from the given string.
     *
     * @param string $allowedTags
     *
     * @return static
     */
    public function stripTags($allowedTags = null)
    {
        return new static(strip_tags($this->value, $allowedTags));
    }

    /**
     * Convert the given string to upper-case.
     *
     * @return static
     */
    public function upper()
    {
        return new static(Str::upper($this->value));
    }

    /**
     * Convert the given string to title case.
     *
     * @return static
     */
    public function title()
    {
        return new static(Str::title($this->value));
    }

    /**
     * Convert the given string to title case for each word.
     *
     * @return static
     */
    public function headline()
    {
        return new static(Str::headline($this->value));
    }

    /**
     * Get the singular form of an English word.
     *
     * @return static
     */
    public function singular()
    {
        return new static(Str::singular($this->value));
    }

    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param string                $separator
     * @param string|null           $language
     * @param array<string, string> $dictionary
     *
     * @return static
     */
    public function slug($separator = '-', $language = 'en', $dictionary = ['@' => 'at'])
    {
        return new static(Str::slug($this->value, $separator, $language, $dictionary));
    }

    /**
     * Convert a string to snake case.
     *
     * @param string $delimiter
     *
     * @return static
     */
    public function snake($delimiter = '_')
    {
        return new static(Str::snake($this->value, $delimiter));
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param iterable<string>|string $needles
     *
     * @return bool
     */
    public function startsWith($needles)
    {
        return Str::startsWith($this->value, $needles);
    }

    /**
     * Convert a value to studly caps case.
     *
     * @return static
     */
    public function studly()
    {
        return new static(Str::studly($this->value));
    }

    /**
     * Returns the portion of the string specified by the start and length parameters.
     *
     * @param int      $start
     * @param int|null $length
     * @param string   $encoding
     *
     * @return static
     */
    public function substr($start, $length = null, $encoding = 'UTF-8')
    {
        return new static(Str::substr($this->value, $start, $length, $encoding));
    }

    /**
     * Returns the number of substring occurrences.
     *
     * @param string   $needle
     * @param int      $offset
     * @param int|null $length
     *
     * @return int
     */
    public function substrCount($needle, $offset = 0, $length = null)
    {
        return Str::substrCount($this->value, $needle, $offset, $length);
    }

    /**
     * Replace text within a portion of a string.
     *
     * @param string|string[] $replace
     * @param int|int[]       $offset
     * @param int|int[]|null  $length
     *
     * @return static
     */
    public function substrReplace($replace, $offset = 0, $length = null)
    {
        return new static(Str::substrReplace($this->value, $replace, $offset, $length));
    }

    /**
     * Swap multiple keywords in a string with other keywords.
     *
     * @return static
     */
    public function swap(array $map)
    {
        return new static(strtr($this->value, $map));
    }

    /**
     * Trim the string of the given characters.
     *
     * @param string $characters
     *
     * @return static
     */
    public function trim($characters = null)
    {
        return new static(trim(...array_merge([$this->value], func_get_args())));
    }

    /**
     * Left trim the string of the given characters.
     *
     * @param string $characters
     *
     * @return static
     */
    public function ltrim($characters = null)
    {
        return new static(ltrim(...array_merge([$this->value], func_get_args())));
    }

    /**
     * Right trim the string of the given characters.
     *
     * @param string $characters
     *
     * @return static
     */
    public function rtrim($characters = null)
    {
        return new static(rtrim(...array_merge([$this->value], func_get_args())));
    }

    /**
     * Make a string's first character lowercase.
     *
     * @return static
     */
    public function lcfirst()
    {
        return new static(Str::lcfirst($this->value));
    }

    /**
     * Make a string's first character uppercase.
     *
     * @return static
     */
    public function ucfirst()
    {
        return new static(Str::ucfirst($this->value));
    }

    /**
     * Split a string by uppercase characters.
     */
    public function ucsplit(): Collection
    {
        return new Collection(Str::ucsplit($this->value));
    }

    /**
     * Limit the number of words in a string.
     *
     * @return static
     */
    public function words(int $words = 100, string $end = '...')
    {
        return new static(Str::words($this->value, $words, $end));
    }

    /**
     * Get the number of words a string contains.
     */
    public function wordCount(?string $characters = null): int
    {
        return Str::wordCount($this->value, $characters);
    }

    /**
     * Wrap the string with the given strings.
     *
     * @return static
     */
    public function wrap(string $before, ?string $after = null)
    {
        return new static(Str::wrap($this->value, $before, $after));
    }

    /**
     * Get the underlying string value.
     */
    public function value(): string
    {
        return $this->toString();
    }

    /**
     * Get the underlying string value.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Get the underlying string value as an integer.
     */
    public function toInteger(): int
    {
        return (int) ($this->value);
    }

    /**
     * Get the underlying string value as a float.
     */
    public function toFloat(): float
    {
        return (float) ($this->value);
    }

    /**
     * Get the underlying string value as a boolean.
     *
     * Returns true when value is "1", "true", "on", and "yes". Otherwise, returns false.
     */
    public function toBoolean(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the underlying string value as a Carbon instance.
     *
     * @throws Exception for invalid format
     */
    public function toDate(?string $format = null, ?string $tz = null): Date
    {
        if (null === $format) {
            return Date::parse($this->value, $tz);
        }

        return Date::createFromFormat($format, $this->value, $tz);
    }

    /**
     * Convert the object to a string when JSON encoded.
     */
    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    /**
     * Proxy dynamic properties onto methods.
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->{$key}();
    }

    /**
     * Get the raw string value.
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
