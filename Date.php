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

use BlitzPHP\Utilities\Exceptions\DateException;
use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use DateInterval;
use DateTimeInterface;
use IntlCalendar;
use IntlDateFormatter;

/**
 * Date class encapsulates various date and time functionality.
 *
 * @method int getDay() Get day of month.
 * @method int getMonth() Get the month.
 * @method int getYear() Get the year.
 * @method int getHour() Get the hour.
 * @method int getMinute() Get the minutes.
 * @method int getSecond() Get the seconds.
 * @method string getDayOfWeek() Get the day of the week, e.g., Monday.
 * @method int getDayOfWeekAsNumeric() Get the numeric day of week.
 * @method int getDaysInMonth() Get the number of days in the month.
 * @method int getDayOfYear() Get the day of the year.
 * @method string getDaySuffix() Get the suffix of the day, e.g., st.
 * @method bool isLeapYear() Determines if is leap year.
 * @method string isAmOrPm() Determines if time is AM or PM.
 * @method bool isDaylightSavings() Determines if observing daylight savings.
 * @method int getGmtDifference() Get difference in GMT.
 * @method int getSecondsSinceEpoch() Get the number of seconds since epoch.
 * @method string getTimezoneName() Get the timezone name.
 * @method setDay(int $day) Set the day of month.
 * @method setMonth(int $month) Set the month.
 * @method setYear(int $year) Set the year.
 * @method setHour(int $hour) Set the hour.
 * @method setMinute(int $minute) Set the minutes.
 * @method setSecond(int $second) Set the seconds.
 *
 * @credit
 */
class Date extends DateTime
{
	const DEFAULT_TIMEZONE = 'UTC'; // UTC&#65533;00:00 Coordinated Universal Time

	/**
	 * Default date format used when casting object to string.
	 */
	protected string $defaultDateFormat = 'jS F, Y \a\\t g:ia';

	/**
	 * Starting day of the week, where 0 is Sunday and 1 is Monday.
	 */
	protected int $weekStartDay = 0;

	/**
	* Used to check time string to determine if it is relative time or not....
	*/
   protected static string $relativePattern = '/this|next|last|tomorrow|yesterday|midnight|today|[+-]|first|last|ago/i';


    protected ?DateTimeZone $timezone = null;

    /**
     * @var DateTimeInterface|static|null
     */
    protected static $testNow;

	/**
	 * Create a new Date instance.
	 */
	public function __construct(?string $time = '', string|DateTimeZone|null $timezone = self::DEFAULT_TIMEZONE, protected ?string $locale = null)
	{
        $time ??= '';

        // If a test instance has been provided, use it instead.
        if ($time === '' && static::$testNow instanceof self) {
            if ($timezone !== null) {
                $testNow = static::$testNow->setTimezone($timezone);
                $time    = $testNow->format('Y-m-d H:i:s');
            } else {
                $timezone = static::$testNow->getTimezone();
                $time     = static::$testNow->format('Y-m-d H:i:s');
            }
        }

        $timezone       = $timezone ?: date_default_timezone_get();
        $this->timezone = $this->parseSuppliedTimezone($timezone);

        // If the time string was a relative string (i.e. 'next Tuesday')
        // then we need to adjust the time going in so that we have a current
        // timezone to work with.
        if ($time !== '' && static::hasRelativeKeywords($time)) {
            $instance = new DateTime('now', $this->timezone);
            $instance->modify($time);
            $time = $instance->format('Y-m-d H:i:s');
        }

        parent::__construct($time, $this->timezone);
	}

	/**
	 * Dynamically handle calls for date attributes and testers.
	 */
	public function __call(string $method, array $parameters = []): mixed
	{
		if (substr($method, 0, 3) == 'get' or substr($method, 0, 3) == 'set') {
			$attribute = substr($method, 3);
		}
		elseif (substr($method, 0, 2) == 'is') {
			$attribute = substr($method, 2);

			return $this->isDateAttribute($attribute);
		}

		if ( ! isset($attribute)) {
			throw new InvalidArgumentException('Could not dynamically handle method call ['.$method.']');
		}

		if (substr($method, 0, 3) == 'set') {
			return $this->setDateAttribute($attribute, $parameters[0]);
		}

		// If not setting an attribute then we'll default to getting an attribute.
		return $this->getDateAttribute($attribute);
	}

    /**
     * Allow for property-type access to any getX method...
     *
     * Note that we cannot use this for any of our setX methods,
     * as they return new Time objects, but the __set ignores
     * return values.
     * See http://php.net/manual/en/language.oop5.overloading.php
     *
     *
     * @return array|bool|DateTimeInterface|DateTimeZone|int|intlCalendar|self|string|null
     */
    public function __get(string $name)
    {
        $method = 'get' . ucfirst($name);

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return null;
    }

    /**
     * Allow for property-type checking to any getX method...
     */
    public function __isset(string $name): bool
    {
        $method = 'get' . ucfirst($name);

        return method_exists($this, $method);
    }

    /**
     * Outputs a short format version of the datetime.
     * The output is NOT localized intentionally.
     */
    public function __toString(): string
    {
        return $this->getDefaultDate();
    }

    /**
     * This is called when we unserialize the Time object.
     */
    public function __wakeup(): void
    {
        /**
         * Prior to unserialization, this is a string.
         *
         * @var string $timezone
         */
        $timezone = $this->timezone;

        $this->timezone = new DateTimeZone($timezone);
        parent::__construct($this->date, $this->timezone);
    }

	/**
	 * Make and return new Date instance.
	 *
	 * @return static
	 */
	public static function create(string $time = 'now', string|DateTimeZone|null $timezone = null, ?string $locale = null)
	{
		return new static($time, $timezone, $locale);
	}

	/**
	 * Make and return a new Date instance with defined year, month, and day.
	 *
	 * @return static
	 */
	public static function createFromDate(?int $year = null, ?int $month = null, ?int $day = null, string|DateTimeZone $timezone = null)
	{
		return static::createFromDateTime($year, $month, $day, null, null, null, $timezone);
	}

	/**
	 * Make and return a new Date instance with defined year, month, day, hour, minute, and second.
	 *
	 * @return static
	 */
	public static function createFromDateTime(?int $year = null, ?int $month = null, ?int $day = null, ?int $hour = null, ?int $minutes = null, ?int $seconds = null, string|DateTimeZone $timezone = null)
	{
		$year ??= date('Y');
        $month ??= date('m');
        $day ??= date('d');
        $hour    = empty($hour) ? 0 : $hour;
        $minutes = empty($minutes) ? 0 : $minutes;
        $seconds = empty($seconds) ? 0 : $seconds;

        return new static(date('Y-m-d H:i:s', strtotime("{$year}-{$month}-{$day} {$hour}:{$minutes}:{$seconds}")), $timezone);
	}

	/**
	 * Parse a string into a new DateTime object according to the specified format
	 *
	 * @return static
	 */
	public static function createFromFormat(string $format, string $datetime, DateTimeZone $timezone = null): static
	{
		$date = parent::createFromFormat($format, $datetime, $timezone);

		return static::create($date->format('Y-m-d H:i:s'), $timezone);
	}

	/**
     * Takes an instance of DateTimeInterface and returns an instance of Time with it's same values.
	 *
	 * @return static
     */
    public static function createFromInstance(DateTimeInterface $dateTime)
    {
        $date     = $dateTime->format('Y-m-d H:i:s');
        $timezone = $dateTime->getTimezone();

        return new self($date, $timezone);
    }

	/**
	 * Make and return a new Date instance with defined hour, minute, and second.
	 *
	 * @return static
	 */
	public static function createFromTime(?int $hour = null, ?int $minutes = null, ?int $seconds = null, string|DateTimeZone $timezone = null)
	{
		return static::createFromDateTime(null, null, null, $hour, $minutes, $seconds, $timezone);
	}

	/**
     * Returns a new instance with the datetime set based on the provided UNIX timestamp.
     *
     * @return static
     */
    public static function createFromTimestamp(int $timestamp, DateTimeZone|string $timezone = null)
    {
        return static::create(gmdate('Y-m-d H:i:s', $timestamp), $timezone);
    }

	/**
	 * Returns the current date and time as a DateTime object or formatted string
	 *
	 * @param string|null $format [OPTIONAL] If specified, will format the date as a string
	 *                       If not specified, returns a DateTime object
	 *	                     (example: 'Y-m-d H:i:s')
	 *
	 * @return static|string The DateTime object or a formatted string
	 */
	public static function now(?string $format = null, DateTimeZone|string $timezone = null)
	{
		$date = new static(null, $timezone);
		$date->setTimestamp(time());

		if (null !== $format) {
			return $date->format($format);
		}

        return $date;
	}

    /**
     * Returns a new Date instance while parsing a datetime string.
     *
     * Example:
     *  $time = Date::parse('first day of April 2023');
     *
     * @return static
     */
    public static function parse(string $datetime, DateTimeZone|string|null $timezone = null, ?string $locale = null)
    {
        return new static($datetime, $timezone, $locale);
    }

	/**
	 * Returns the current date (not time) as a DateTime object or formatted string
	 *
	 * @param string|null $format [OPTIONAL] If specified, will format the date as a string
	 *                       If not specified, returns a DateTime object
	 *	                     (example: 'Y-m-d')
	 *
	 * @return static|string The DateTime object or a formatted string
	 */
	public static function today(?string $format = null, string|DateTimeZone $timezone = null)
	{
		$date = new static(date('Y-m-d 00:00:00'), $timezone);

		if (null !== $format) {
			return $date->format($format);
		}

        return $date;
	}

	/**
	 * Returns the date (not time) of tomorrow as a DateTime object or formatted string
	 *
	 * @param string|null $format [OPTIONAL] If specified, will format the date as a string
	 *                       If not specified, returns a DateTime object
	 *	                     (example: 'Y-m-d')
	 *
	 * @return static|string The DateTime object or a formatted string
	 */
	public static function tomorrow(?string $format = null, DateTimeZone|string $timezone = null)
	{
		$date = new static(date('Y-m-d 00:00:00', strtotime('+1 day')), $timezone);

		if (null !== $format) {
			return $date->format($format);
		}

		return $date;
	}

	/**
	 * Returns the date (not time) of yesterday as a DateTime object or formatted string
	 *
	 * @param string|null $format [OPTIONAL] If specified, will format the date as a string
	 *                       If not specified, returns a DateTime object
	 *	                     (example: 'Y-m-d')
	 *
	 * @return static|string The DateTime object or a formatted string
	 */
	public static function yesterday(?string $format = null, DateTimeZone|string $timezone = null)
	{
		$date = new static(date('Y-m-d 00:00:00', strtotime('-1 day')), $timezone);

		if (null !== $format) {
			return $date->format($format);
		}

		return $date;
	}

	/**
	 * Return copy of expressive date object
	 */
	public function copy(): static
	{
		return clone $this;
	}

	/**
     * Converts the current instance to a mutable DateTime object.
     */
    public function toDateTime(): DateTime
    {
        $dateTime = new DateTime('', $this->getTimezone());
        $dateTime->setTimestamp(parent::getTimestamp());

        return $dateTime;
    }


    // --------------------------------------------------------------------
    // Formatters
    // --------------------------------------------------------------------

	/**
	 * Converts any English textual datetimes into a date object
	 *
	 * @return DateTimeInterface|false Date if valid and false if not
	 */
	public static function convertToDate(string|DateTimeInterface $date, string $timezone = self::DEFAULT_TIMEZONE)
    {
		if (is_string($date)) {
			// Set the timezone to default
			date_default_timezone_set($timezone);

			$datevalue = $date;

			// Convert the string into a linux time stamp
			$timestamp = strtotime($datevalue);

			// If this was a valid date
			if ($timestamp) {
				// Convert the UNIX time stamp into a date object
				$date = DateTime::createFromFormat('U', $timestamp);
			}
            else  {
                // Not a valid date... This was not a valid date
				$date = false;
			}
		}
		else if (intval($date->format('Y')) <= 0) {
			$date = false;
		}

		// Return the date object or false if invalid
		return $date;
	}

	/**
     * Returns the localized value of the date in the format 'Y-m-d H:i:s'
	 */
    public function toDateTimeString(): string
    {
        return $this->toLocalizedString('yyyy-MM-dd HH:mm:ss');
    }

    /**
     * Returns a localized version of the date in Y-m-d format.
    */
    public function toDateString(): string
    {
        return $this->toLocalizedString('yyyy-MM-dd');
    }

    /**
     * Returns a localized version of the date in nicer date format:
     *
     *  i.e. Apr 1, 2017
     */
    public function toFormattedDateString(): string
    {
        return $this->toLocalizedString('MMM d, yyyy');
    }

    /**
     * Returns a localized version of the time in nicer date format:
     *
     *  i.e. 13:20:33
     */
    public function toTimeString(): string
    {
        return $this->toLocalizedString('HH:mm:ss');
    }

    /**
     * Returns the localized value of this instance in $format.
     *
     * @return false|string
     */
    public function toLocalizedString(?string $format = null)
    {
        $format ??= $this->defaultDateFormat;

        return IntlDateFormatter::formatObject($this->toDateTime(), $format, $this->locale);
    }

    // --------------------------------------------------------------------
    // For Testing
    // --------------------------------------------------------------------

    /**
     * Creates an instance of Date that will be returned during testing
     * when calling 'Date::now()' instead of the current time.
     */
    public static function setTestNow(DateTimeInterface|string|null $datetime = null, DateTimeZone|string|null $timezone = null, string $locale = null)
    {
        // Reset the test instance
        if ($datetime === null) {
            static::$testNow = null;

            return;
        }

        // Convert to a Time instance
        if (is_string($datetime)) {
            $datetime = new self($datetime, $timezone, $locale);
        } elseif ($datetime instanceof DateTimeInterface && ! $datetime instanceof self) {
            $datetime = new self($datetime->format('Y-m-d H:i:s'), $timezone);
        }

        static::$testNow = $datetime;
    }

    /**
     * Returns whether we have a testNow instance saved.
     */
    public static function hasTestNow(): bool
    {
        return static::$testNow !== null;
    }

	// --------------------------------------------------------------------
    // Getters
    // --------------------------------------------------------------------

    /**
     * Returns the age in years from the date and 'now'
     */
    public function getAge(): int
    {
        // future dates have no age
        return max(0, $this->differenceYears(static::now()));
    }

    /**
     * Returns the IntlCalendar object used for this object,
     * taking into account the locale, date, etc.
     *
     * Primarily used internally to provide the difference and comparison functions,
     * but available for public consumption if they need it.
     *
     * @return IntlCalendar
     *
     * @throws Exception
     */
    public function getCalendar()
    {
        return IntlCalendar::fromDateTime($this, $this->locale);
    }

	/**
	 * Get a date string in the format of 2012-12-04.
	 */
	public function getDate(): string
	{
		return $this->format('Y-m-d');
	}

	/**
	 * Get a date and time string in the format of 2012-12-04 23:43:27.
	 */
	public function getDateTime(): string
	{
		return $this->format('Y-m-d H:i:s');
	}

	/**
	 * Get a date string in the format of 07:42:32.
	 */
	public function getTime(): string
	{
		return $this->format('H:i:s');
	}

	/**
	 * Get a date string in the default format.
	 */
	public function getDefaultDate(): string
	{
		return $this->format($this->defaultDateFormat);
	}

	/**
	 * Get a date string in the format of January 31st, 1991 at 7:45am.
	 */
	public function getLongDate(): string
	{
		return $this->format('F jS, Y \a\\t g:ia');
	}

	/**
	 * Get a date string in the format of Jan 31, 1991.
	 */
	public function getShortDate(): string
	{
		return $this->format('M j, Y');
	}

	/**
     * Return the localized day of the month.
     */
    public function getDay(): string
    {
        return $this->toLocalizedString('d');
    }

    /**
     * Return the index of the day of the week.
     */
    public function getDayOfWeek(): string
    {
        return $this->toLocalizedString('c');
    }

    /**
     * Return the index of the day of the year
     */
    public function getDayOfYear(): string
    {
        return $this->toLocalizedString('D');
    }

    /**
     * Return the localized hour (in 24-hour format).
     */
    public function getHour(): string
    {
        return $this->toLocalizedString('H');
    }

    /**
     * Return the localized minutes in the hour.
     */
    public function getMinute(): string
    {
        return $this->toLocalizedString('m');
    }

	/**
     * Returns the localized Month
     */
    public function getMonth(): string
    {
        return $this->toLocalizedString('M');
    }

    /**
     * Returns the number of the current quarter for the year.
     */
    public function getQuarter(): string
    {
        return $this->toLocalizedString('Q');
    }

    /**
     * Return the localized seconds.
     */
    public function getSecond(): string
    {
        return $this->toLocalizedString('s');
    }

    /**
     * Return the index of the week in the month.
     */
    public function getWeekOfMonth(): string
    {
        return $this->toLocalizedString('W');
    }

    /**
     * Return the index of the week in the year.
     */
    public function getWeekOfYear(): string
    {
        return $this->toLocalizedString('w');
    }

    /**
     * Returns the localized Year
     */
    public function getYear(): string
    {
        return $this->toLocalizedString('y');
    }

	/**
	 * Get the starting day of the week, where 0 is Sunday and 1 is Monday
	 */
	public function getWeekStartDay(): int
	{
		return $this->weekStartDay;
	}

    /**
     * Are we in daylight savings time currently?
     */
    public function isDst(): bool
    {
        return $this->format('I') === '1'; // 1 if Daylight Saving Time, 0 otherwise.
    }

    /**
     * Returns boolean whether the passed timezone is the same as
     * the local timezone.
     */
    public function isLocal(): bool
    {
        return date_default_timezone_get() === $this->timezoneName();
    }

    /**
     * Returns boolean whether object is in UTC.
     */
    public function isUtc(): bool
    {
        return $this->getOffset() === 0;
    }

	/**
	 * Determine if day is a weekday.
	 */
	public function isWeekday(): bool
	{
		$day = $this->getDayOfWeek();

		return ! in_array($day, ['Saturday', 'Sunday']);
	}

	/**
	 * Determine if day is a weekend.
	 */
	public function isWeekend(): bool
	{
		return ! $this->isWeekday();
	}

    /**
     * Returns the name of the current timezone.
     */
    public function timezoneName(): string
    {
        return $this->timezone->getName();
    }

	// --------------------------------------------------------------------
    // Setters
    // --------------------------------------------------------------------

    /**
     * Sets the day of the month.
     *
     * @return static
     */
    public function setDay(int|string $value)
    {
        if ($value < 1 || $value > 31) {
            throw DateException::invalidDay($value);
        }

        $date    = $this->getYear() . '-' . $this->getMonth();
        $lastDay = date('t', strtotime($date));
        if ($value > $lastDay) {
            throw DateException::invalidOverDay($lastDay, $value);
        }

        return $this->setValue('day', $value);
    }

    /**
     * Sets the hour of the day (24 hour cycle)
     *
     * @return static
     */
    public function setHour(int|string $value)
    {
        if ($value < 0 || $value > 23) {
            throw DateException::invalidHour($value);
        }

        return $this->setValue('hour', $value);
    }

    /**
     * Sets the minute of the hour.
     *
     * @return static
     */
    public function setMinute(int|string $value)
    {
        if ($value < 0 || $value > 59) {
            throw DateException::invalidMinutes($value);
        }

        return $this->setValue('minute', $value);
    }

    /**
     * Sets the month of the year.
     *
     * @return static
     */
    public function setMonth(int|string $value)
    {
        if (is_numeric($value) && ($value < 1 || $value > 12)) {
            throw DateException::invalidMonth($value);
        }

        if (is_string($value) && ! is_numeric($value)) {
            $value = date('m', strtotime("{$value} 1 2017"));
        }

        return $this->setValue('month', $value);
    }

    /**
     * Sets the second of the minute.
     *
     * @return static
     */
    public function setSecond(int|string $value)
    {
        if ($value < 0 || $value > 59) {
            throw DateException::invalidSeconds($value);
        }

        return $this->setValue('second', $value);
    }

    /**
     * Sets the current year for this instance.
     *
     * @return static
     */
    public function setYear(int|string $value)
    {
        return $this->setValue('year', $value);
    }

	/**
	 * Set the default date format.
	 */
	public function setDefaultDateFormat(string $format): self
	{
		$this->defaultDateFormat = $format;

		return $this;
	}

	/**
	 * Returns a new instance with the revised timezone.
	 */
	public function setTimezone(string|DateTimeZone $timezone): static
	{
		$this->timezone = $this->parseSuppliedTimezone($timezone);

		return static::createFromInstance($this->toDateTime()->setTimezone($this->timezone));
	}

    /**
     * Returns a new instance with the date set to the new timestamp.
     */
    public function setTimestamp(int $timestamp): static
    {
        $time = date('Y-m-d H:i:s', $timestamp);

        return static::parse($time, $this->timezone, $this->locale);
    }

	/**
	 * Sets the timestamp from a human readable string.
	 */
	public function setTimestampFromString(string $string): self
	{
		$this->setTimestamp(strtotime($string));

		return $this;
	}

	/**
	 * Set the starting day of the week, where 0 is Sunday and 1 is Monday.
	 */
	public function setWeekStartDay(int|string $weekStartDay): static
	{
		if (is_numeric($weekStartDay)) {
			$this->weekStartDay = $weekStartDay;
		}
		else {
			$this->weekStartDay = array_search(strtolower($weekStartDay), ['sunday', 'monday']);
		}

		return $this;
	}

	/**
     * Helper method to do the heavy lifting of the 'setX' methods.
     *
     * @return static
     */
    protected function setValue(string $name, int|string $value)
    {
        [$year, $month, $day, $hour, $minute, $second] = explode('-', $this->format('Y-n-j-G-i-s'));

        ${$name} = $value;

        return static::create(
            (int) $year,
            (int) $month,
            (int) $day,
            (int) $hour,
            (int) $minute,
            (int) $second,
            $this->getTimezoneName(),
            $this->locale
        );
    }

    // --------------------------------------------------------------------
    // Add/Subtract
    // --------------------------------------------------------------------

	/**
	 * Add a given amount of days.
	 */
	public function addDays(int|float $value): static
	{
		return $this->modifyDays($value);
	}

	/**
	 * Add a given amount of hours.
	 */
	public function addHours(int|float $value): static
	{
		return $this->modifyHours($value);
	}

	/**
	 * Add a given amount of minutes.
	 */
	public function addMinutes(int|float $value): static
	{
		return $this->modifyMinutes($value);
	}

	/**
	 * Add a given amount of months.
	 */
	public function addMonths(int|float $value): static
	{
		return $this->modifyMonths($value);
	}

	/**
	 * Add one day.
	 */
	public function addOneDay(): static
	{
		return $this->addDays(1);
	}

	/**
	 * Add one hour.
	 */
	public function addOneHour(): static
	{
		return $this->addHours(1);
	}

	/**
	 * Add one minute.
	 */
	public function addOneMinute(): static
	{
		return $this->addMinutes(1);
	}

	/**
	 * Add one month.
	 */
	public function addOneMonth(): static
	{
		return $this->addMonths(1);
	}

	/**
	 * Add one year.
	 */
	public function addOneYear(): static
	{
		return $this->addYears(1);
	}

	/**
	 * Add one second.
	 */
	public function addOneSecond(): static
	{
		return $this->addSeconds(1);
	}

	/**
	 * Add one week.
	 */
	public function addOneWeek(): static
	{
		return $this->addWeeks(1);
	}

	/**
	 * Add a given amount of seconds.
	 */
	public function addSeconds(int|float $value): static
	{
		return $this->modifySeconds($value);
	}

	/**
	 * Add a given amount of weeks.
	 */
	public function addWeeks(int|float $value): static
	{
		return $this->modifyWeeks($value);
	}

	/**
	 * Add a given amount of years.
	 */
	public function addYears(int|float $value): static
	{
		return $this->modifyYears($value);
	}

	/**
	 * Minus a given amount of days.
	 */
	public function subDays(int|float $value): static
	{
		return $this->modifyDays($value, true);
	}

	/**
	 * Minus a given amount of hours.
	 */
	public function subHours(int|float $value): static
	{
		return $this->modifyHours($value, true);
	}

	/**
	 * Minus a given amount of minutes.
	 */
	public function subMinutes(int|float $value): static
	{
		return $this->modifyMinutes($value, true);
	}

	/**
	 * Minus a given amount of months.
	 */
	public function subMonths(int|float $value): static
	{
		return $this->modifyMonths($value, true);
	}

	/**
	 * Minus one day.
	 */
	public function subOneDay(): static
	{
		return $this->subDays(1);
	}

	/**
	 * Minus one hour.
	 */
	public function subOneHour(): static
	{
		return $this->subHours(1);
	}

	/**
	 * Minus one minute.
	 */
	public function subOneMinute(): static
	{
		return $this->subMinutes(1);
	}

	/**
	 * Minus one month.
	 */
	public function subOneMonth(): static
	{
		return $this->subMonths(1);
	}

	/**
	 * Minus one second.
	 */
	public function subOneSecond(): static
	{
		return $this->subSeconds(1);
	}

	/**
	 * Minus one week.
	 */
	public function subOneWeek(): static
	{
		return $this->subWeeks(1);
	}

	/**
	 * Minus one year.
	 */
	public function subOneYear(): static
	{
		return $this->subYears(1);
	}

	/**
	 * Minus a given amount of seconds.
	 */
	public function subSeconds(int|float $value): static
	{
		return $this->modifySeconds($value, true);
	}

	/**
	 * Minus a given amount of weeks.
	 */
	public function subWeeks(int|float $value): static
	{
		return $this->modifyWeeks($value, true);
	}

	/**
	 * Minus a given amount of years.
	 */
	public function subYears(int|float $value): static
	{
		return $this->modifyYears($value, true);
	}

	// --------------------------------------------------------------------
    // Comparison
    // --------------------------------------------------------------------

	/**
	 * Determine if date is equal to another  Date instance.
	 */
	public function equalTo(string|DateTimeInterface $date, string $timezone = null): bool
	{
		if (is_string($date)) {
			$date = static::create($date, $this->getTimezone());
		}

		$test = clone $this;

		if (null !== $timezone) {
			$date->setTimezone($timezone);
			$test->setTimezone($timezone);
		}

		return $test->format('Y-m-d H:i:s') === $date->format('Y-m-d H:i:s');
	}

	/**
	 * Determine if date is not equal to another  Date instance.
	 */
	public function notEqualTo(string|DateTimeInterface $date, string $timezone = null): bool
	{
		return ! $this->equalTo($date, $timezone);
	}

	/**
	 * Determine if date is greater than another  Date instance.
	 */
	public function greaterThan(DateTimeInterface|string $date, string $timezone = null): bool
	{
		if (is_string($date)) {
			$date = static::create($date, $this->getTimezone());
		}

		$test = $this->copy();

		if (null !== $timezone) {
			$date->setTimezone($timezone);
			$test->setTimezone($timezone);
		}

		return $test > $date;
	}

	/**
	 * Determine if date is greater than or equal to another  Date instance.
	 */
	public function greaterOrEqualTo(DateTimeInterface|string $date, string $timezone = null): bool
	{
		if (is_string($date)) {
			$date = static::create($date, $this->getTimezone());
		}

		$test = $this->copy();

		if (null !== $timezone) {
			$date->setTimezone($timezone);
			$test->setTimezone($timezone);
		}

		return $test >= $date;
	}

	/**
	 * Determine if date is less than another  Date instance.
	 */
	public function lessThan(DateTimeInterface|string $date, string $timezone = null): bool
	{
		if (is_string($date)) {
			$date = static::create($date, $this->getTimezone());
		}

		$test = clone $this;

		if (null !== $timezone) {
			$date->setTimezone($timezone);
			$test->setTimezone($timezone);
		}

		return $test < $date;
	}

	/**
	 * Determine if date is less than or equal to another  Date instance.
	 */
	public function lessOrEqualTo(DateTimeInterface|string $date, string $timezone = null): bool
	{
		if (is_string($date)) {
			$date = static::create($date, $this->getTimezone());
		}

		$test = clone $this;

		if (null !== $timezone) {
			$date->setTimezone($timezone);
			$test->setTimezone($timezone);
		}

		return $test <= $date;
	}

	/**
     * Ensures that the times are identical, taking timezone into account.
     */
    public function sameAs(DateTimeInterface|string $testTime, string $timezone = null): bool
    {
        if ($testTime instanceof DateTimeInterface) {
            $testTime = $testTime->format('Y-m-d H:i:s');
        } elseif (is_string($testTime)) {
            $timezone = $timezone ?: $this->timezone;
            $timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);
            $testTime = new DateTime($testTime, $timezone);
            $testTime = $testTime->format('Y-m-d H:i:s');
        }

        $ourTime = $this->toDateTimeString();

        return $testTime === $ourTime;
    }

	// --------------------------------------------------------------------
    // Differences
    // --------------------------------------------------------------------

	/**
	 * Get the difference in days.
	 */
	public function diffInDays(string|DateTimeInterface $date = null, string $timezone = null): string
	{
		if (!($date instanceof DateTimeInterface)) {
			$date = new static($date, $this->getTimezone());
		}

		$test = clone $this;

		if (null !== $timezone) {
			$date->setTimezone($timezone);
			$test->setTimezone($timezone);
		}


		return $test->diff($date)->format('%r%a');
	}

	/**
	 * Get the difference in hours.
	 */
	public function diffInHours(string|DateTimeInterface $date = null, string $timezone = null)
	{
		return $this->diffInMinutes($date, $timezone) / 60;
	}

	/**
	 * Get the difference in minutes.
	 */
	public function diffInMinutes(string|DateTimeInterface $date = null, string $timezone = null)
	{
		return $this->diffInSeconds($date, $timezone) / 60;
	}

	/**
	 * Get the difference in months.
	 */
	public function diffInMonths(string|DateTimeInterface $date = null, string $timezone = null)
	{
		if (!($date instanceof DateTimeInterface)) {
			$date = new static($date, $this->getTimezone());
		}

		$test = clone $this;

		if (null !== $timezone) {
			$date->setTimezone($timezone);
			$test->setTimezone($timezone);
		}

		$difference = $test->diff($date);

		[$years, $months] = explode(':', $difference->format('%y:%m'));

		return (($years * 12) + $months) * $difference->format('%r1');
	}

	/**
	 * Get the difference in seconds.
	 */
	public function diffInSeconds(string|DateTimeInterface $date = null, string $timezone = null)
	{
		if (!($date instanceof DateTimeInterface)) {
			$date = new static($date, $this->getTimezone());
		}

		$test = clone $this;

		if (null !== $timezone) {
			$date->setTimezone($timezone);
			$test->setTimezone($timezone);
		}

		$difference = $test->diff($date);

		[$days, $hours, $minutes, $seconds] = explode(':', $difference->format('%a:%h:%i:%s'));

		// Add the total amount of seconds in all the days.
		$seconds += ($days * 24 * 60 * 60);

		// Add the total amount of seconds in all the hours.
		$seconds += ($hours * 60 * 60);

		// Add the total amount of seconds in all the minutes.
		$seconds += ($minutes * 60);

		return $seconds * $difference->format('%r1');
	}

	/**
	 * Get the difference in years.
	 */
	public function diffInYears(string|DateTimeInterface $date = null, string $timezone = null)
	{
		if (!($date instanceof DateTimeInterface)) {
			$date = new static($date, $this->getTimezone());
		}

		$test = clone $this;

		if (null !== $timezone) {
			$date->setTimezone($timezone);
			$test->setTimezone($timezone);
		}

		return $test->diff($date)->format('%r%y');
	}

	/**
	 * Get the number of days between two dates
	 *
	 * @return int|false Returns the number of days or false if invalid dates
	 */
	public static function diffDays(string|DateTimeInterface $date1, string|DateTimeInterface $date2 = null)
    {
		// Get the difference between the two dates
		$interval = self::differenceInterval($date1, $date2);

		if ($interval) {
            return $interval->days;
		}

        // The passed in values were not dates
        return false;
	}

	/**
	 * Get the number of hours between two dates
	 *
	 * @return int|false Returns the number of hours or false if invalid dates
	 */
	public static function diffHours(string|DateTimeInterface $date1, string|DateTimeInterface $date2 = null)
    {
		$interval = self::differenceInterval($date1, $date2);

		if ($interval) {
			// Return the number of hours
			return ($interval->days * 24) + $interval->h;
		}

        return false;
    }

	/**
	 * Get the number of minutes between two dates.
	 *
	 * @return int|false Returns the number of minutes or false if invalid dates
	 */
	public static function diffMinutes(string|DateTimeInterface $date1, string|DateTimeInterface $date2 = null)
    {
		$interval = static::differenceInterval($date1, $date2);

		if ($interval) {
            return ((($interval->days * 24) + $interval->h) * 60) + $interval->i;
		}

        return false;
	}

	/**
	 * Get the number of months between two dates.
	 *
	 * @return int|false Returns the number of months or false if invalid dates
	 */
	public static function diffMonths(string|DateTimeInterface $date1, string|DateTimeInterface $date2 = null)
    {
		$interval = static::differenceInterval($date1, $date2);

		if ($interval) {
			return ($interval->y * 12) + $interval->m;
		}

		return false;
	}

	/**
	 * Get the number of seconds between two dates.
	 *
	 * @return int|false Returns the number of seconds or false if invalid dates
	 */
	public static function diffSeconds(string|DateTimeInterface $date1, string|DateTimeInterface $date2 = null)
    {
		$interval = self::differenceInterval($date1, $date2);

		if ($interval) {
			return ((((($interval->days * 24) + $interval->h) * 60) +
				$interval->i) * 60)  + $interval->s;
		}

        return false;
	}

	/**
	 * Get the number of years between two dates.
	 *
	 * @return int|false Returns the number of years or false if invalid dates
	 */
	public static function differenceYears($date1, $date2 = null)
    {
		$interval = self::differenceInterval($date1, $date2);

		if ($interval) {
			return $interval->y;
		}

        return false;
	}

	/**
	 * Get a relative date string, e.g., 3 days ago.
	 */
	public function relativeTo(string|DateTimeInterface $date = null): string
	{
		if (!($date instanceof DateTimeInterface)) {
			$date = new static($date, $this->getTimezone());
		}

		$units = ['second', 'minute', 'hour', 'day', 'week', 'month', 'year'];
		$values = [60, 60, 24, 7, 4.35, 12];

		// Get the difference between the two timestamps. We'll use this to cacluate the
		// actual time remaining.
		$difference = abs($date->getTimestamp() - $this->getTimestamp());

		for ($i = 0; $i < count($values) AND $difference >= $values[$i]; $i++) {
			$difference = $difference / $values[$i];
		}

		// Round the difference to the nearest whole number.
		$difference = round($difference);

		if ($date->getTimestamp() < $this->getTimestamp()) {
			$suffix = 'from now';
		}
		else {
			$suffix = 'ago';
		}

		// Get the unit of time we are measuring. We'll then check the difference, if it is not equal
		// to exactly 1 then it's a multiple of the given unit so we'll append an 's'.
		$unit = $units[$i];

		if ($difference != 1) {
			$unit .= 's';
		}

		return $difference.' '.$unit.' '.$suffix;
	}

	// --------------------------------------------------------------------
    // Utilities
    // --------------------------------------------------------------------

	/**
	 * Use the end of the day.
	 */
	public function endOfDay(): self
	{
		return $this->setHour(23)->setMinute(59)->setSecond(59);
	}

	/**
	 * Use the end of the month.
	 */
	public function endOfMonth(): self
	{
		return $this->setDay($this->getDaysInMonth())->endOfDay();
	}

	/**
	 * Use the end of the week.
	 */
	public function endOfWeek(): self
	{
		return $this->addDays(6 - $this->getDayOfWeekAsNumeric())->endOfDay();
	}

	/**
	 * Use the start of the day.
	 */
	public function startOfDay(): self
	{
		return $this->setHour(0)->setMinute(0)->setSecond(0);
	}
	/**
	 * Use the start of the month.
	 */
	public function startOfMonth(): self
	{
		return $this->setDay(1)->startOfDay();
	}

	/**
	 * Use the start of the week.
	 */
	public function startOfWeek(): self
	{
		return $this->subDays($this->getDayOfWeekAsNumeric())->startOfDay();
	}




    /**
     * Check a time string to see if it includes a relative date (like 'next Tuesday').
     */
    protected static function hasRelativeKeywords(string $time): bool
    {
        // skip common format with a '-' in it
        if (preg_match('/\d{4}-\d{1,2}-\d{1,2}/', $time) !== 1) {
            return preg_match(static::$relativePattern, $time) > 0;
        }

        return false;
    }

	/**
	 * Modify by an amount of days.
	 */
	protected function modifyDays(int|float $value, bool $invert = false): self
	{
		if ($this->isFloat($value)) {
			return $this->modifyHours($value * 24, $invert);
		}

		$interval = new DateInterval("P{$value}D");

		return $this->modifyFromInterval($interval, $invert);
	}

	/**
	 * Modify by an amount of hours.
	 */
	protected function modifyHours(int|float $value, bool $invert = false): self
	{
		if ($this->isFloat($value)) {
			return $this->modifyMinutes($value * 60, $invert);
		}

		$interval = new DateInterval("PT{$value}H");

		return $this->modifyFromInterval($interval, $invert);
	}

	/**
	 * Modify by an amount of minutes.
	 */
	protected function modifyMinutes(int|float $value, bool $invert = false): self
	{
		if ($this->isFloat($value)) {
			return $this->modifySeconds($value * 60, $invert);
		}

		$interval = new DateInterval("PT{$value}M");

		return $this->modifyFromInterval($interval, $invert);
	}

	/**
	 * Modify by an amount of months.
	 */
	protected function modifyMonths(int|float $value, bool $invert = false): self
	{
		if ($this->isFloat($value)) {
			return $this->modifyWeeks($value * 4, $invert);
		}

		$interval = new DateInterval("P{$value}M");

		return $this->modifyFromInterval($interval, $invert);
	}

	/**
	 * Modify by an amount of seconds.
	 */
	protected function modifySeconds(int|float $value, bool $invert = false): self
	{
		$interval = new DateInterval("PT{$value}S");

		return $this->modifyFromInterval($interval, $invert);
	}

	/**
	 * Modify by an amount of weeks.
	 */
	protected function modifyWeeks(int|float $value, bool $invert = false): self
	{
		if ($this->isFloat($value)) {
			return $this->modifyDays($value * 7, $invert);
		}

		$interval = new DateInterval("P{$value}W");

		return $this->modifyFromInterval($interval, $invert);
	}

	/**
	 * Modify by an amount of Years.
	 */
	protected function modifyYears(int|float $value, bool $invert = false): self
	{
		if ($this->isFloat($value)) {
			return $this->modifyMonths($value * 12, $invert);
		}

		$interval = new DateInterval("P{$value}Y");

		return $this->modifyFromInterval($interval, $invert);
	}

	/**
	 * Modify from a DateInterval object.
	 */
	protected function modifyFromInterval(DateInterval $interval, bool $invert = false): self
	{
		if ($invert) {
			$this->sub($interval);
		}
		else {
			$this->add($interval);
		}

		return $this;
	}

	/**
	 * Get a date attribute.
	 */
	protected function getDateAttribute(string $attribute): mixed
	{
		return match($attribute) {
			'Day'                => $this->format('d'),
			'Month'              => $this->format('m'),
			'Year'               => $this->format('Y'),
			'Hour'               => $this->format('G'),
			'Minute'             => $this->format('i'),
			'Second'             => $this->format('s'),
			'DayOfWeek'          => $this->format('l'),
			'DayOfWeekAsNumeric' => (7 + $this->format('w') - $this->getWeekStartDay()) % 7,
			'DaysInMonth'        => $this->format('t'),
			'DayOfYear'          => $this->format('z'),
			'DaySuffix'          => $this->format('S'),
			'GmtDifference'      => $this->format('O'),
			'SecondsSinceEpoch'  => $this->format('U'),
			'TimezoneName'       => $this->getTimezoneName(),
			default              => throw new InvalidArgumentException('The date attribute ['.$attribute.'] could not be found.')
		};
	}

	/**
	 * Syntactical sugar for determining if date object "is" a condition.
	 */
	protected function isDateAttribute(string $attribute): mixed
	{
		return match($attribute) {
			'LeapYear'        => (bool) $this->format('L'),
			'AmOrPm'          => $this->format('A'),
			'DaylightSavings' => (bool) $this->format('I'),
			default           => new InvalidArgumentException('The date attribute ['.$attribute.'] could not be found.')
		};
	}

	/**
	 * Set a date attribute.
	 */
	protected function setDateAttribute(string $attribute, mixed $value): mixed
	{
		return match($attribute) {
			'Day'    => $this->setDate($this->getYear(), $this->getMonth(), $value),
			'Month'  => $this->setDate($this->getYear(), $value, $this->getDay()),
			'Year'   => $this->setDate($value, $this->getMonth(), $this->getDay()),
			'Hour'   => $this->setTime($value, $this->getMinute(), $this->getSecond()),
			'Minute' => $this->setTime($this->getHour(), $value, $this->getSecond()),
			'Second' => $this->setTime($this->getHour(), $this->getMinute(), $value),
			default  => throw new InvalidArgumentException('The date attribute ['.$attribute.'] could not be set.')
		};
	}

	/**
	 * Determine if a given amount is a floating point number.
	 */
	protected function isFloat(int|float $value): bool
	{
		return is_float($value) AND intval($value) != $value;
	}

	/**
	 * Parse a supplied timezone.
	 */
	protected function parseSuppliedTimezone(string|DateTimeZone|null $timezone): ?DateTimeZone
	{
		if ($timezone instanceof DateTimeZone OR is_null($timezone)) {
			return $timezone;
		}

		try {
			$timezone = new DateTimeZone($timezone);
		}
		catch (Exception $error) {
			throw new InvalidArgumentException('The supplied timezone ['.$timezone.'] is not supported.');
		}

		return $timezone;
	}

	/**
	 * Get the interval of time between two dates
	 *
	 * @return DateInterval|false Returns an interval object
	 */
	private static function differenceInterval(string|DateTimeInterface $date1, string|DateTimeInterface $date2 = null)
    {
		if (null === $date2) {
			$date2 = $date1;
			$date1 = static::now();
		}

		// Make sure our dates are DateTime objects
		$datetime1 = static::convertToDate($date1);
		$datetime2 = static::convertToDate($date2);

		// If both variables were valid dates...
		if ($datetime1 AND $datetime2) {
			// Get the time interval between the two dates
			return $datetime1->diff($datetime2);
        }

		// The dates were invalid... Return false
        return false;
    }
}
