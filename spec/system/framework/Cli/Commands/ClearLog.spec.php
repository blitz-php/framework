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

describe('Commandes / ClearLog', function (): void {
    beforeAll(function (): void {
        COH::setUpBeforeClass();

		$this->date = date('Y-m-d', strtotime('+1 year'));

		$this->createDummyLogFiles = function(): void {
			$date = $this->date;
			$path = STORAGE_PATH . 'logs' . DS . "log-{$date}.log";

			// creer 10 faux ficher de log
			for ($i = 0; $i < 10; $i++) {
				$newDate = date('Y-m-d', strtotime("+1 year -{$i} day"));
				$path    = str_replace($date, $newDate, $path);
				file_put_contents($path, 'Lorem ipsum');

				$date = $newDate;
			}
		};
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
		expect(file_exists(STORAGE_PATH . 'logs' . DS . "log-{$this->date}.log"))->toBeFalsy();

        $this->createDummyLogFiles();
		expect(file_exists(STORAGE_PATH . 'logs' . DS . "log-{$this->date}.log"))->toBeTruthy();

        command('logs:clear -force');

		expect(file_exists(STORAGE_PATH . 'logs' . DS . "log-{$this->date}.log"))->toBeFalsy();

		expect(COH::buffer())->toMatch(
			static fn ($actual) => str_contains($actual, 'Logs netoyés.')
		);
	});
});
