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

describe('Utilities / Date', static function (): void {
    describe('Helper date', static function (): void {
        helper('date');

        it('Now() par défaut', static function (): void {
            Date::setTestNow('April 09, 2023');

            expect(now(null, false))->toBe(1_680_994_800);

            Date::setTestNow();
        });

        it('Now() specifique', static function (): void {
            Date::setTestNow('April 09, 2023', 'America/Chicago');

            // Chicago should be two hours ahead of Vancouver
            expect(7200)->toBe(now('America/Chicago', false) - now('America/Vancouver', false));

            Date::setTestNow();
        });

        it('Liste deroulante de timezone avec timezone par defaut', static function (): void {
            $timezones = DateTimeZone::listIdentifiers();

            $expected = "<select name='timezone' class='custom-select'>\n";

            foreach ($timezones as $timezone) {
                $selected = ($timezone === 'Africa/Douala') ? 'selected' : '';
                $expected .= "<option value='{$timezone}' {$selected}>{$timezone}</option>\n";
            }

            $expected .= ("</select>\n");

            expect($expected)->toBe(timezone_select('custom-select', 'Africa/Douala'));
        });

        it('Liste deroulante de timezone avec regions geographique specifiee', static function (): void {
            $spesificRegion = DateTimeZone::AFRICA;
            $timezones      = DateTimeZone::listIdentifiers($spesificRegion, null);

            $expected = "<select name='timezone' class='custom-select'>\n";

            foreach ($timezones as $timezone) {
                $selected = ($timezone === 'Africa/Douala') ? 'selected' : '';
                $expected .= "<option value='{$timezone}' {$selected}>{$timezone}</option>\n";
            }

            $expected .= ("</select>\n");

            expect($expected)->toBe(timezone_select('custom-select', 'Africa/Douala', $spesificRegion));
        });

        it('Liste deroulante de timezone par pays specifie', static function (): void {
            $spesificRegion = DateTimeZone::PER_COUNTRY;
            $country        = 'CM';
            $timezones      = DateTimeZone::listIdentifiers($spesificRegion, $country);

            $expected = "<select name='timezone' class='custom-select'>\n";

            foreach ($timezones as $timezone) {
                $selected = ($timezone === 'Africa/Douala') ? 'selected' : '';
                $expected .= "<option value='{$timezone}' {$selected}>{$timezone}</option>\n";
            }

            $expected .= ("</select>\n");

            expect($expected)->toBe(timezone_select('custom-select', 'Africa/Douala', $spesificRegion, $country));
        });
    });

    describe('Date', static function (): void {
        it('Date', static function (): void {
            $formatter = new IntlDateFormatter(
                'en_US',
                IntlDateFormatter::SHORT,
                IntlDateFormatter::SHORT,
                Date::DEFAULT_TIMEZONE,
                IntlDateFormatter::GREGORIAN,
                'yyyy-MM-dd HH:mm:ss'
            );

            $date = new Date();

            expect($formatter->format($date))->toBe($date->toDateTimeString());
        });

        it('Date avec timezone', static function (): void {
            $formatter = new IntlDateFormatter(
                'en_US',
                IntlDateFormatter::SHORT,
                IntlDateFormatter::SHORT,
                'Europe/London',
                IntlDateFormatter::GREGORIAN,
                'yyyy-MM-dd HH:mm:ss'
            );

            $date = new Date('now', 'Europe/London');

            expect($formatter->format($date))->toBe($date->toDateTimeString());
        });

        it('Date avec timezone et langue', static function (): void {
            $formatter = new IntlDateFormatter(
                'fr_FR',
                IntlDateFormatter::SHORT,
                IntlDateFormatter::SHORT,
                'Europe/London',
                IntlDateFormatter::GREGORIAN,
                'yyyy-MM-dd HH:mm:ss'
            );

            $date = new Date('now', 'Europe/London', 'fr_FR');

            expect($formatter->format($date))->toBe($date->toDateTimeString());
        });

        it('Date avec datetimezone', static function (): void {
            $formatter = new IntlDateFormatter(
                'fr_FR',
                IntlDateFormatter::SHORT,
                IntlDateFormatter::SHORT,
                'Europe/London',
                IntlDateFormatter::GREGORIAN,
                'yyyy-MM-dd HH:mm:ss'
            );

            $date = new Date('now', new DateTimeZone('Europe/London'), 'fr_FR');

            expect($formatter->format($date))->toBe($date->toDateTimeString());
        });

        it('toDateTime', static function (): void {
            $date = new Date();

            $obj = $date->toDateTime();

            expect($obj)->toBeAnInstanceOf(DateTime::class);
        });

        it('now', static function (): void {
            $date  = Date::now();
            $date1 = new DateTime();

            expect($date)->toBeAnInstanceOf(Date::class);
            expect($date->getTimestamp())->toBe($date1->getTimestamp());
        });

        it('parse', static function (): void {
            $date  = Date::parse('next Tuesday', 'America/Chicago');
            $date1 = new DateTime('now', new DateTimeZone('America/Chicago'));
            $date1->modify('next Tuesday');

            expect($date->getTimestamp())->toBe($date1->getTimestamp());
        });

        it('ToDateTimeString', static function (): void {
            $date = Date::parse('2017-01-12 00:00');
            expect('2017-01-12 00:00:00')->toBe($date->toDateTimeString());

            $date = Date::parse('2017-01-12 00:00', 'America/Chicago');
            expect('2017-01-12 00:00:00')->toBe($date->toDateTimeString());
        });

        it('ToDateTimeStringWithTimeZone', static function (): void {
            $date   = Date::parse('2017-01-12 00:00', 'Europe/London');
            $expect = new DateTime('2017-01-12', new DateTimeZone('Europe/London'));

            expect($expect->format('Y-m-d H:i:s'))->toBe($date->toDateTimeString());
        });
    });

    describe('Date relatives', static function (): void {
        it('Today', static function (): void {
            $date = Date::today();

            expect(date('Y-m-d 00:00:00'))->toBe($date->toDateTimeString());
        });

        it('TodayLocalized', static function (): void {
            $date = Date::today('Europe/London');

            expect(date('Y-m-d 00:00:00'))->toBe($date->toDateTimeString());
        });

        it('Yesterday', static function (): void {
            $date = Date::yesterday();

            expect(date('Y-m-d 00:00:00', strtotime('-1 day')))->toBe($date->toDateTimeString());
        });

        it('Tomorrow', static function (): void {
            $date = Date::tomorrow();

            expect(date('Y-m-d 00:00:00', strtotime('+1 day')))->toBe($date->toDateTimeString());
        });
    });

    describe('Creations', static function (): void {
        it('CreateFromDate', static function (): void {
            $date = Date::createFromDate(2017, 0o3, 0o5);

            expect(date('Y-m-d 00:00:00', strtotime('2017-03-05 00:00:00')))->toBe($date->toDateTimeString());
        });

        it('CreateFromDateLocalized', static function (): void {
            $date = Date::createFromDate(2017, 0o3, 0o5, 'Europe/London');

            expect(date('Y-m-d 00:00:00', strtotime('2017-03-05 00:00:00')))->toBe($date->toDateTimeString());
        });

        it('createFromTime', static function (): void {
            $date = Date::createFromTime(10, 0o3, 0o5);

            expect(date('Y-m-d 10:03:05'))->toBe($date->toDateTimeString());
        });

        it('createFromTimeLocalized', static function (): void {
            $date = Date::createFromTime(10, 0o3, 0o5, 'Europe/London');

            expect(date('Y-m-d 10:03:05'))->toBe($date->toDateTimeString());
        });

        it('createFromTimeEvening', static function (): void {
            $date = Date::createFromTime(20, 0o3, 0o5);

            expect(date('Y-m-d 20:03:05'))->toBe($date->toDateTimeString());
        });
    });
});
