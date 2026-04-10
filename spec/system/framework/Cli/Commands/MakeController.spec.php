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

describe('Commandes / MakeController', function (): void {
    beforeAll(function (): void {
        COH::setUpBeforeClass();

		$this->getFileContents = function(string $filepath): string {
			if (! is_file($filepath)) {
				return '';
			}

			return file_get_contents($filepath) ?: '';
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

		$buffer = str_replace(["\033[0;32m", "\033[0m", "\n", "\x1b[0;38;5;119m"], '', COH::buffer());
		preg_match('/\[([^\]]+\.php)\]/', $buffer, $matches);

		if (!isset($matches[1])) {
			return;
		}

		$result = explode('[', $matches[1]);
		$file   = array_pop($result);

		if ($file !== '' && is_file($file)) {
            unlink($file);
        }
    });

    it('Generation de controleur', function (): void {
		command('make:controller user');

        $file   = CONTROLLER_PATH . 'UserController.php';
        $buffer = COH::buffer();

		expect(file_exists($file))->toBeTruthy();

		expect($buffer)->toMatch(
			static fn ($actual): bool => str_contains($actual, 'Créé avec succès')
		);

		expect($this->getFileContents($file))->toMatch(
			static fn ($actual): bool => str_contains($actual, 'class UserController extends AppController')
		);
	});

	it('Generation de controleur avec l\'option bare', function (): void {
		command('make:controller blog --bare');

        $file   = CONTROLLER_PATH . 'BlogController.php';
        $buffer = COH::buffer();

		expect(file_exists($file))->toBeTruthy();

		expect($buffer)->toMatch(
			static fn ($actual): bool => str_contains($actual, 'Créé avec succès')
		);

		expect($this->getFileContents($file))->toMatch(
			static fn ($actual): bool => str_contains($actual, 'extends BaseController')
		);
	});


	it('Generation de controleur avec l\'option restful', function (): void {
		command('make:controller order --restful');

        $file   = CONTROLLER_PATH . 'OrderController.php';
        $buffer = COH::buffer();

		expect(file_exists($file))->toBeTruthy();

		expect($buffer)->toMatch(
			static fn ($actual): bool => str_contains($actual, 'Créé avec succès')
		);

		expect($this->getFileContents($file))->toMatch(
			static fn ($actual): bool => str_contains($actual, 'class OrderController extends ResourceController')
		);
	});

	it('Generation de controleur avec l\'option restful', function (): void {
		command('make:controller pay --restful=presenter');

        $file   = CONTROLLER_PATH . 'PayController.php';
        $buffer = COH::buffer();

		expect(file_exists($file))->toBeTruthy();

		expect($buffer)->toMatch(
			static fn ($actual): bool => str_contains($actual, 'Créé avec succès')
		);

		expect($this->getFileContents($file))->toMatch(
			static fn ($actual): bool => str_contains($actual, 'class PayController extends ResourcePresenter')
		);
	});

	it('Generation de controleur avec l\'option suffix', function (): void {
		command('make:controller dashboard --suffix');

        $file   = CONTROLLER_PATH . 'DashboardController.php';
        $buffer = COH::buffer();

		expect(file_exists($file))->toBeTruthy();

		expect($buffer)->toMatch(
			static fn ($actual): bool => str_contains($actual, 'Créé avec succès')
		);
	});
});
