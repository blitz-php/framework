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
use Spec\BlitzPHP\App\Publishers\TestPublisher;

use function Kahlan\expect;

describe('Commandes / Publish', function (): void {
    beforeEach(function (): void {
        COH::setUp();
    });

    afterEach(function (): void {
        COH::tearDown();
		TestPublisher::setResult(true);
    });

    beforeAll(function (): void {
        COH::setUpBeforeClass();
    });

    afterAll(function (): void {
        COH::tearDownAfterClass();
    });

    it('default', function (): void {
        command('publish');

        expect(COH::buffer())->toMatch(
			static fn ($actual) => str_contains($actual, lang('Publisher.publishSuccess', [
				TestPublisher::class,
				0,
				STORAGE_PATH,
			]))
		);
	});

    it('Echoue', function (): void {
        TestPublisher::setResult(false);

        command('publish');

		expect(COH::buffer())->toMatch(
			static fn ($actual) => str_contains($actual, lang('Publisher.publishFailure', [
				TestPublisher::class,
				STORAGE_PATH,
			]))
		);
    });
});
