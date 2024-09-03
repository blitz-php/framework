<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Spec\CliOutputHelper as COH;

use function Kahlan\expect;

describe('Commandes / ClearCache', function (): void {
    beforeAll(function (): void {
        COH::setUpBeforeClass();
	});

    afterAll(function (): void {
        COH::tearDownAfterClass();
	});

    beforeEach(function (): void {
        COH::setUp();
    });

    afterEach(function (): void {
        COH::tearDown();
    });

    it('Fonctionne normalement', function (): void {
		cache()->write('foo', 'bar');

		expect(cache('foo'))->toBe('bar');

        command('cache:clear');

		expect(cache('foo'))->toBeNull();

		expect(COH::buffer())->toMatch(
			static fn ($actual) => str_contains($actual, 'Cache vidé.')
		);
	});

    it('Gestionnaire de cache invalide', function (): void {
		command('cache:clear junk');

		expect(COH::buffer())->toMatch(
			static fn ($actual) => str_contains($actual, 'junk n\'est pas un gestionnaire de cache valide.')
		);
	});
});
