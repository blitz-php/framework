<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Utilities\String;

use BlitzPHP\Utilities\Iterable\Collection;
use Countable;
use InvalidArgumentException;
use JsonException;
use Transliterator;
use Traversable;

if (! defined('MB_ENABLED')) {
    if (extension_loaded('mbstring')) {
        define('MB_ENABLED', true);
        // mbstring.internal_encoding est obsolète à partir de PHP 5.6
        // et son utilisation déclenche des messages E_DEPRECATED.
        @ini_set('mbstring.internal_encoding', $charset);
        // Ceci est requis pour que mb_convert_encoding() supprime les caractères invalides.
        // C'est utilisé par CI_Utf8, mais c'est aussi fait pour la cohérence avec iconv.
        mb_substitute_character('none');
    } else {
        define('MB_ENABLED', false);
    }
}
if (! defined('ICONV_ENABLED')) {
    if (extension_loaded('iconv')) {
        define('ICONV_ENABLED', true);
        // iconv.internal_encoding est obsolète à partir de PHP 5.6
        // et son utilisation déclenche des messages E_DEPRECATED.
        @ini_set('iconv.internal_encoding', $charset);
    } else {
        define('ICONV_ENABLED', false);
    }
}

class Str
{
    /**
     * Default transliterator.
     *
     * @var Transliterator Transliterator instance.
     */
    protected static $_defaultTransliterator;

    /**
     * Default transliterator id string.
     *
     * @var string Transliterator identifier string.
     */
    protected static $_defaultTransliteratorId = 'Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove';

    /**
     * Default html tags who must not be count for truncate text.
     *
     * @var array
     */
    protected static $_defaultHtmlNoCount = [
        'style',
        'script',
    ];

    /**
     * The cache of snake-cased words.
     *
     * @var array
     */
    protected static $snakeCache = [];

    /**
     * The cache of camel-cased words.
     *
     * @var array
     */
    protected static $camelCache = [];

    /**
     * The cache of studly-cased words.
     *
     * @var array
     */
    protected static $studlyCache = [];

    /**
     * The callback that should be used to generate random strings.
     *
     * @var callable|null
     */
    protected static $randomStringFactory;

    public static function __callStatic($name, $arguments)
    {
        /**
         * Conversion de casse d'ecriture
         */
        if (\preg_match('#^to(.*)(Case)?$#', $name)) {
            return self::convertTo($arguments[0], $name);
        }

        throw new InvalidArgumentException('Unknown method ' . __CLASS__ . '::' . $name);
    }

    /**
     * Convertissez des chaînes entre 13 conventions de nommage :
     * - Snake case, Camel case, Kebab case, Pascal case, Ada case, Train case, Cobol case, Macro case,
     * - majuscules, minuscules, Title case, Sentence Case et notation par points.
     *
     * @use \Jawira\CaseConverter\Convert
     */
    public static function convertTo(string $str, string $converter): string
    {
        $available_case = [
            'camel',
            'pascal',
            'snake',
            'ada',
            'macro',
            'kebab',
            'train',
            'cobol',
            'lower',
            'upper',
            'title',
            'sentence',
            'dot',
        ];

        $converter = preg_replace('#Case$#i', '', $converter);
        $converter = str_replace('to', '', strtolower($converter));

        if (! in_array($converter, $available_case, true)) {
            throw new InvalidArgumentException("Invalid converter type: `{$converter}`");
        }

        return call_user_func([new \Jawira\CaseConverter\Convert($str), 'to' . ucfirst($converter)]);
    }

    /**
     * Get a new stringable object from the given string.
     */
    public static function of(string $string): Stringable
    {
        return new Stringable($string);
    }

    /**
     * Return the remainder of a string after the first occurrence of a given value.
     */
    public static function after(string $subject, string $search): string
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    /**
     * Return the remainder of a string after the last occurrence of a given value.
     */
    public static function afterLast(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, (string) $search);

        if ($position === false) {
            return $subject;
        }

        return substr($subject, $position + strlen($search));
    }

    /**
     * Transliterate a UTF-8 value to ASCII.
     */
    public static function ascii(string $value, string $language = 'en'): string
    {
        $languageSpecific = static::languageSpecificCharsArray($language);

        if (null !== $languageSpecific) {
            $value = str_replace($languageSpecific[0], $languageSpecific[1], $value);
        }

        foreach (static::charsArray() as $key => $val) {
            $value = str_replace($val, $key, $value);
        }

        return preg_replace('/[^\x20-\x7E]/u', '', $value);
    }

    /**
     * Transliterate a string to its closest ASCII representation.
     */
    public static function transliterate(string $string, ?string $unknown = '?', ?bool $strict = false): string
    {
        return $string;
        // return ASCII::to_transliterate($string, $unknown, $strict);
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     */
    public static function before(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $result = strstr($subject, (string) $search, true);

        return $result === false ? $subject : $result;
    }

    /**
     * Get the portion of a string before the last occurrence of a given value.
     */
    public static function beforeLast(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $pos = mb_strrpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return static::substr($subject, 0, $pos);
    }

    /**
     * Get the portion of a string between two given values.
     */
    public static function between(string $subject, string $from, string $to): string
    {
        if ($from === '' || $to === '') {
            return $subject;
        }

        return static::beforeLast(static::after($subject, $from), $to);
    }

    /**
     * Get the smallest possible portion of a string between two given values.
     */
    public static function betweenFirst(string $subject, string $from, string $to): string
    {
        if ($from === '' || $to === '') {
            return $subject;
        }

        return static::before(static::after($subject, $from), $to);
    }

    /**
     * Convert a value to camel case.
     */
    public static function camel(string $value): string
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param iterable<string>|string $needles
     */
    public static function contains(string $haystack, $needles, bool $ignoreCase = false): bool
    {
        if ($ignoreCase) {
            $haystack = mb_strtolower($haystack);
        }

        if (! is_iterable($needles)) {
            $needles = (array) $needles;
        }

        foreach ($needles as $needle) {
            if ($ignoreCase) {
                $needle = mb_strtolower($needle);
            }

            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string contains all array values.
     *
     * @param string           $haystack
     * @param iterable<string> $needles
     * @param bool             $ignoreCase
     *
     * @return bool
     */
    public static function containsAll($haystack, $needles, $ignoreCase = false)
    {
        foreach ($needles as $needle) {
            if (! static::contains($haystack, $needle, $ignoreCase)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param string                  $haystack
     * @param iterable<string>|string $needles
     *
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        if (! is_iterable($needles)) {
            $needles = (array) $needles;
        }

        foreach ($needles as $needle) {
            if ((string) $needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extracts an excerpt from text that matches the first instance of a phrase.
     */
    public static function excerpt(string $text, string $phrase, array $options = []): string
    {
        $radius   = $options['radius'] ?? 100;
        $ellipsis = $options['ellipsis'] ?? '...';

        if (empty($text) || empty($phrase)) {
            return static::truncate($text, $radius * 2, ['ellipsis' => $ellipsis]);
        }

        $append = $prepend = $ellipsis;

        $phraseLen = mb_strlen($phrase);
        $textLen   = mb_strlen($text);

        $pos = mb_stripos($text, $phrase);

        if ($pos === false) {
            return mb_substr($text, 0, $radius) . $ellipsis;
        }

        $startPos = $pos - $radius;

        if ($startPos <= 0) {
            $startPos = 0;
            $prepend  = '';
        }

        $endPos = $pos + $phraseLen + $radius;

        if ($endPos >= $textLen) {
            $endPos = $textLen;
            $append = '';
        }

        $excerpt = mb_substr($text, $startPos, $endPos - $startPos);
        $excerpt = $prepend . $excerpt . $append;

        return $excerpt;
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @param string $value
     * @param string $cap
     *
     * @return string
     */
    public static function finish($value, $cap)
    {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }

    /**
     * Wrap the string with the given strings.
     *
     * @param string      $value
     * @param string      $before
     * @param string|null $after
     *
     * @return string
     */
    public static function wrap($value, $before, $after = null)
    {
        return $before . $value . ($after ??= $before);
    }

    /**
     * Determine if a given string matches a given pattern.
     *
     * @param iterable<string>|string $pattern
     * @param string                  $value
     *
     * @return bool
     */
    public static function is($pattern, $value)
    {
        $value = (string) $value;

        if (! is_iterable($pattern)) {
            $pattern = [$pattern];
        }

        foreach ($pattern as $pattern) {
            $pattern = (string) $pattern;

            // If the given value is an exact match we can of course return true right
            // from the beginning. Otherwise, we will translate asterisks and do an
            // actual pattern match against the two strings to see if they match.
            if ($pattern === $value) {
                return true;
            }

            $pattern = preg_quote($pattern, '#');

            // Asterisks are translated into zero-or-more regular expression wildcards
            // to make it convenient to check if the strings starts with the given
            // pattern such as "library/*", making any string check convenient.
            $pattern = str_replace('\*', '.*', $pattern);

            if (preg_match('#^' . $pattern . '\z#u', $value) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string is 7 bit ASCII.
     */
    public static function isAscii(string $value): bool
    {
        return preg_match('/[^\x00-\x7F]/S', $value) === 0;
    }

    /**
     * Determine if a given string is valid JSON.
     *
     * @param string $value
     */
    public static function isJson($value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        try {
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        return true;
    }

    /**
     * Determine if a given string is a valid UUID.
     *
     * @param string $value
     */
    public static function isUuid($value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return preg_match('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/iD', $value) > 0;
    }

    /**
     * Determine if a given string is a valid ULID.
     *
     * @param string $value
     */
    public static function isUlid($value)
    {
        if (! is_string($value)) {
            return false;
        }

        return false;
        // return Ulid::isValid($value);
    }

    /**
     * Convert a string to kebab case.
     */
    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    /**
     * Return the length of the given string.
     */
    public static function length(string $value, ?string $encoding = null): int
    {
        if ($encoding) {
            return mb_strlen($value, $encoding);
        }

        return mb_strlen($value);
    }

    /**
     * Limit the number of characters in a string.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Convert the given string to lower-case.
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Limit the number of words in a string.
     */
    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);

        if (! isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]) . $end;
    }

    /**
     * Masks a portion of a string with a repeated character.
     */
    public static function mask(string $string, string $character, int $index, ?int $length = null, string $encoding = 'UTF-8'): string
    {
        if ($character === '') {
            return $string;
        }

        $segment = mb_substr($string, $index, $length, $encoding);

        if ($segment === '') {
            return $string;
        }

        $strlen     = mb_strlen($string, $encoding);
        $startIndex = $index;

        if ($index < 0) {
            $startIndex = $index < -$strlen ? 0 : $strlen + $index;
        }

        $start      = mb_substr($string, 0, $startIndex, $encoding);
        $segmentLen = mb_strlen($segment, $encoding);
        $end        = mb_substr($string, $startIndex + $segmentLen);

        return $start . str_repeat(mb_substr($character, 0, 1, $encoding), $segmentLen) . $end;
    }

    /**
     * Get the string matching the given pattern.
     */
    public static function match(string $pattern, string $subject): string
    {
        preg_match($pattern, $subject, $matches);

        if (! $matches) {
            return '';
        }

        return $matches[1] ?? $matches[0];
    }

    /**
     * Get the string matching the given pattern.
     */
    public static function matchAll(string $pattern, string $subject): Collection
    {
        preg_match_all($pattern, $subject, $matches);

        if (empty($matches[0])) {
            return new Collection();
        }

        return new Collection($matches[1] ?? $matches[0]);
    }

    /**
     * Pad both sides of a string with another.
     */
    public static function padBoth(string $value, int $length, string $pad = ' '): string
    {
        $short      = max(0, $length - mb_strlen($value));
        $shortLeft  = floor($short / 2);
        $shortRight = ceil($short / 2);

        return mb_substr(str_repeat($pad, $shortLeft), 0, $shortLeft) .
               $value .
               mb_substr(str_repeat($pad, $shortRight), 0, $shortRight);
    }

    /**
     * Pad the left side of a string with another.
     *
     * @param string $value
     * @param int    $length
     * @param string $pad
     *
     * @return string
     */
    public static function padLeft($value, $length, $pad = ' ')
    {
        $short = max(0, $length - mb_strlen($value));

        return mb_substr(str_repeat($pad, $short), 0, $short) . $value;
    }

    /**
     * Pad the right side of a string with another.
     *
     * @param string $value
     * @param int    $length
     * @param string $pad
     *
     * @return string
     */
    public static function padRight($value, $length, $pad = ' ')
    {
        $short = max(0, $length - mb_strlen($value));

        return $value . mb_substr(str_repeat($pad, $short), 0, $short);
    }

    /**
     * Parse a Class[@]method style callback into class and method.
     *
     * @param string      $callback
     * @param string|null $default
     *
     * @return array<int, string|null>
     */
    public static function parseCallback($callback, $default = null)
    {
        return static::contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
    }

    /**
     * Get the plural form of an English word.
     *
     * @param array|Countable|int $count
     */
    public static function plural(string $value, $count = 2): string
    {
        return Inflector::pluralize($value);
        // return Pluralizer::plural($value, $count);
    }

    /**
     * Pluralize the last word of an English, studly caps case string.
     *
     * @param array|Countable|int $count
     */
    public static function pluralStudly(string $value, $count = 2): string
    {
        $parts = preg_split('/(.)(?=[A-Z])/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE);

        $lastWord = array_pop($parts);

        return implode('', $parts) . self::plural($lastWord, $count);
    }

    /**
     * Generate a more truly "random" alpha-numeric string.
     */
    public static function random(int $length = 16): string
    {
        return (static::$randomStringFactory ?? static function ($length) {
            $string = '';

            while (($len = strlen($string)) < $length) {
                $size = $length - $len;

                $bytesSize = (int) ceil(($size) / 3) * 3;

                $bytes = random_bytes($bytesSize);

                $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
            }

            return $string;
        })($length);
    }

    /**
     * Set the callable that will be used to generate random strings.
     *
     * @return void
     */
    public static function createRandomStringsUsing(?callable $factory = null)
    {
        static::$randomStringFactory = $factory;
    }

    /**
     * Set the sequence that will be used to generate random strings.
     *
     * @return void
     */
    public static function createRandomStringsUsingSequence(array $sequence, ?callable $whenMissing = null)
    {
        $next = 0;

        $whenMissing ??= static function ($length) use (&$next) {
            $factoryCache = static::$randomStringFactory;

            static::$randomStringFactory = null;

            $randomString = static::random($length);

            static::$randomStringFactory = $factoryCache;

            $next++;

            return $randomString;
        };

        static::createRandomStringsUsing(static function ($length) use (&$next, $sequence, $whenMissing) {
            if (array_key_exists($next, $sequence)) {
                return $sequence[$next++];
            }

            return $whenMissing($length);
        });
    }

    /**
     * Indicate that random strings should be created normally and not using a custom factory.
     *
     * @return void
     */
    public static function createRandomStringsNormally()
    {
        static::$randomStringFactory = null;
    }

    /**
     * Repeat the given string.
     */
    public static function repeat(string $string, int $times): string
    {
        return str_repeat($string, $times);
    }

    /**
     * Replace a given value in the string sequentially with an array.
     *
     * @param iterable<string> $replace
     */
    public static function replaceArray(string $search, $replace, string $subject): string
    {
        if ($replace instanceof Traversable) {
            $replace = Collection::make($replace)->all();
        }

        $segments = explode($search, $subject);

        $result = array_shift($segments);

        foreach ($segments as $segment) {
            $result .= (array_shift($replace) ?? $search) . $segment;
        }

        return $result;
    }

    /**
     * Replace the given value in the given string.
     *
     * @param iterable<string>|string $search
     * @param iterable<string>|string $replace
     * @param iterable<string>|string $subject
     */
    public static function replace($search, $replace, $subject): string
    {
        if ($search instanceof Traversable) {
            $search = Collection::make($search)->all();
        }

        if ($replace instanceof Traversable) {
            $replace = Collection::make($replace)->all();
        }

        if ($subject instanceof Traversable) {
            $subject = Collection::make($subject)->all();
        }

        return str_replace($search, $replace, $subject);
    }

    /**
     * Replace the first occurrence of a given value in the string.
     */
    public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * Replace the last occurrence of a given value in the string.
     */
    public static function replaceLast(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * Remove any occurrence of the given string in the subject.
     *
     * @param iterable<string>|string $search
     */
    public static function remove($search, string $subject, bool $caseSensitive = true): string
    {
        if ($search instanceof Traversable) {
            $search = Collection::make($search)->all();
        }

        return $caseSensitive
                    ? str_replace($search, '', $subject)
                    : str_ireplace($search, '', $subject);
    }

    /**
     * Reverse the given string.
     */
    public static function reverse(string $value): string
    {
        return implode('', array_reverse(mb_str_split($value)));
    }

    /**
     * Begin a string with a single instance of a given value.
     */
    public static function start(string $value, string $prefix): string
    {
        $quoted = preg_quote($prefix, '/');

        return $prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $value);
    }

    /**
     * Convert the given string to upper-case.
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Convert the given string to title case.
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert the given string to title case for each word.
     */
    public static function headline(string $value): string
    {
        $parts = explode(' ', $value);

        $parts = count($parts) > 1
            ? array_map([static::class, 'title'], $parts)
            : array_map([static::class, 'title'], static::ucsplit(implode('_', $parts)));

        $collapsed = static::replace(['-', '_', ' '], '_', implode('_', $parts));

        return implode(' ', array_filter(explode('_', $collapsed)));
    }

    /**
     * Get the singular form of an English word.
     */
    public static function singular(string $value): string
    {
        return Inflector::singularize($value);
    }

    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param array<string, string> $dictionary
     */
    public static function slug(string $title, string $separator = '-', ?string $language = 'en', array $dictionary = ['@' => 'at']): string
    {
        $title = $language ? static::ascii($title, $language) : $title;

        // Convert all dashes/underscores into separator
        $flip = $separator === '-' ? '_' : '-';

        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);

        // Replace dictionary words
        foreach ($dictionary as $key => $value) {
            $dictionary[$key] = $separator . $value . $separator;
        }

        $title = str_replace(array_keys($dictionary), array_values($dictionary), $title);

        // Remove all characters that are not the separator, letters, numbers, or whitespace
        $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', static::lower($title));

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);

        return trim($title, $separator);
    }

    /**
     * Convert a string to snake case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $key = $value;

        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }

        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * Remove all "extra" blank space from the given string.
     */
    public static function squish(string $value): string
    {
        return preg_replace('~(\s|\x{3164})+~u', ' ', preg_replace('~^[\s\x{FEFF}]+|[\s\x{FEFF}]+$~u', '', $value));
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param iterable<string>|string $needles
     */
    public static function startsWith(string $haystack, $needles): bool
    {
        if (! is_iterable($needles)) {
            $needles = [$needles];
        }

        foreach ($needles as $needle) {
            if ((string) $needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert a value to studly caps case.
     */
    public static function studly(string $value): string
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $words = explode(' ', static::replace(['-', '_'], ' ', $value));

        $studlyWords = array_map(static fn ($word) => static::ucfirst($word), $words);

        return static::$studlyCache[$key] = implode('', $studlyWords);
    }

    /**
     * Returns the portion of the string specified by the start and length parameters.
     */
    public static function substr(string $string, int $start, ?int $length = null, string $encoding = 'UTF-8'): string
    {
        return mb_substr($string, $start, $length, $encoding);
    }

    /**
     * Returns the number of substring occurrences.
     */
    public static function substrCount(string $haystack, string $needle, int $offset = 0, ?int $length = null): int
    {
        if (null !== $length) {
            return substr_count($haystack, $needle, $offset, $length);
        }

        return substr_count($haystack, $needle, $offset);
    }

    /**
     * Replace text within a portion of a string.
     *
     * @param string|string[] $string
     * @param string|string[] $replace
     * @param int|int[]       $offset
     * @param int|int[]|null  $length
     *
     * @return string|string[]
     */
    public static function substrReplace($string, $replace, $offset = 0, $length = null)
    {
        if ($length === null) {
            $length = strlen($string);
        }

        return substr_replace($string, $replace, $offset, $length);
    }

    /**
     * Swap multiple keywords in a string with other keywords.
     */
    public static function swap(array $map, string $subject): string
    {
        return strtr($subject, $map);
    }

    /**
     * Make a string's first character lowercase.
     */
    public static function lcfirst(string $string): string
    {
        return static::lower(static::substr($string, 0, 1)) . static::substr($string, 1);
    }

    /**
     * Make a string's first character uppercase.
     */
    public static function ucfirst(string $string): string
    {
        return static::upper(static::substr($string, 0, 1)) . static::substr($string, 1);
    }

    /**
     * Split a string into pieces by uppercase characters.
     *
     * @return string[]
     */
    public static function ucsplit(string $string): array
    {
        return preg_split('/(?=\p{Lu})/u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Get the number of words a string contains.
     */
    public static function wordCount(string $string, ?string $characters = null): int
    {
        return str_word_count($string, 0, $characters);
    }

    /**
     * Remove all strings from the casing caches.
     *
     * @return void
     */
    public static function flushCache()
    {
        static::$snakeCache  = [];
        static::$camelCache  = [];
        static::$studlyCache = [];
    }
}
