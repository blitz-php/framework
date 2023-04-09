<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Utilities\Exceptions;

use Exception;

/**
 * @credit <a href="http://www.codeigniter.com">CodeIgniter 4.2 - CodeIgniter\I18n\Exceptions\I18nException</a>
 */
class DateException extends Exception
{
    /**
     * Thrown when createFromFormat fails to receive a valid
     * DateTime back from DateTime::createFromFormat.
     *
     * @return static
     */
    public static function invalidFormat(string $format)
    {
        return new static('"'.$format.'" is not a valid datetime format');
    }

    /**
     * Thrown when the numeric representation of the month falls
     * outside the range of allowed months.
     *
     * @return static
     */
    public static function invalidMonth(string $month)
    {
        return new static('Months must be between 1 and 12. Given: ' . $month);
    }

    /**
     * Thrown when the supplied day falls outside the range
     * of allowed days.
     *
     * @return static
     */
    public static function invalidDay(string $day)
    {
        return new static('Days must be between 1 and 31. Given: ' . $day);
    }

    /**
     * Thrown when the day provided falls outside the allowed
     * last day for the given month.
     *
     * @return static
     */
    public static function invalidOverDay(string $lastDay, string $day)
    {
        return new static('Days must be between 1 and ' . $lastDay . '. Given: ' . $day);
    }

    /**
     * Thrown when the supplied hour falls outside the
     * range of allowed hours.
     *
     * @return static
     */
    public static function invalidHour(string $hour)
    {
        return new static('Hours must be between 0 and 23. Given: ' . $hour);
    }

    /**
     * Thrown when the supplied minutes falls outside the
     * range of allowed minutes.
     *
     * @return static
     */
    public static function invalidMinutes(string $minutes)
    {
        return new static('Minutes must be between 0 and 59. Given: ' . $minutes);
    }

    /**
     * Thrown when the supplied seconds falls outside the
     * range of allowed seconds.
     *
     * @return static
     */
    public static function invalidSeconds(string $seconds)
    {
        return new static('Seconds must be between 0 and 59. Given: ' . $seconds);
    }
}
