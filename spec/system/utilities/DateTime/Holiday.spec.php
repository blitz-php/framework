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
use BlitzPHP\Utilities\DateTime\Holiday;

use function Kahlan\expect;

describe('Utilities / DateTime / Holiday', function (): void {
    beforeEach(function (): void {
        // Fixer une date de test pour la reproductibilité
        Date::setTestNow('2025-08-01 14:00:00', 'Africa/Douala', 'fr_FR');
    });

    afterEach(function (): void {
        Date::setTestNow(null);
        Holiday::setHolidays('XX', []); // Réinitialiser les jours fériés personnalisés
    });

    describe('Gestion des jours fériés', function (): void {
		it('isHoliday pour jour férié fixe (Cameroun)', function (): void {
			$date = Date::create('2025-05-20'); // Journée nationale
			expect($date->isHoliday('CM'))->toBeTruthy();
		});

		it('isHoliday pour jour non férié', function (): void {
			$date = Date::create('2025-05-21');
			expect($date->isHoliday('CM'))->toBeFalsy();
		});

		it('isHoliday avec règle Pâques', function (): void {
			$date = Date::create('2025-04-20'); // Pâques 2025
			expect($date->isHoliday('CM'))->toBeTruthy();
		});

		it('isHoliday avec règle nth_weekday', function (): void {
			Holiday::setHolidays('XX', ['custom' => 'nth_weekday:monday:2:july']);
			$date = Date::create('2025-07-14'); // 2e lundi de juillet
			expect($date->isHoliday('XX'))->toBeTruthy();
		});

		it('setHolidays avec règle fixe', function (): void {
			Holiday::setHolidays('XX', ['custom' => '08-01']);
			$date = Date::create('2025-08-01');
			expect($date->isHoliday('XX'))->toBeTruthy();
		});

		it('setHolidays avec règle Pâques', function (): void {
			Holiday::setHolidays('XX', ['custom' => 'easter + 1']);
			$date = Date::create('2025-04-21'); // Lundi de Pâques 2025
			expect($date->isHoliday('XX'))->toBeTruthy();
		});

		it('setHolidays avec règle Pâques - soustraction', function (): void {
			Holiday::setHolidays('XX', ['custom' => 'easter - 2']); // Vendredi Saint
			$date = Date::create('2025-04-18'); // 18 avril 2025
			expect($date->isHoliday('XX'))->toBeTruthy();
		});

		it('setHolidays avec règle invalide', function (): void {
			expect(fn() => Holiday::setHolidays('XX', ['invalid' => 'invalid_rule']))
				->toThrow(new InvalidArgumentException());
		});

		it('Cache des jours fériés', function (): void {
			Holiday::setHolidays('XX', ['custom' => '08-01']);
			$date1 = Date::create('2025-08-01');
			$date2 = Date::create('2025-08-01');
			expect($date1->isHoliday('XX'))->toBeTruthy();
			expect($date2->isHoliday('XX'))->toBeTruthy(); // Utilise le cache
		});

		it('Cache Pâques', function (): void {
			$date1 = Date::create('2025-04-20'); // Pâques
			$date2 = Date::create('2025-04-20');
			expect($date1->isHoliday('FR'))->toBeTruthy();
			expect($date2->isHoliday('FR'))->toBeTruthy(); // Utilise le cache
		});
	});
});
