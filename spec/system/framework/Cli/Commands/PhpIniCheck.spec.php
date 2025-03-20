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

describe('Commandes / PhpIniCheck', function (): void {
    beforeEach(function (): void {
        COH::setUp();
    });

    afterEach(function (): void {
        COH::tearDown();
    });

    beforeAll(function (): void {
        COH::setUpBeforeClass();
    });

    afterAll(function (): void {
        COH::tearDownAfterClass();
    });

    it('phpini:check', function (): void {
        command('phpini:check');

        $result = COH::buffer();

        expect($result)->toMatch(static fn ($actual) => str_contains($actual, 'Directive'));
        expect($result)->toMatch(static fn ($actual) => str_contains($actual, 'Globale'));
        expect($result)->toMatch(static fn ($actual) => str_contains($actual, 'Actuelle'));
        expect($result)->toMatch(static fn ($actual) => str_contains($actual, 'Recommandation'));
        expect($result)->toMatch(static fn ($actual) => str_contains($actual, 'Remarque'));
	});

    it('phpini:check opcache', function (): void {
        command('phpini:check opcache');

        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'opcache.save_comments'));
	});

    it('phpini:check avec un argument non valide', function (): void {
        command('phpini:check unknown');

        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'Vous devez indiquer un argument correct.'));
	});
});
