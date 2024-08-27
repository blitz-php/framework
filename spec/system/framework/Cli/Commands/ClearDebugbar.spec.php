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

describe('Commandes / ClearDebugbar', function (): void {
    beforeAll(function (): void {
        COH::setUpBeforeClass();

		$this->time = time();

		$this->createDummyDebugbarJson = function(): void {
			$time = $this->time;
			$path = FRAMEWORK_STORAGE_PATH . 'debugbar' . DS . "debugbar_{$time}.json";

			// creer 10 faux ficher json de debugbar
			for ($i = 0; $i < 10; $i++) {
				$path = str_replace((string) $time, (string) ($time - $i), $path);
				file_put_contents($path, "{}\n");

				$time -= $i;
			}
		};

		if (!is_dir(FRAMEWORK_STORAGE_PATH . 'debugbar')) {
			mkdir(FRAMEWORK_STORAGE_PATH . 'debugbar', 0777);
		}
	});

    afterAll(function (): void {
        COH::tearDownAfterClass();

		if (is_dir(FRAMEWORK_STORAGE_PATH . 'debugbar')) {
			@rmdir(FRAMEWORK_STORAGE_PATH . 'debugbar');
		}
	});

    beforeEach(function (): void {
        COH::setUp();
    });

    afterEach(function (): void {
        COH::tearDown();
    });

    it('Fonctionne normalement', function (): void {
		expect(file_exists(FRAMEWORK_STORAGE_PATH . 'debugbar' . DS . "debugbar_{$this->time}.json"))->toBeFalsy();

        $this->createDummyDebugbarJson();
		expect(file_exists(FRAMEWORK_STORAGE_PATH . 'debugbar' . DS . "debugbar_{$this->time}.json"))->toBeTruthy();

        command('debugbar:clear');

		expect(file_exists(FRAMEWORK_STORAGE_PATH . 'debugbar' . DS . "debugbar_{$this->time}.json"))->toBeFalsy();

		expect(COH::buffer())->toMatch(
			static fn ($actual) => str_contains($actual, 'Debugbar netoyée.')
		);
	});
});
