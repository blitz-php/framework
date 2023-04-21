<?php

use BlitzPHP\Utilities\Date;

describe("Utilities / Date", function() {
    
    describe("Helper date", function() {
        helper('date');

        it("Now() par défaut", function() {
            Date::setTestNow('April 09, 2023');

            expect(now(null, false))->toBe(1_680_998_400);

            Date::setTestNow();
        });

        it("Now() specifique", function() {
            Date::setTestNow('April 09, 2023', 'America/Chicago');

            // Chicago should be two hours ahead of Vancouver
            expect(7200)->toBe(now('America/Chicago', false) - now('America/Vancouver', false));

            Date::setTestNow();
        });

        it("Liste deroulante de timezone avec timezone par defaut", function() {
            $timezones = DateTimeZone::listIdentifiers();

            $expected = "<select name='timezone' class='custom-select'>\n";
    
            foreach ($timezones as $timezone) {
                $selected = ($timezone === 'Africa/Douala') ? 'selected' : '';
                $expected .= "<option value='{$timezone}' {$selected}>{$timezone}</option>\n";
            }
    
            $expected .= ("</select>\n");
    
            expect($expected)->toBe(timezone_select('custom-select', 'Africa/Douala'));
        });
        
        it("Liste deroulante de timezone avec regions geographique specifiee", function() {
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

        it("Liste deroulante de timezone par pays specifie", function() {
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

    describe("Date", function() {
        
        it("Date", function() {
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

        it("Date avec timezone", function() {
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
        
        it("Date avec timezone et langue", function() {
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

        it("Date avec datetimezone", function() {
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

        it("toDateTime", function() {
            $date = new Date();

            $obj = $date->toDateTime();

            expect($obj)->toBeAnInstanceOf(DateTime::class);
        });
        
        it("now", function() {
            $date  = Date::now();
            $date1 = new DateTime();

            expect($date)->toBeAnInstanceOf(Date::class);
            expect($date->getTimestamp())->toBe($date1->getTimestamp());
        });

        it("parse", function() {
            $date  = Date::parse('next Tuesday', 'America/Chicago');
            $date1 = new DateTime('now', new DateTimeZone('America/Chicago'));
            $date1->modify('next Tuesday');

            expect($date->getTimestamp())->toBe($date1->getTimestamp());
        });

        it("ToDateTimeString", function() {
            $date  = Date::parse('2017-01-12 00:00');
            expect('2017-01-12 00:00:00')->toBe($date->toDateTimeString());

            $date  = Date::parse('2017-01-12 00:00', 'America/Chicago');
            expect('2017-01-12 00:00:00')->toBe($date->toDateTimeString());
        });

        it("ToDateTimeStringWithTimeZone", function() {
            $date  = Date::parse('2017-01-12 00:00', 'Europe/London');
            $expect = new DateTime('2017-01-12', new DateTimeZone('Europe/London'));
            
            expect($expect->format('Y-m-d H:i:s'))->toBe($date->toDateTimeString());
        });
    });

    describe("Date relatives", function() {

        it("Today", function() {
            $date  = Date::today();
            
            expect(date('Y-m-d 00:00:00'))->toBe($date->toDateTimeString());
        });

        it("TodayLocalized", function() {
            $date  = Date::today('Europe/London');
            
            expect(date('Y-m-d 00:00:00'))->toBe($date->toDateTimeString());
        });

        it("Yesterday", function() {
            $date  = Date::yesterday();
            
            expect(date('Y-m-d 00:00:00', strtotime('-1 day')))->toBe($date->toDateTimeString());
        });
        
        it("Tomorrow", function() {
            $date  = Date::tomorrow();
            
            expect(date('Y-m-d 00:00:00', strtotime('+1 day')))->toBe($date->toDateTimeString());
        });
    });

    describe("Creations", function() {
    
        it("CreateFromDate", function() {
            $date  = Date::createFromDate(2017, 03, 05);
            
            expect(date('Y-m-d 00:00:00', strtotime('2017-03-05 00:00:00')))->toBe($date->toDateTimeString());
        });

        it("CreateFromDateLocalized", function() {
            $date  = Date::createFromDate(2017, 03, 05, 'Europe/London');
            
            expect(date('Y-m-d 00:00:00', strtotime('2017-03-05 00:00:00')))->toBe($date->toDateTimeString());
        });
    
        it("CreateFromDateLocalized", function() {
            $date  = Date::createFromTime(10, 03, 05, 'America/Chicago');
            
            expect(date('Y-m-d 10:03:05'))->toBe($date->toDateTimeString());
        });

    });
});
