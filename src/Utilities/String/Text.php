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

/**
 * @credit      CakePHP (Cake\Utility\Text - https://cakephp.org)
 * @credit      Laravel (Illuminate\Support\Str - https://laravel.com)
 */
class Text
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
            return static::convertTo($arguments[0], $name);
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
     * Clean UTF-8 strings
     *
     * Ensures strings contain only valid UTF-8 characters.
     */
    public static function clean(string $str): string
    {
        if (static::isAscii($str) === false) {
            if (MB_ENABLED) {
                $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            } elseif (ICONV_ENABLED) {
                $str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
            }
        }

        return $str;
    }

    /**
     * Cleans up a static::insert() formatted string with given $options depending on the 'clean' key in
     * $options. The default method used is text but html is also available. The goal of this function
     * is to replace all whitespace and unneeded markup around placeholders that did not get replaced
     * by static::insert().
     */
    public static function cleanInsert(string $str, array $options): string
    {
        $clean = $options['clean'];
        if (! $clean) {
            return $str;
        }

        if ($clean === true) {
            $clean = ['method' => 'text'];
        }

        if (! is_array($clean)) {
            $clean = ['method' => $options['clean']];
        }

        switch ($clean['method']) {
            case 'html':
                $clean += [
                    'word'        => '[\w,.]+',
                    'andText'     => true,
                    'replacement' => '',
                ];
                $kleenex = sprintf(
                    '/[\s]*[a-z]+=(")(%s%s%s[\s]*)+\\1/i',
                    preg_quote($options['before'], '/'),
                    $clean['word'],
                    preg_quote($options['after'], '/')
                );
                $str = preg_replace($kleenex, $clean['replacement'], $str);
                if ($clean['andText']) {
                    $options['clean'] = ['method' => 'text'];
                    $str              = static::cleanInsert($str, $options);
                }
                break;

            case 'text':
                $clean += [
                    'word'        => '[\w,.]+',
                    'gap'         => '[\s]*(?:(?:and|or)[\s]*)?',
                    'replacement' => '',
                ];

                $kleenex = sprintf(
                    '/(%s%s%s%s|%s%s%s%s)/',
                    preg_quote($options['before'], '/'),
                    $clean['word'],
                    preg_quote($options['after'], '/'),
                    $clean['gap'],
                    $clean['gap'],
                    preg_quote($options['before'], '/'),
                    $clean['word'],
                    preg_quote($options['after'], '/')
                );
                $str = preg_replace($kleenex, $clean['replacement'], $str);
                break;
        }

        return $str;
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
     * @param iterable<string> $needles
     */
    public static function containsAll(string $haystack, iterable $needles, bool $ignoreCase = false): bool
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
     */
    public static function finish(string $value, string $cap): string
    {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }

    /**
     * Highlights a given phrase in a text. You can specify any expression in highlighter that
     * may include the \1 expression to include the $phrase found.
     *
     * ### Options:
     *
     * - `format` The piece of HTML with that the phrase will be highlighted
     * - `html` If true, will ignore any HTML tags, ensuring that only the correct text is highlighted
     * - `regex` A custom regex rule that is used to match words, default is '|$tag|iu'
     * - `limit` A limit, optional, defaults to -1 (none)
     *
     * @param string       $text    Text to search the phrase in.
     * @param array|string $phrase  The phrase or phrases that will be searched.
     * @param array        $options An array of HTML attributes and options.
     */
    public static function highlight(string $text, array|string $phrase, array $options = []): string
    {
        if (empty($phrase)) {
            return $text;
        }

        $defaults = [
            'format' => '<span class="highlight">\1</span>',
            'html'   => false,
            'regex'  => '|%s|iu',
            'limit'  => -1,
        ];
        $options += $defaults;

        $html = $format = $limit = null;

        /**
         * @var bool         $html
         * @var array|string $format
         * @var int          $limit
         */
        extract($options);

        if (is_array($phrase)) {
            $replace = [];
            $with    = [];

            foreach ($phrase as $key => $segment) {
                $segment = '(' . preg_quote($segment, '|') . ')';
                if ($html) {
                    $segment = "(?![^<]+>){$segment}(?![^<]+>)";
                }

                $with[]    = is_array($format) ? $format[$key] : $format;
                $replace[] = sprintf($options['regex'], $segment);
            }

            return preg_replace($replace, $with, $text, $limit);
        }

        $phrase = '(' . preg_quote($phrase, '|') . ')';
        if ($html) {
            $phrase = "(?![^<]+>){$phrase}(?![^<]+>)";
        }

        return preg_replace(sprintf($options['regex'], $phrase), $format, $text, $limit);
    }

    /**
     * Wrap the string with the given strings.
     */
    public static function wrap(string $value, string $before, ?string $after = null): string
    {
        return $before . $value . ($after ??= $before);
    }

    /**
     * Replaces variable placeholders inside a $str with any given $data. Each key in the $data array
     * corresponds to a variable placeholder name in $str.
     * Example:
     * ```
     * Text::insert(':name is :age years old.', ['name' => 'Bob', 'age' => '65']);
     * ```
     * Returns: Bob is 65 years old.
     *
     * Available $options are:
     *
     * - before: The character or string in front of the name of the variable placeholder (Defaults to `:`)
     * - after: The character or string after the name of the variable placeholder (Defaults to null)
     * - escape: The character or string used to escape the before character / string (Defaults to `\`)
     * - format: A regex to use for matching variable placeholders. Default is: `/(?<!\\)\:%s/`
     *   (Overwrites before, after, breaks escape / clean)
     * - clean: A boolean or array with instructions for Text::cleanInsert
     *
     * @param string $str     A string containing variable placeholders
     * @param array  $data    A key => val array where each key stands for a placeholder variable name
     *                        to be replaced with val
     * @param array  $options An array of options, see description above
     */
    public static function insert(string $str, $data, array $options = []): string
    {
        $defaults = [
            'before' => ':', 'after' => null, 'escape' => '\\', 'format' => null, 'clean' => false,
        ];
        $options += $defaults;
        $format = $options['format'];
        $data   = (array) $data;
        if (empty($data)) {
            return $options['clean'] ? static::cleanInsert($str, $options) : $str;
        }

        if (! isset($format)) {
            $format = sprintf(
                '/(?<!%s)%s%%s%s/',
                preg_quote($options['escape'], '/'),
                str_replace('%', '%%', preg_quote($options['before'], '/')),
                str_replace('%', '%%', preg_quote($options['after'], '/'))
            );
        }

        if (strpos($str, '?') !== false && is_numeric(key($data))) {
            $offset = 0;

            while (($pos = strpos($str, '?', $offset)) !== false) {
                $val    = array_shift($data);
                $offset = $pos + strlen($val);
                $str    = substr_replace($str, $val, $pos, 1);
            }

            return $options['clean'] ? static::cleanInsert($str, $options) : $str;
        }

        $dataKeys = array_keys($data);
        $hashKeys = array_map('crc32', $dataKeys);
        $tempData = array_combine($dataKeys, $hashKeys);
        krsort($tempData);

        foreach ($tempData as $key => $hashVal) {
            $key = sprintf($format, preg_quote($key, '/'));
            $str = preg_replace($key, $hashVal, $str);
        }
        $dataReplacements = array_combine($hashKeys, array_values($data));

        foreach ($dataReplacements as $tmpHash => $tmpValue) {
            $tmpValue = is_array($tmpValue) ? '' : $tmpValue;
            $str      = str_replace($tmpHash, $tmpValue, $str);
        }

        if (! isset($options['format']) && isset($options['before'])) {
            $str = str_replace($options['escape'] . $options['before'], $options['before'], $str);
        }

        return $options['clean'] ? static::cleanInsert($str, $options) : $str;
    }

    /**
     * Determine if a given string matches a given pattern.
     *
     * @param iterable<string>|string $pattern
     */
    public static function is(iterable|string $pattern, string $value): bool
    {
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
        return Uuid::isValid($value);
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
     * @return array<int, string|null>
     */
    public static function parseCallback(string $callback, ?string $default = null): array
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

        return implode('', $parts) . static::plural($lastWord, $count);
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
     * Removes the last word from the input text.
     */
    public static function removeLastWord(string $text): string
    {
        $spacepos = mb_strrpos($text, ' ');

        if ($spacepos !== false) {
            $lastWord = mb_strrpos($text, $spacepos);

            // Some languages are written without word separation.
            // We recognize a string as a word if it doesn't contain any full-width characters.
            if (mb_strwidth($lastWord) === mb_strlen($lastWord)) {
                $text = mb_substr($text, 0, $spacepos);
            }

            return $text;
        }

        return '';
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
     * Get string length.
     *
     * ### Options:
     *
     * - `html` If true, HTML entities will be handled as decoded characters.
     * - `trimWidth` If true, the width will return.
     */
    public static function strlen(string $text, array $options): int
    {
        if (empty($options['trimWidth'])) {
            $strlen = 'mb_strlen';
        } else {
            $strlen = 'mb_strwidth';
        }

        if (empty($options['html'])) {
            return $strlen($text);
        }

        $pattern = '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i';
        $replace = preg_replace_callback(
            $pattern,
            function ($match) use ($strlen) {
                $utf8 = html_entity_decode($match[0], ENT_HTML5 | ENT_QUOTES, 'UTF-8');

                return str_repeat(' ', $strlen($utf8, 'UTF-8'));
            },
            $text
        );

        return $strlen($replace);
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
     * Returns the portion of string specified by the start and length parameters.
     *
     * ### Options:
     *
     * - `html` If true, HTML entities will be handled as decoded characters.
     * - `trimWidth` If true, will be truncated with specified width.
     */
    public static function substr(string $text, int $start, ?int $length = null, array $options = []): string
    {
        if (empty($options['encoding'])) {
            $options['encoding'] = 'UTF-8';
        }

        if (empty($options['trimWidth'])) {
            $substr = 'mb_substr';
        } else {
            $substr = 'mb_strimwidth';
        }

        $maxPosition = static::strlen($text, ['trimWidth' => false] + $options);
        if ($start < 0) {
            $start += $maxPosition;
            if ($start < 0) {
                $start = 0;
            }
        }
        if ($start >= $maxPosition) {
            return '';
        }

        if ($length === null) {
            $length = static::strlen($text, $options);
        }

        if ($length < 0) {
            $text  = static::substr($text, $start, null, $options);
            $start = 0;
            $length += static::strlen($text, $options);
        }

        if ($length <= 0) {
            return '';
        }

        if (empty($options['html'])) {
            return (string) $substr($text, $start, $length);
        }

        $totalOffset = 0;
        $totalLength = 0;
        $result      = '';

        $pattern = '/(&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};)/i';
        $parts   = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        foreach ($parts as $part) {
            $offset = 0;

            if ($totalOffset < $start) {
                $len = static::strlen($part, ['trimWidth' => false] + $options);
                if ($totalOffset + $len <= $start) {
                    $totalOffset += $len;

                    continue;
                }

                $offset      = $start - $totalOffset;
                $totalOffset = $start;
            }

            $len = static::strlen($part, $options);
            if ($offset !== 0 || $totalLength + $len > $length) {
                if (
                    strpos($part, '&') === 0 && preg_match($pattern, $part)
                    && $part !== html_entity_decode($part, ENT_HTML5 | ENT_QUOTES, $options['encoding'])
                ) {
                    // Entities cannot be passed substr.
                    continue;
                }

                $part = $substr($part, $offset, $length - $totalLength);
                $len  = static::strlen($part, $options);
            }

            $result .= $part;
            $totalLength += $len;
            if ($totalLength >= $length) {
                break;
            }
        }

        return $result;
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
     * Truncates text starting from the end.
     *
     * Cuts a string to the length of $length and replaces the first characters
     * with the ellipsis if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as beginning and prepended to the trimmed string
     * - `exact` If false, $text will not be cut mid-word
     */
    public static function tail(string $text, int $length = 100, array $options = []): string
    {
        $default = [
            'ellipsis' => '...', 'exact' => true,
        ];
        $options += $default;
        $exact = $ellipsis = null;
        /**
         * @var string $ellipsis
         * @var bool   $exact
         */
        extract($options);

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $truncate = mb_substr($text, mb_strlen($text) - $length + mb_strlen($ellipsis));
        if (! $exact) {
            $spacepos = mb_strpos($truncate, ' ');
            $truncate = $spacepos === false ? '' : trim(mb_substr($truncate, $spacepos));
        }

        return $ellipsis . $truncate;
    }

    /**
     * Tokenizes a string using $separator, ignoring any instance of $separator that appears between
     * $leftBound and $rightBound.
     */
    public static function tokenize(string $data, string $separator = ',', string $leftBound = '(', string $rightBound = ')'): array
    {
        if (empty($data)) {
            return [];
        }

        $depth   = 0;
        $offset  = 0;
        $buffer  = '';
        $results = [];
        $length  = strlen($data);
        $open    = false;

        while ($offset <= $length) {
            $tmpOffset = -1;
            $offsets   = [
                strpos($data, $separator, $offset),
                strpos($data, $leftBound, $offset),
                strpos($data, $rightBound, $offset),
            ];

            for ($i = 0; $i < 3; $i++) {
                if ($offsets[$i] !== false && ($offsets[$i] < $tmpOffset || $tmpOffset === -1)) {
                    $tmpOffset = $offsets[$i];
                }
            }
            if ($tmpOffset !== -1) {
                $buffer .= substr($data, $offset, ($tmpOffset - $offset));
                if (! $depth && $data[$tmpOffset] === $separator) {
                    $results[] = $buffer;
                    $buffer    = '';
                } else {
                    $buffer .= $data[$tmpOffset];
                }
                if ($leftBound !== $rightBound) {
                    if ($data[$tmpOffset] === $leftBound) {
                        $depth++;
                    }
                    if ($data[$tmpOffset] === $rightBound) {
                        $depth--;
                    }
                } else {
                    if ($data[$tmpOffset] === $leftBound) {
                        if (! $open) {
                            $depth++;
                            $open = true;
                        } else {
                            $depth--;
                        }
                    }
                }
                $offset = ++$tmpOffset;
            } else {
                $results[] = $buffer . substr($data, $offset);
                $offset    = $length + 1;
            }
        }
        if (empty($results) && ! empty($buffer)) {
            $results[] = $buffer;
        }

        return array_map('trim', $results);
    }

    /**
     * Creates a comma separated list where the last two items are joined with 'and', forming natural language.
     *
     * @param string[] $list The list to be joined.
     *
     * @return string The glued together string.
     */
    public static function toList(array $list, string $separator = ', ', string $and = 'and')
    {
        if (count($list) > 1) {
            return implode($separator, array_slice($list, 0, -1)) . ' ' . $and . ' ' . array_pop($list);
        }

        return array_pop($list);
    }

    /**
     * Convert to UTF-8
     *
     * Attempts to convert a string to UTF-8.
     *
     * @return false|string $str encoded in UTF-8 or FALSE on failure
     */
    public static function toUtf8(string $str, string $encoding)
    {
        if (MB_ENABLED) {
            return mb_convert_encoding($str, 'UTF-8', $encoding);
        }

        if (ICONV_ENABLED) {
            return @iconv($encoding, 'UTF-8', $str);
        }

        return false;
    }

    /**
     * Transliterate string.
     *
     * @param string|Transliterator|null $transliterator Either a Transliterator
     *                                                   instance, or a transliterator identifier string. If `null`, the default
     *                                                   transliterator (identifier) set via `setTransliteratorId()` or
     *                                                   `setTransliterator()` will be used.
     *
     * @return false|string
     *
     * @see https://secure.php.net/manual/en/transliterator.transliterate.php
     */
    public static function transliterate(string $string, $transliterator = null)
    {
        if (! $transliterator) {
            $transliterator = static::$_defaultTransliterator ?: static::$_defaultTransliteratorId;
        }

        return transliterator_transliterate($transliterator, $string);
    }

    /**
     * Truncates text.
     *
     * Cuts a string to the length of $length and replaces the last characters
     * with the ellipsis if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as ending and appended to the trimmed string
     * - `exact` If false, $text will not be cut mid-word
     * - `html` If true, HTML tags would be handled correctly
     * - `trimWidth` If true, $text will be truncated with the width
     */
    public static function truncate(string $text, int $length = 100, array $options = []): string
    {
        $default = [
            'ellipsis' => '...', 'exact' => true, 'html' => false, 'trimWidth' => false,
        ];
        if (! empty($options['html']) && strtolower(mb_internal_encoding()) === 'utf-8') {
            $default['ellipsis'] = "\xe2\x80\xa6";
        }
        $options += $default;

        $prefix = '';
        $suffix = $options['ellipsis'];

        if ($options['html']) {
            $ellipsisLength = self::strlen(strip_tags($options['ellipsis']), $options);

            $truncateLength = 0;
            $totalLength    = 0;
            $openTags       = [];
            $truncate       = '';

            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);

            foreach ($tags as $tag) {
                $contentLength = 0;
                if (! in_array($tag[2], static::$_defaultHtmlNoCount, true)) {
                    $contentLength = self::strlen($tag[3], $options);
                }

                if ($truncate === '') {
                    if (! preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/i', $tag[2])) {
                        if (preg_match('/<[\w]+[^>]*>/', $tag[0])) {
                            array_unshift($openTags, $tag[2]);
                        } elseif (preg_match('/<\/([\w]+)[^>]*>/', $tag[0], $closeTag)) {
                            $pos = array_search($closeTag[1], $openTags, true);
                            if ($pos !== false) {
                                array_splice($openTags, $pos, 1);
                            }
                        }
                    }

                    $prefix .= $tag[1];

                    if ($totalLength + $contentLength + $ellipsisLength > $length) {
                        $truncate       = $tag[3];
                        $truncateLength = $length - $totalLength;
                    } else {
                        $prefix .= $tag[3];
                    }
                }

                $totalLength += $contentLength;
                if ($totalLength > $length) {
                    break;
                }
            }

            if ($totalLength <= $length) {
                return $text;
            }

            $text   = $truncate;
            $length = $truncateLength;

            foreach ($openTags as $tag) {
                $suffix .= '</' . $tag . '>';
            }
        } else {
            if (self::strlen($text, $options) <= $length) {
                return $text;
            }
            $ellipsisLength = self::strlen($options['ellipsis'], $options);
        }

        $result = self::substr($text, 0, $length - $ellipsisLength, $options);

        if (! $options['exact']) {
            if (self::substr($text, $length - $ellipsisLength, 1, $options) !== ' ') {
                $result = self::removeLastWord($result);
            }

            // If result is empty, then we don't need to count ellipsis in the cut.
            if (! strlen($result)) {
                $result = self::substr($text, 0, $length, $options);
            }
        }

        return $prefix . $result . $suffix;
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

    /**
     * Get the default transliterator.
     */
    public static function getTransliterator(): ?Transliterator
    {
        return static::$_defaultTransliterator;
    }

    /**
     * Set the default transliterator.
     */
    public static function setTransliterator(Transliterator $transliterator): void
    {
        static::$_defaultTransliterator = $transliterator;
    }

    /**
     * Get default transliterator identifier string.
     */
    public static function getTransliteratorId(): string
    {
        return static::$_defaultTransliteratorId;
    }

    /**
     * Set default transliterator identifier string.
     */
    public static function setTransliteratorId(string $transliteratorId)
    {
        static::setTransliterator(transliterator_create($transliteratorId));
        static::$_defaultTransliteratorId = $transliteratorId;
    }

    /**
     * Returns the replacements for the ascii method.
     *
     * Note: Adapted from Stringy\Stringy.
     *
     * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
     */
    protected static function charsArray(): array
    {
        static $charsArray;

        if (isset($charsArray)) {
            return $charsArray;
        }

        return $charsArray = [
            '0'    => ['°', '₀', '۰', '０'],
            '1'    => ['¹', '₁', '۱', '１'],
            '2'    => ['²', '₂', '۲', '２'],
            '3'    => ['³', '₃', '۳', '３'],
            '4'    => ['⁴', '₄', '۴', '٤', '４'],
            '5'    => ['⁵', '₅', '۵', '٥', '５'],
            '6'    => ['⁶', '₆', '۶', '٦', '６'],
            '7'    => ['⁷', '₇', '۷', '７'],
            '8'    => ['⁸', '₈', '۸', '８'],
            '9'    => ['⁹', '₉', '۹', '９'],
            'a'    => ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å', 'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά', 'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ', 'အ', 'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ا', 'ａ', 'ä', 'א'],
            'b'    => ['б', 'β', 'ب', 'ဗ', 'ბ', 'ｂ', 'ב'],
            'c'    => ['ç', 'ć', 'č', 'ĉ', 'ċ', 'ｃ'],
            'd'    => ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ', 'ｄ', 'ד'],
            'e'    => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э', 'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'إ', 'ئ', 'ｅ'],
            'f'    => ['ф', 'φ', 'ف', 'ƒ', 'ფ', 'ｆ', 'פ', 'ף'],
            'g'    => ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ', 'ｇ', 'ג'],
            'h'    => ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ', 'ｈ', 'ה'],
            'i'    => ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į', 'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი', 'इ', 'ی', 'ｉ', 'י'],
            'j'    => ['ĵ', 'ј', 'Ј', 'ჯ', 'ج', 'ｊ'],
            'k'    => ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ', 'ک', 'ｋ', 'ק'],
            'l'    => ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ', 'ｌ', 'ל'],
            'm'    => ['м', 'μ', 'م', 'မ', 'მ', 'ｍ', 'מ', 'ם'],
            'n'    => ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န', 'ნ', 'ｎ', 'נ'],
            'o'    => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő', 'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό', 'о', 'و', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ', 'ｏ', 'ö'],
            'p'    => ['п', 'π', 'ပ', 'პ', 'پ', 'ｐ', 'פ', 'ף'],
            'q'    => ['ყ', 'ｑ'],
            'r'    => ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ', 'ｒ', 'ר'],
            's'    => ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ', 'ſ', 'ს', 'ｓ', 'ס'],
            't'    => ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ', 'ｔ', 'ת'],
            'u'    => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ', 'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ', 'ｕ', 'ў', 'ü'],
            'v'    => ['в', 'ვ', 'ϐ', 'ｖ', 'ו'],
            'w'    => ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ', 'ｗ'],
            'x'    => ['χ', 'ξ', 'ｘ'],
            'y'    => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ي', 'ယ', 'ｙ'],
            'z'    => ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ', 'ｚ', 'ז'],
            'aa'   => ['ع', 'आ', 'آ'],
            'ae'   => ['æ', 'ǽ'],
            'ai'   => ['ऐ'],
            'ch'   => ['ч', 'ჩ', 'ჭ', 'چ'],
            'dj'   => ['ђ', 'đ'],
            'dz'   => ['џ', 'ძ', 'דז'],
            'ei'   => ['ऍ'],
            'gh'   => ['غ', 'ღ'],
            'ii'   => ['ई'],
            'ij'   => ['ĳ'],
            'kh'   => ['х', 'خ', 'ხ'],
            'lj'   => ['љ'],
            'nj'   => ['њ'],
            'oe'   => ['ö', 'œ', 'ؤ'],
            'oi'   => ['ऑ'],
            'oii'  => ['ऒ'],
            'ps'   => ['ψ'],
            'sh'   => ['ш', 'შ', 'ش', 'ש'],
            'shch' => ['щ'],
            'ss'   => ['ß'],
            'sx'   => ['ŝ'],
            'th'   => ['þ', 'ϑ', 'θ', 'ث', 'ذ', 'ظ'],
            'ts'   => ['ц', 'ც', 'წ'],
            'ue'   => ['ü'],
            'uu'   => ['ऊ'],
            'ya'   => ['я'],
            'yu'   => ['ю'],
            'zh'   => ['ж', 'ჟ', 'ژ'],
            '(c)'  => ['©'],
            'A'    => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą', 'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ', 'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ', 'Ａ', 'Ä'],
            'B'    => ['Б', 'Β', 'ब', 'Ｂ'],
            'C'    => ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ', 'Ｃ'],
            'D'    => ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ', 'Ｄ'],
            'E'    => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э', 'Є', 'Ə', 'Ｅ'],
            'F'    => ['Ф', 'Φ', 'Ｆ'],
            'G'    => ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ', 'Ｇ'],
            'H'    => ['Η', 'Ή', 'Ħ', 'Ｈ'],
            'I'    => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į', 'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ', 'Ｉ'],
            'J'    => ['Ｊ'],
            'K'    => ['К', 'Κ', 'Ｋ'],
            'L'    => ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल', 'Ｌ'],
            'M'    => ['М', 'Μ', 'Ｍ'],
            'N'    => ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν', 'Ｎ'],
            'O'    => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő', 'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ', 'Ό', 'О', 'Ө', 'Ǒ', 'Ǿ', 'Ｏ', 'Ö'],
            'P'    => ['П', 'Π', 'Ｐ'],
            'Q'    => ['Ｑ'],
            'R'    => ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ', 'Ｒ'],
            'S'    => ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ', 'Ｓ'],
            'T'    => ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ', 'Ｔ'],
            'U'    => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ', 'Ǘ', 'Ǚ', 'Ǜ', 'Ｕ', 'Ў', 'Ü'],
            'V'    => ['В', 'Ｖ'],
            'W'    => ['Ω', 'Ώ', 'Ŵ', 'Ｗ'],
            'X'    => ['Χ', 'Ξ', 'Ｘ'],
            'Y'    => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ', 'Ｙ'],
            'Z'    => ['Ź', 'Ž', 'Ż', 'З', 'Ζ', 'Ｚ'],
            'AE'   => ['Æ', 'Ǽ'],
            'Ch'   => ['Ч'],
            'Dj'   => ['Ђ'],
            'Dz'   => ['Џ'],
            'Gx'   => ['Ĝ'],
            'Hx'   => ['Ĥ'],
            'Ij'   => ['Ĳ'],
            'Jx'   => ['Ĵ'],
            'Kh'   => ['Х'],
            'Lj'   => ['Љ'],
            'Nj'   => ['Њ'],
            'Oe'   => ['Œ'],
            'Ps'   => ['Ψ'],
            'Sh'   => ['Ш', 'ש'],
            'Shch' => ['Щ'],
            'Ss'   => ['ẞ'],
            'Th'   => ['Þ', 'Θ', 'ת'],
            'Ts'   => ['Ц'],
            'Ya'   => ['Я', 'יא'],
            'Yu'   => ['Ю', 'יו'],
            'Zh'   => ['Ж'],
            ' '    => ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80", "\xEF\xBE\xA0"],
        ];
    }

    /**
     * Returns the language specific replacements for the ascii method.
     *
     * Note: Adapted from Stringy\Stringy.
     *
     * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
     */
    protected static function languageSpecificCharsArray(string $language): ?array
    {
        static $languageSpecific;

        if (! isset($languageSpecific)) {
            $languageSpecific = [
                'bg' => [
                    ['х', 'Х', 'щ', 'Щ', 'ъ', 'Ъ', 'ь', 'Ь'],
                    ['h', 'H', 'sht', 'SHT', 'a', 'А', 'y', 'Y'],
                ],
                'da' => [
                    ['æ', 'ø', 'å', 'Æ', 'Ø', 'Å'],
                    ['ae', 'oe', 'aa', 'Ae', 'Oe', 'Aa'],
                ],
                'de' => [
                    ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü'],
                    ['ae', 'oe', 'ue', 'AE', 'OE', 'UE'],
                ],
                'he' => [
                    ['א', 'ב', 'ג', 'ד', 'ה', 'ו'],
                    ['ז', 'ח', 'ט', 'י', 'כ', 'ל'],
                    ['מ', 'נ', 'ס', 'ע', 'פ', 'צ'],
                    ['ק', 'ר', 'ש', 'ת', 'ן', 'ץ', 'ך', 'ם', 'ף'],
                ],
                'ro' => [
                    ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
                    ['a', 'a', 'i', 's', 't', 'A', 'A', 'I', 'S', 'T'],
                ],
            ];
        }

        return $languageSpecific[$language] ?? null;
    }
}
