<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\DateTime\Date;
use BlitzPHP\Utilities\Exceptions\DateException;

use function Kahlan\expect;

describe('Utilities / DateTime / Date', function (): void {
    beforeEach(function (): void {
        // Fixer une date de test pour la reproductibilité
        Date::setTestNow('2025-08-01 14:00:00', 'Africa/Douala', 'fr_FR');
    });

    afterEach(function (): void {
        Date::setTestNow(null);
    });

	describe('Création et initialisation', function (): void {
		it('Création avec now', function (): void {
			$date = Date::now();
			expect($date)->toBeAnInstanceOf(Date::class);
			expect($date->toDateTimeString())->toBe('2025-08-01 14:00:00');
		});

		it('Création avec timezone spécifique', function (): void {
			$date = new Date('now', 'Europe/London', 'en_US');
			$formatter = new IntlDateFormatter(
				'en_US',
				IntlDateFormatter::SHORT,
				IntlDateFormatter::SHORT,
				'Europe/London',
				IntlDateFormatter::GREGORIAN,
				'yyyy-MM-dd HH:mm:ss'
			);
			expect($formatter->format($date))->toBe($date->toDateTimeString());
		});

		it('Création avec locale invalide', function (): void {
			expect(fn() => new Date('now', 'Africa/Douala', 'invalid_locale'))
				->toThrow(new InvalidArgumentException("La locale 'invalid_locale' n'est pas valide."));
		});

		it('Création avec timezone invalide', function (): void {
			expect(fn() => new Date('now', 'Invalid/Timezone'))
				->toThrow(new InvalidArgumentException("Le fuseau horaire fourni [Invalid/Timezone] n'est pas supporté."));
		});

		it('Création avec date relative', function (): void {
			$date = Date::parse('next Tuesday', 'Africa/Douala');
			$expected = new DateTime('2025-08-05', new DateTimeZone('Africa/Douala'));
			expect($date->getTimestamp())->toBe($expected->getTimestamp());
		});

		it('Création depuis createFromDate', function (): void {
			$date = Date::createFromDate(2025, 3, 5);
			expect($date->toDateTimeString())->toBe('2025-03-05 00:00:00');
		});

		it('Création depuis createFromTime', function (): void {
			$date = Date::createFromTime(10, 3, 5);
			expect($date->toDateTimeString())->toBe('2025-08-01 10:03:05');
		});

		it('Création depuis createFromFormat', function (): void {
			$date = Date::createFromFormat('Y-m-d', '2025-08-01', 'Africa/Douala');
			expect($date->toDateString())->toBe('2025-08-01');
		});

		it('Création depuis createFromInstance', function (): void {
			$dt = new DateTime('2025-08-01', new DateTimeZone('Africa/Douala'));
			$date = Date::createFromInstance($dt);
			expect($date->toDateTimeString())->toBe('2025-08-01 00:00:00');
		});

		it('Création depuis createFromTimestamp', function (): void {
			$timestamp = strtotime('2025-08-01 14:00:00');
			$date = Date::createFromTimestamp($timestamp, 'Africa/Douala');
			expect($date->toDateTimeString())->toBe('2025-08-01 14:00:00');
		});

		it('Création avec testNow', function (): void {
			Date::setTestNow('2025-08-01 14:00:00', 'Africa/Douala', 'fr_FR');
			$date = new Date();
			expect($date->toDateTimeString())->toBe('2025-08-01 14:00:00');
		});

		it('hasTestNow', function (): void {
			expect(Date::hasTestNow())->toBeTruthy();
			Date::setTestNow(null);
			expect(Date::hasTestNow())->toBeFalsy();
		});
	});

	describe('Formatage', function (): void {
		it('toDateTimeString', function (): void {
			$date = Date::parse('2025-08-01 14:00:00', 'Africa/Douala');
			expect($date->toDateTimeString())->toBe('2025-08-01 14:00:00');
		});

		it('toDateString', function (): void {
			$date = Date::parse('2025-08-01', 'Africa/Douala');
			expect($date->toDateString())->toBe('2025-08-01');
		});

		it('toFormattedDateString', function (): void {
			$date = Date::parse('2025-08-01', 'Africa/Douala', 'en_US');
			expect($date->toFormattedDateString())->toBe('Aug 1, 2025');
		});

		it('toTimeString', function (): void {
			$date = Date::parse('2025-08-01 14:30:45', 'Africa/Douala');
			expect($date->toTimeString())->toBe('14:30:45');
		});

		it('toLocalizedString', function (): void {
			$date = Date::parse('2025-08-01', 'Africa/Douala', 'fr_FR');
			expect($date->toLocalizedString('d MMMM yyyy'))->toBe('1 août 2025');
		});

		it('toString utilise defaultDateFormat', function (): void {
			$date = Date::parse('2025-08-01 14:00:00', 'Africa/Douala');
			expect((string) $date)->toBe('2025-08-01 14:00:00');
		});

		it('setDefaultDateFormat', function (): void {
			$date = Date::parse('2025-08-01 14:00:00', 'Africa/Douala');
			$date->setDefaultDateFormat('d/m/Y');
			expect((string) $date)->toBe('01/08/2025');
		});
	});

	describe('Dates relatives', function (): void {
		it('today', function (): void {
			$date = Date::today();
			expect($date->toDateTimeString())->toBe('2025-08-01 14:00:00');
		});

		it('yesterday', function (): void {
			$date = Date::yesterday();
			expect($date->toDateTimeString())->toBe('2025-07-31 14:00:00');
		});

		it('tomorrow', function (): void {
			$date = Date::tomorrow();
			expect($date->toDateTimeString())->toBe('2025-08-02 14:00:00');
		});

		it('isToday', function (): void {
			$date = Date::today();
			expect($date->isToday())->toBeTruthy();
			expect(Date::tomorrow()->isToday())->toBeFalsy();
		});

		it('isTomorrow', function (): void {
			$date = Date::tomorrow();
			expect($date->isTomorrow())->toBeTruthy();
			expect(Date::today()->isTomorrow())->toBeFalsy();
		});

		it('isYesterday', function (): void {
			$date = Date::yesterday();
			expect($date->isYesterday())->toBeTruthy();
			expect(Date::today()->isYesterday())->toBeFalsy();
		});
	});

	describe('Comparaisons', function (): void {
		it('equalTo', function (): void {
			$date1 = Date::parse('2025-08-01 14:00:00');
			$date2 = Date::parse('2025-08-01 14:00:00');
			expect($date1->equalTo($date2))->toBeTruthy();
			expect($date1->equalTo('2025-08-01 14:00:00'))->toBeTruthy();
			expect($date1->equalTo('2025-08-01 15:00:00'))->toBeFalsy();
		});

		it('notEqualTo', function (): void {
			$date1 = Date::parse('2025-08-01');
			$date2 = Date::parse('2025-08-02');
			expect($date1->notEqualTo($date2))->toBeTruthy();
			expect($date1->notEqualTo($date1))->toBeFalsy();
		});

		it('greaterThan', function (): void {
			$date1 = Date::parse('2025-08-02');
			$date2 = Date::parse('2025-08-01');
			expect($date1->greaterThan($date2))->toBeTruthy();
			expect($date1->greaterThan($date1))->toBeFalsy();
		});

		it('lessThan', function (): void {
			$date1 = Date::parse('2025-08-01');
			$date2 = Date::parse('2025-08-02');
			expect($date1->lessThan($date2))->toBeTruthy();
			expect($date1->lessThan($date1))->toBeFalsy();
		});

		it('greaterOrEqualTo', function (): void {
			$date1 = Date::parse('2025-08-01');
			$date2 = Date::parse('2025-08-01');
			expect($date1->greaterOrEqualTo($date2))->toBeTruthy();
			expect(Date::parse('2025-08-02')->greaterOrEqualTo($date2))->toBeTruthy();
			expect(Date::parse('2025-07-31')->greaterOrEqualTo($date2))->toBeFalsy();
		});

		it('lessOrEqualTo', function (): void {
			$date1 = Date::parse('2025-08-01');
			$date2 = Date::parse('2025-08-01');
			expect($date1->lessOrEqualTo($date2))->toBeTruthy();
			expect($date1->lessOrEqualTo(Date::parse('2025-08-02')))->toBeTruthy();
			expect(Date::parse('2025-08-02')->lessOrEqualTo($date2))->toBeFalsy();
		});

		it('sameAs avec timezone', function (): void {
			$date1 = Date::parse('2025-08-01 14:00:00', 'Africa/Douala');
			$date2 = Date::parse('2025-08-01 13:00:00', 'UTC');

			expect($date1->sameAs($date2, 'Africa/Douala'))->toBeTruthy();
			expect($date1->sameAs('2025-08-01 14:00:00', 'Africa/Douala'))->toBeTruthy();
		});
	});

	describe('Différences', function (): void {
		it('diffInDays', function (): void {
			$date1 = Date::parse('2025-08-01');
			$date2 = Date::parse('2025-08-05');
			expect($date1->diffInDays($date2))->toBe(4);
			expect($date2->diffInDays($date1))->toBe(-4);
		});

		it('diffInHours', function (): void {
			$date1 = Date::parse('2025-08-01 14:00:00');
			$date2 = Date::parse('2025-08-01 16:00:00');
			expect($date1->diffInHours($date2))->toBe(2);
		});

		it('diffInMinutes', function (): void {
			$date1 = Date::parse('2025-08-01 14:00:00');
			$date2 = Date::parse('2025-08-01 14:30:00');
			expect($date1->diffInMinutes($date2))->toBe(30);
		});

		it('diffInSeconds', function (): void {
			$date1 = Date::parse('2025-08-01 14:00:00');
			$date2 = Date::parse('2025-08-01 14:00:45');
			expect($date1->diffInSeconds($date2))->toBe(45);
		});

		it('diffInMonths', function (): void {
			$date1 = Date::parse('2025-08-01');
			$date2 = Date::parse('2025-10-01');
			expect($date1->diffInMonths($date2))->toBe(2);
		});

		it('diffInYears', function (): void {
			$date1 = Date::parse('2025-08-01');
			$date2 = Date::parse('2027-08-01');
			expect($date1->diffInYears($date2))->toBe(2);
		});

		it('differenceYears statique', function (): void {
			$date1 = Date::parse('2025-08-01');
			$date2 = Date::parse('2027-08-01');
			expect(Date::diffYears($date1, $date2))->toBe(2);
			expect(fn() => Date::diffYears('invalid', $date2))->toThrow(new InvalidArgumentException());
		});

		it('diffDays statique', function (): void {
			$date1 = Date::parse('2025-08-01');
			$date2 = Date::parse('2025-08-05');
			expect(Date::diffDays($date1, $date2))->toBe(4);
		});
	});

	describe('Jours ouvrés', function (): void {
		it('addBusinessDays petite période', function (): void {
			$date = Date::create('2025-08-01', 'Africa/Douala', 'fr_FR');
			$newDate = $date->addBusinessDays(5, 'CM');
			// 1er août 2025 (vendredi) + 5 jours ouvrés = 8 août
			expect($newDate->toDateString())->toBe('2025-08-08');
		});

		it('addBusinessDays soustraction', function (): void {
			$date = Date::create('2025-08-01', 'Africa/Douala', 'fr_FR');
			$newDate = $date->addBusinessDays(-3, 'CM');
			// 1er août 2025 (vendredi) - 3 jours ouvrés = 29 juillet
			expect($newDate->toDateString())->toBe('2025-07-29');
		});

		it('addBusinessDays avec jour férié (Assomption)', function (): void {
			$date = Date::create('2025-08-14', 'Africa/Douala', 'fr_FR');
			$newDate = $date->addBusinessDays(1, 'CM');
			// 14 août (jeudi), 15 août férié, 16-17 août week-end => 18 août
			expect($newDate->toDateString())->toBe('2025-08-18');
		});

		it('addBusinessDays grande période', function (): void {
			$date = Date::create('2025-08-01', 'Africa/Douala', 'fr_FR');
			$newDate = $date->addBusinessDays(50, 'CM');
			// Estimation : 50 jours ouvrés + week-ends + Assomption => environ 10 octobre
			expect($newDate->toDateString())->toMatch('/2025-10-(09|10|11)/');
		});

		it('diffInBusinessDays inclusif (défaut)', function (): void {
			$start = Date::create('2025-08-01', 'Africa/Douala', 'fr_FR'); // Vendredi
			$end = Date::create('2025-08-08', 'Africa/Douala', 'fr_FR');   // Vendredi
			// 1er au 8 août inclusif = 6 jours ouvrés (2-3 août week-end)
			expect($start->diffInBusinessDays($end, 'CM'))->toBe(6);
		});

		it('diffInBusinessDays exclusif (end non inclus)', function (): void {
			$start = Date::create('2025-08-01', 'Africa/Douala', 'fr_FR'); // Vendredi
			$end = Date::create('2025-08-08', 'Africa/Douala', 'fr_FR');   // Vendredi
			// 1er au 8 août exclusif = 5 jours ouvrés (end non inclus)
			expect($start->diffInBusinessDays($end, 'CM', false))->toBe(5);
		});

		it('diffInBusinessDays avec start == end (inclusif)', function (): void {
			$start = Date::create('2025-08-01', 'Africa/Douala', 'fr_FR');
			$end = $start->copy();
			// Start == end inclusif = 1 jour ouvré (si weekday et non férié)
			expect($start->diffInBusinessDays($end, 'CM'))->toBe(1);
		});

		it('diffInBusinessDays avec start == end (exclusif)', function (): void {
			$start = Date::create('2025-08-01', 'Africa/Douala', 'fr_FR');
			$end = $start->copy();
			// Start == end exclusif = 0 jours
			expect($start->diffInBusinessDays($end, 'CM', false))->toBe(0);
		});

		it('diffInBusinessDays avec start > end', function (): void {
			$start = Date::create('2025-08-08', 'Africa/Douala', 'fr_FR');
			$end = Date::create('2025-08-01', 'Africa/Douala', 'fr_FR');
			// Start > end = 0 jours (inclusif ou exclusif)
			expect($start->diffInBusinessDays($end, 'CM'))->toBe(0);
			expect($start->diffInBusinessDays($end, 'CM', false))->toBe(0);
		});

		it('diffInBusinessDays avec jour férié (inclusif)', function (): void {
			$start = Date::create('2025-08-14', 'Africa/Douala', 'fr_FR'); // Jeudi
			$end = Date::create('2025-08-18', 'Africa/Douala', 'fr_FR');   // Lundi
			// 14 au 18 août inclusif = 2 jours ouvrés (15 août férié, 16-17 week-end)
			expect($start->diffInBusinessDays($end, 'CM'))->toBe(2);
		});

		it('diffInBusinessDays avec jour férié (exclusif)', function (): void {
			$start = Date::create('2025-08-14', 'Africa/Douala', 'fr_FR'); // Jeudi
			$end = Date::create('2025-08-18', 'Africa/Douala', 'fr_FR');   // Lundi
			// 14 au 18 août exclusif = 1 jour ouvré (end non inclus)
			expect($start->diffInBusinessDays($end, 'CM', false))->toBe(1);
		});

		it('isHoliday', function (): void {
			$date = Date::create('2025-05-20', 'Africa/Douala', 'fr_FR'); // Journée nationale
			expect($date->isHoliday('CM'))->toBeTruthy();
			expect(Date::create('2025-05-21')->isHoliday('CM'))->toBeFalsy();
		});
	});

	describe('Support multilingue', function (): void {
		it('diffForHumans en français', function (): void {
			$date = Date::create('2025-08-01 14:00:00', 'Africa/Douala', 'fr');
			$other = Date::create('2025-08-03 14:00:00', 'Africa/Douala', 'fr');
			expect($date->diffForHumans($other))->toBe('dans 2 jours');
		});

		it('diffForHumans en anglais', function (): void {
			$date = Date::create('2025-08-01 14:00:00', 'America/New_York', 'en');
			$other = Date::create('2025-08-01 16:00:00', 'America/New_York', 'en');
			expect($date->diffForHumans($other))->toBe('in 2 hours');
		});

		it('diffForHumans avec traductions personnalisées', function (): void {
			Date::setTranslations('es_ES', [
				'year' => ['año', 'años'],
				'month' => ['mes', 'meses'],
				'day' => ['día', 'días'],
				'hour' => ['hora', 'horas'],
				'minute' => ['minuto', 'minutos'],
				'second' => ['segundo', 'segundos'],
				'prefix_future' => 'en ',
				'prefix_past' => 'hace ',
			]);
			$date = Date::create('2025-08-01', 'Africa/Douala', 'es_ES');
			$other = Date::create('2025-07-30', 'Africa/Douala', 'es_ES');
			expect($date->diffForHumans($other))->toBe('hace 2 días');
		});

		it('diffForHumans avec locale non définie', function (): void {
			$date = Date::create('2025-08-01', 'Africa/Douala', 'fr_FR');
			$other = Date::create('2025-08-03', 'Africa/Douala', 'fr_FR');
			expect(fn() => $date->diffForHumans($other, false, 'de_DE'))
				->toThrow(new InvalidArgumentException("Aucune traduction définie pour la locale 'de_DE'. Utilisez setTranslations pour la définir."));
		});
	});

	describe('Setters', function (): void {
		it('setDay valide', function (): void {
			$date = Date::create('2025-08-01')->setDay(15);
			expect($date->toDateString())->toBe('2025-08-15');
		});

		it('setDay invalide', function (): void {
			expect(fn() => Date::create('2025-08-01')->setDay(32))
				->toThrow(new DateException());
		});

		it('setMonth valide', function (): void {
			$date = Date::create('2025-08-01')->setMonth(10);
			expect($date->toDateString())->toBe('2025-10-01');
		});

		it('setMonth invalide', function (): void {
			expect(fn() => Date::create('2025-08-01')->setMonth(13))
				->toThrow(new DateException());
		});

		it('setHour valide', function (): void {
			$date = Date::create('2025-08-01 14:00:00')->setHour(16);
			expect($date->toDateTimeString())->toBe('2025-08-01 16:00:00');
		});

		it('setHour invalide', function (): void {
			expect(fn() => Date::create('2025-08-01')->setHour(24))
				->toThrow(new DateException());
		});

		it('setMinute valide', function (): void {
			$date = Date::create('2025-08-01 14:00:00')->setMinute(45);
			expect($date->toDateTimeString())->toBe('2025-08-01 14:45:00');
		});

		it('setSecond valide', function (): void {
			$date = Date::create('2025-08-01 14:00:00')->setSecond(30);
			expect($date->toDateTimeString())->toBe('2025-08-01 14:00:30');
		});

		it('setYear valide', function (): void {
			$date = Date::create('2025-08-01')->setYear(2026);
			expect($date->toDateString())->toBe('2026-08-01');
		});

		it('setLocale valide', function (): void {
			$date = Date::create('2025-08-01', 'Africa/Douala', 'fr_FR');
			$date->setLocale('en_US');
			expect($date->getLocale())->toBe('en_US');
		});

		it('setLocale invalide', function (): void {
			expect(fn() => Date::create('2025-08-01')->setLocale('invalid_locale'))
				->toThrow(new InvalidArgumentException("La locale 'invalid_locale' n'est pas valide."));
		});

		it('setTimezone', function (): void {
			$date = Date::create('2025-08-01 14:00:00', 'Africa/Douala')->setTimezone('Europe/London');
			expect($date->timezoneName())->toBe('Europe/London');
		});

		it('setTimestamp', function (): void {
			$timestamp = strtotime('2025-08-01 14:00:00');
			$date = Date::create()->setTimestamp($timestamp);
			expect($date->toDateTimeString())->toBe('2025-08-01 14:00:00');
		});

		it('setTimestampFromString', function (): void {
			$date = Date::create()->setTimestampFromString('2025-08-01 14:00:00');
			expect($date->toDateTimeString())->toBe('2025-08-01 14:00:00');
		});

		it('setWeekStartDay numérique', function (): void {
			$date = Date::create()->setWeekStartDay(1); // Lundi
			expect($date->getWeekStartDay())->toBe(1);
		});

		it('setWeekStartDay string', function (): void {
			$date = Date::create()->setWeekStartDay('monday');
			expect($date->getWeekStartDay())->toBe(1);
		});
	});

	describe('Getters', function (): void {
		it('getAge', function (): void {
			$date = Date::create('2000-08-01');
			expect($date->age())->toBe(25);
		});

		it('getDayOfWeek', function (): void {
			$date = Date::create('2025-08-01', 'Africa/Douala', 'en_US'); // Vendredi
			expect($date->getDayOfWeek())->toBe(5);
		});

		it('getDayOfWeekAsString', function (): void {
			$date = Date::create('2025-08-01'); // Vendredi
			expect($date->getDayOfWeekAsString())->toBe('Friday');
		});

		it('getDaysInMonth', function (): void {
			$date = Date::create('2025-08-01');
			expect($date->getDaysInMonth())->toBe(31);
		});

		it('getDaySuffix', function (): void {
			$date = Date::create('2025-08-01');
			expect($date->getDaySuffix())->toBe('st');
		});

		it('getGmtDifference', function (): void {
			$date = Date::create('2025-08-01', 'Africa/Douala');
			expect($date->getGmtDifference())->toBe('+0100');
		});

		it('getSecondsSinceEpoch', function (): void {
			$date = Date::create('2025-08-01 14:00:00');
			expect($date->getSecondsSinceEpoch())->toBe(strtotime('2025-08-01 14:00:00'));
		});

		it('isWeekday', function (): void {
			$date = Date::create('2025-08-01'); // Vendredi
			expect($date->isWeekday())->toBeTruthy();
			expect(Date::create('2025-08-02')->isWeekday())->toBeFalsy(); // Samedi
		});

		it('isWeekend', function (): void {
			$date = Date::create('2025-08-02'); // Samedi
			expect($date->isWeekend())->toBeTruthy();
			expect(Date::create('2025-08-01')->isWeekend())->toBeFalsy();
		});

		it('isDst', function (): void {
			$date = Date::create('2025-08-01', 'Europe/London');
			expect($date->isDst())->toBeTruthy(); // Heure d'été en août
		});

		it('isUtc', function (): void {
			$date = Date::create('2025-08-01', 'UTC');
			expect($date->isUtc())->toBeTruthy();
			expect(Date::create('2025-08-01', 'Africa/Douala')->isUtc())->toBeFalsy();
		});

		it('isLocal', function (): void {
			$date = Date::create('2025-08-01', date_default_timezone_get());
			expect($date->isLocal())->toBeTruthy();
			expect(Date::create('2025-08-01', 'Europe/London')->isLocal())->toBeFalsy();
		});

		it('isLeapYear', function (): void {
			$date = Date::create('2024-08-01');
			expect($date->isLeapYear())->toBeTruthy();
			expect(Date::create('2025-08-01')->isLeapYear())->toBeFalsy();
		});

		it('isAmOrPm', function (): void {
			expect(Date::create('2025-08-01 14:00:00')->isPm())->toBeTruthy();
			expect(Date::create('2025-08-01 02:00:00')->isAm())->toBeTruthy();
		});

		it('getCalendar', function (): void {
			$date = Date::create('2025-08-01');
			expect($date->getCalendar())->toBeAnInstanceOf(\IntlCalendar::class);
		});
	});

	describe('Ajout/Soustraction', function (): void {
		it('addDays', function (): void {
			$date = Date::create('2025-08-01')->addDays(5);
			expect($date->toDateString())->toBe('2025-08-06');
		});

		it('subDays', function (): void {
			$date = Date::create('2025-08-01')->subDays(5);
			expect($date->toDateString())->toBe('2025-07-27');
		});

		it('addHours', function (): void {
			$date = Date::create('2025-08-01 14:00:00')->addHours(2);
			expect($date->toDateTimeString())->toBe('2025-08-01 16:00:00');
		});

		it('addMinutes avec float', function (): void {
			$date = Date::create('2025-08-01 14:00:00')->addMinutes(1.5);
			expect($date->toDateTimeString())->toBe('2025-08-01 14:01:30');
		});

		it('addMonths', function (): void {
			$date = Date::create('2025-08-01')->addMonths(2);
			expect($date->toDateString())->toBe('2025-10-01');
		});

		it('addOneDay', function (): void {
			$date = Date::create('2025-08-01')->addOneDay();
			expect($date->toDateString())->toBe('2025-08-02');
		});
	});

	describe('Utilitaires', function (): void {
		it('startOfDay', function (): void {
			$date = Date::create('2025-08-01 14:30:45')->startOfDay();
			expect($date->toDateTimeString())->toBe('2025-08-01 00:00:00');
		});

		it('endOfDay', function (): void {
			$date = Date::create('2025-08-01 14:30:45')->endOfDay();
			expect($date->toDateTimeString())->toBe('2025-08-01 23:59:59');
		});

		it('startOfMonth', function (): void {
			$date = Date::create('2025-08-15')->startOfMonth();
			expect($date->toDateString())->toBe('2025-08-01');
		});

		it('endOfMonth', function (): void {
			$date = Date::create('2025-08-15')->endOfMonth();
			expect($date->toDateTimeString())->toBe('2025-08-31 23:59:59');
		});

		it('startOfWeek', function (): void {
			$date = Date::create('2025-08-06')->startOfWeek(); // Mercredi
			expect($date->toDateString())->toBe('2025-08-03'); // Dimanche
		});

		it('endOfWeek', function (): void {
			$date = Date::create('2025-08-06')->endOfWeek();
			expect($date->toDateTimeString())->toBe('2025-08-09 23:59:59');
		});

		it('copy', function (): void {
			$date = Date::create('2025-08-01');
			$copy = $date->copy();
			expect($copy->toDateTimeString())->toBe($date->toDateTimeString());
			expect($copy)->not->toBe($date);
		});

		it('toDateTime', function (): void {
			$date = Date::create('2025-08-01');
			$dt = $date->toDateTime();
			expect($dt)->toBeAnInstanceOf(DateTime::class);
			expect($dt->format('Y-m-d H:i:s'))->toBe('2025-08-01 00:00:00');
		});

		it('convertToDate avec chaîne valide (Y-m-d)', function (): void {
			$date = Date::convertToDate('2025-08-01');
			expect($date)->toBeAnInstanceOf(Date::class);
			expect($date->format('Y-m-d'))->toBe('2025-08-01');
		});

		it('convertToDate avec timezone spécifique', function (): void {
			$date = Date::convertToDate('2025-08-01', 'Africa/Douala');
			expect($date->format('Y-m-d'))->toBe('2025-08-01');
			expect($date->getTimezone()->getName())->toBe('Africa/Douala');
		});

		it('convertToDate avec date relative', function (): void {
			$date = Date::convertToDate('next Tuesday');
			// next Tuesday après 2025-08-01 (vendredi) = 2025-08-05
			expect($date->format('Y-m-d'))->toBe('2025-08-05');
		});

		it('convertToDate avec objet DateTimeInterface', function (): void {
			$dt = new DateTime('2025-08-01');
			$date = Date::convertToDate($dt, 'UTC');
			expect($date)->toBeAnInstanceOf(Date::class);
			expect($date->format('Y-m-d'))->toBe('2025-08-01');
			expect($date->getTimezone()->getName())->toBe('UTC');
		});

		it('convertToDate lève exception pour invalide', function (): void {
			expect(fn() => Date::convertToDate('invalid'))->toThrow(new InvalidArgumentException());
		});
	});

	describe('Méthodes magiques', function (): void {
		it('__get', function (): void {
			$date = Date::create('2025-08-01');
			expect($date->day)->toBe('01');
			expect($date->invalid)->toBeNull();
		});

		it('__isset', function (): void {
			$date = Date::create('2025-08-01');
			expect(isset($date->day))->toBeTruthy();
			expect(isset($date->invalid))->toBeFalsy();
		});

		it('__call pour equals', function (): void {
			$date1 = Date::create('2025-08-01');
			$date2 = Date::create('2025-08-01');
			expect($date1->equals($date2))->toBeTruthy();
		});

		it('__call pour setDay', function (): void {
			$date = Date::create('2025-08-01');
			$date = $date->setDay(15);
			expect($date->toDateString())->toBe('2025-08-15');
		});

		it('__call méthode inconnue', function (): void {
			expect(fn() => Date::create('2025-08-01')->unknownMethod())
				->toThrow(new BadMethodCallException());
		});
	});

	describe('Anciens test', static function (): void {
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
});
