<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\String\Inflector;
use BlitzPHP\Utilities\String\Text;

if (! function_exists('camelize')) {
    /**
     * Camelize
     *
     * Takes multiple words separated by spaces or underscores and camelizes them
     *
     * @param string $str Input string
     */
    function camelize(string $str): string
    {
        return Inflector::camelize($str);
    }
}

if (! function_exists('classify')) {
    /**
     * Returns model class name ("Person" for the database table "people".) for given database table.
     *
     * @param string $tableName Name of database table to get class name for
     *
     * @return string Class name
     */
    function classify(string $tableName): string
    {
        return Inflector::classify($tableName);
    }
}

if (! function_exists('dasherize')) {
    /**
     * Returns the input CamelCasedString as an dashed-string.
     *
     * Also replaces underscores with dashes
     *
     * @param string $string The string to dasherize.
     *
     * @return string Dashed version of the input string
     */
    function dasherize(string $string): string
    {
        return Inflector::dasherize($string);
    }
}

if (! function_exists('delimit')) {
    /**
     * Expects a CamelCasedInputString, and produces a lower_case_delimited_string
     *
     * @param string $string    String to delimit
     * @param string $delimiter the character to use as a delimiter
     *
     * @return string delimited string
     */
    function delimit(string $string, string $delimiter = '_'): string
    {
        return Inflector::delimit($string, $delimiter);
    }
}

if (! function_exists('humanize')) {
    /**
     * Humanize
     *
     * Takes multiple words separated by the separator and changes them to spaces
     *
     * @param string $str       Input string
     * @param string $separator Input separator
     */
    function humanize(string $str, string $separator = '_'): string
    {
        return Inflector::humanize($str, $separator);
    }
}

if (! function_exists('plural')) {
    /**
     * Plural
     *
     * Takes a singular word and makes it plural
     */
    function plural(string $str): string
    {
        if (! is_countable($str)) {
            return $str;
        }

        $plural_rules = [
            '/(quiz)$/'               => '\1zes',      // quizzes
            '/^(ox)$/'                => '\1\2en',     // ox
            '/([m|l])ouse$/'          => '\1ice',      // mouse, louse
            '/(matr|vert|ind)ix|ex$/' => '\1ices',     // matrix, vertex, index
            '/(x|ch|ss|sh)$/'         => '\1es',       // search, switch, fix, box, process, address
            '/([^aeiouy]|qu)y$/'      => '\1ies',      // query, ability, agency
            '/(hive)$/'               => '\1s',        // archive, hive
            '/(?:([^f])fe|([lr])f)$/' => '\1\2ves',    // half, safe, wife
            '/sis$/'                  => 'ses',        // basis, diagnosis
            '/([ti])um$/'             => '\1a',        // datum, medium
            '/(p)erson$/'             => '\1eople',    // person, salesperson
            '/(m)an$/'                => '\1en',       // man, woman, spokesman
            '/(c)hild$/'              => '\1hildren',  // child
            '/(buffal|tomat)o$/'      => '\1\2oes',    // buffalo, tomato
            '/(bu|campu)s$/'          => '\1\2ses',    // bus, campus
            '/(alias|status|virus)$/' => '\1es',       // alias
            '/(octop)us$/'            => '\1i',        // octopus
            '/(ax|cris|test)is$/'     => '\1es',       // axis, crisis
            '/s$/'                    => 's',          // no change (compatibility)
            '/$/'                     => 's',
        ];

        foreach ($plural_rules as $rule => $replacement) {
            if (preg_match($rule, $str)) {
                if (is_string($result = preg_replace($rule, $replacement, $str))) {
                    $str = $result;
                    break;
                }
            }
        }

        return $str;
    }
}

if (! function_exists('pluralize')) {
    /**
     * Return $word in plural form.
     *
     * @param string $word Word in singular
     *
     * @return string Word in plural
     */
    function pluralize(string $word): string
    {
        return Inflector::pluralize($word);
    }
}

if (! function_exists('singular')) {
    /**
     * Singular
     *
     * Takes a plural word and makes it singular
     */
    function singular(string $str): string
    {
        if (! is_countable($str)) {
            return $str;
        }

        $singular_rules = [
            '/(matr)ices$/'                                                   => '\1ix',
            '/(vert|ind)ices$/'                                               => '\1ex',
            '/^(ox)en/'                                                       => '\1',
            '/(alias)es$/'                                                    => '\1',
            '/([octop|vir])i$/'                                               => '\1us',
            '/(cris|ax|test)es$/'                                             => '\1is',
            '/(shoe)s$/'                                                      => '\1',
            '/(o)es$/'                                                        => '\1',
            '/(bus|campus)es$/'                                               => '\1',
            '/([m|l])ice$/'                                                   => '\1ouse',
            '/(x|ch|ss|sh)es$/'                                               => '\1',
            '/(m)ovies$/'                                                     => '\1\2ovie',
            '/(s)eries$/'                                                     => '\1\2eries',
            '/([^aeiouy]|qu)ies$/'                                            => '\1y',
            '/([lr])ves$/'                                                    => '\1f',
            '/(tive)s$/'                                                      => '\1',
            '/(hive)s$/'                                                      => '\1',
            '/([^f])ves$/'                                                    => '\1fe',
            '/(^analy)ses$/'                                                  => '\1sis',
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/' => '\1\2sis',
            '/([ti])a$/'                                                      => '\1um',
            '/(p)eople$/'                                                     => '\1\2erson',
            '/(m)en$/'                                                        => '\1an',
            '/(s)tatuses$/'                                                   => '\1\2tatus',
            '/(c)hildren$/'                                                   => '\1\2hild',
            '/(n)ews$/'                                                       => '\1\2ews',
            '/(quiz)zes$/'                                                    => '\1',
            '/([^us])s$/'                                                     => '\1',
        ];

        foreach ($singular_rules as $rule => $replacement) {
            if (preg_match($rule, $str)) {
                if (is_string($result = preg_replace($rule, $replacement, $str))) {
                    $str = $result;
                    break;
                }
            }
        }

        return $str;
    }
}

if (! function_exists('singularize')) {
    /**
     * Return $word in singular form.
     *
     * @param string $word Word in plural
     *
     * @return string Word in singular
     */
    function singularize(string $word): string
    {
        return Inflector::singularize($word);
    }
}

if (! function_exists('tableize')) {
    /**
     * Returns corresponding table name for given model $className. ("people" for the model class "Person").
     *
     * @param string $className Name of class to get database table name for
     *
     * @return string Name of the database table for given class
     */
    function tableize(string $className): string
    {
        return Inflector::tableize($className);
    }
}

if (! function_exists('underscore')) {
    /**
     * Underscore
     *
     * Takes multiple words separated by spaces and underscores them
     *
     * @param string $str Input string
     */
    function underscore(string $str): string
    {
        return Inflector::underscore($str);
    }
}

if (! function_exists('variable')) {
    /**
     * Returns camelBacked version of an underscored string.
     *
     * @param string $string String to convert.
     *
     * @return string in variable form
     */
    function variable(string $string): string
    {
        return Inflector::variable($string);
    }
}

if (! function_exists('counted')) {
    /**
     * Counted
     *
     * Takes a number and a word to return the plural or not
     * E.g. 0 cats, 1 cat, 2 cats, ...
     *
     * @param int    $count  Number of items
     * @param string $string Input string
     */
    function counted(int $count, string $string): string
    {
        $result = "{$count} ";

        return $result . ($count === 1 ? singular($string) : plural($string));
    }
}

if (! function_exists('pascalize')) {
    /**
     * Pascalize
     *
     * Takes multiple words separated by spaces or
     * underscores and converts them to Pascal case,
     * which is camel case with an uppercase first letter.
     *
     * @param string $string Input string
     */
    function pascalize(string $string): string
    {
        return Text::convertTo($string, 'pascalcase');
    }
}

if (! function_exists('is_pluralizable')) {
    /**
     * Checks if the given word has a plural version.
     *
     * @param string $word Word to check
     */
    function is_pluralizable(string $word): bool
    {
        return ! in_array(
            strtolower($word),
            [
                'advice',
                'audio',
                'bison',
                'bravery',
                'butter',
                'chaos',
                'chassis',
                'clarity',
                'coal',
                'compensation',
                'coreopsis',
                'courage',
                'cowardice',
                'curiosity',
                'data',
                'deer',
                'education',
                'emoji',
                'equipment',
                'evidence',
                'fish',
                'fun',
                'furniture',
                'gold',
                'greed',
                'help',
                'homework',
                'honesty',
                'information',
                'insurance',
                'jewelry',
                'knowledge',
                'livestock',
                'love',
                'luck',
                'marketing',
                'meta',
                'money',
                'moose',
                'mud',
                'news',
                'nutrition',
                'offspring',
                'patriotism',
                'plankton',
                'pokemon',
                'police',
                'racism',
                'rain',
                'rice',
                'satisfaction',
                'scenery',
                'series',
                'sexism',
                'sheep',
                'silence',
                'species',
                'spelling',
                'sugar',
                'swine',
                'traffic',
                'water',
                'weather',
                'wheat',
                'wisdom',
                'work',
            ],
            true
        );
    }
}

if (! function_exists('ordinal')) {
    /**
     * Returns the suffix that should be added to a
     * number to denote the position in an ordered
     * sequence such as 1st, 2nd, 3rd, 4th.
     *
     * @param int $integer The integer to determine the suffix
     */
    function ordinal(int $integer): string
    {
        $suffixes = [
            'th',
            'st',
            'nd',
            'rd',
            'th',
            'th',
            'th',
            'th',
            'th',
            'th',
        ];

        return $integer % 100 >= 11 && $integer % 100 <= 13 ? 'th' : $suffixes[$integer % 10];
    }
}

if (! function_exists('ordinalize')) {
    /**
     * Turns a number into an ordinal string used
     * to denote the position in an ordered sequence
     * such as 1st, 2nd, 3rd, 4th.
     *
     * @param int $integer The integer to ordinalize
     */
    function ordinalize(int $integer): string
    {
        return $integer . ordinal($integer);
    }
}
