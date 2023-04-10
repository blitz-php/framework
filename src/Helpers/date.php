<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\Date;

/**
 * FONCTIONS DE MANIPULATION DES DATES
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - date_helper</a>
 */

if (! function_exists('now')) {
    /**
     * Get "now" time
     *
     * Returns Date::now()->getTimestamp() based on the timezone parameter or on the
     * app.timezone setting
     * 
     * @return Date|int
     */
    function now(?string $timezone = null, bool $returnObject = true)
    {
        $timezone = empty($timezone) ? config('app.timezone') : $timezone;

        if ($returnObject) {
            return Date::now($timezone);
        }

        if ($timezone === 'local' || $timezone === date_default_timezone_get()) {
            return Date::now()->getTimestamp();
        }

        $time = Date::now($timezone);
        sscanf(
            $time->format('j-n-Y G:i:s'),
            '%d-%d-%d %d:%d:%d',
            $day,
            $month,
            $year,
            $hour,
            $minute,
            $second
        );

        return mktime($hour, $minute, $second, $month, $day, $year);
    }
}

if (! function_exists('timezone_select')) {
    /**
     * Generates a select field of all available timezones
     *
     * Returns a string with the formatted HTML
     *
     * @param string $class   Optional class to apply to the select field
     * @param string $default Default value for initial selection
     * @param int    $what    One of the DateTimeZone class constants (for listIdentifiers)
     * @param string $country A two-letter ISO 3166-1 compatible country code (for listIdentifiers)
     *
     * @throws Exception
     */
    function timezone_select(string $class = '', string $default = '', int $what = DateTimeZone::ALL, ?string $country = null): string
    {
        $timezones = DateTimeZone::listIdentifiers($what, $country);

        $buffer = "<select name='timezone' class='{$class}'>\n";

        foreach ($timezones as $timezone) {
            $selected = ($timezone === $default) ? 'selected' : '';
            $buffer .= "<option value='{$timezone}' {$selected}>{$timezone}</option>\n";
        }

        return $buffer . ("</select>\n");
    }
}
