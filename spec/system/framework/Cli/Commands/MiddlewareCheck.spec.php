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

describe('Commandes / MiddlewareCheck', function (): void {
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

    it('Trouve les middlewares definies pour une route', function (): void {
        command('middleware:check GET /');

		expect(preg_replace('/\033\[.+?m/u', '', COH::buffer()))->toMatch(
			static fn ($actual) => str_contains($actual, '| GET     | /     | forcehttps pagecache |')
		);
	});

    it('Genere une erreur lorsque la route n\'existe pas', function (): void {
        command('middleware:check PUT product/123');

		expect(str_replace(["\033[0m", "\033[1;31m", "\033[0;30m", "\033[47m"], '', COH::buffer()))->toMatch(
			static fn ($actual) => str_contains($actual, 'Impossible de trouver une route: "PUT product/123"')
		);
	});
});
