<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\DateTime\FormatDetector;

use function Kahlan\expect;

describe('Utilities / DateTime / FormatDetector', function (): void {
    describe('Détection des formats', function (): void {
		it('Format ISO 8601 avec microsecondes', function (): void {
			$format = FormatDetector::detect('2025-08-01T14:00:00.123456+01:00');
			expect($format)->toBe('Y-m-d\TH:i:s.uP');
		});

		it('Format ISO 8601 sans microsecondes', function (): void {
			$format = FormatDetector::detect('2025-08-01T14:00:00+01:00');
			expect($format)->toBe('Y-m-d\TH:i:sP');
		});

		it('Format Y-m-d H:i:s.u', function (): void {
			$format = FormatDetector::detect('2025-08-01 14:00:00.123456');
			expect($format)->toBe('Y-m-d H:i:s.u');
		});

		it('Format Y-m-d H:i:s', function (): void {
			$format = FormatDetector::detect('2025-08-01 14:00:00');
			expect($format)->toBe('Y-m-d H:i:s');
		});

		it('Format Y-m-d', function (): void {
			$format = FormatDetector::detect('2025-08-01');
			expect($format)->toBe('Y-m-d');
		});

		it('Format européen d.m.Y H:i:s', function (): void {
			$format = FormatDetector::detect('01.08.2025 14:00:00');
			expect($format)->toBe('d.m.Y H:i:s');
		});

		it('Format ambigu slash : US prioritaire (08/01 = Aug 1)', function (): void {
			$format = FormatDetector::detect('08/01/2025');
			expect($format)->toBe('m/d/Y');
		});

		it('Format ambigu slash : EU fallback (25/12 = 25 Dec)', function (): void {
			$format = FormatDetector::detect('25/12/2025');
			expect($format)->toBe('d/m/Y'); // Skip m/d/Y (m=25 invalide)
		});

		it('Format ambigu slash : EU valide (13/01 = 13 Jan)', function (): void {
			$format = FormatDetector::detect('13/01/2025');
			expect($format)->toBe('d/m/Y'); // Skip m/d/Y (m=13 invalide)
		});

		it('Format Ymd', function (): void {
			$format = FormatDetector::detect('20250801');
			expect($format)->toBe('Ymd');
		});

		it('Format RFC 2822', function (): void {
			$format = FormatDetector::detect('Fri, 01 Aug 2025 14:00:00 +0100');
			expect($format)->toBe('D, d M Y H:i:s O');
		});

		it('Format timestamp Unix', function (): void {
			$format = FormatDetector::detect('1749056400');
			expect($format)->toBe('U');
		});

		it('Format timestamp avec microsecondes', function (): void {
			$format = FormatDetector::detect('1749056400.123456');
			expect($format)->toBe('U.u');
		});

		it('Format non reconnu avec parsing automatique', function (): void {
			$format = FormatDetector::detect('01 Aug 2025');
			expect($format)->toBe('d m Y H:i:s');
		});

		it('Format invalide', function (): void {
			$format = FormatDetector::detect('invalid_date');
			expect($format)->toBeNull();
		});
	});

	describe('Détection des séparateurs', function (): void {
		it('Séparateur tiret', function (): void {
			$format = FormatDetector::detect('2025-08-01');
			expect($format)->toBe('Y-m-d');
		});

		it('Séparateur slash', function (): void {
			$format = FormatDetector::detect('20/08/2025');
			expect($format)->toBe('d/m/Y');

			$format = FormatDetector::detect('08/20/2025');
			expect($format)->toBe('m/d/Y');
		});

		it('Séparateur point', function (): void {
			$format = FormatDetector::detect('01.08.2025');
			expect($format)->toBe('d.m.Y');
		});
	});
});
