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

describe('Commandes / MakeCommand', function (): void {
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

		$result = str_replace(["\033[0;32m", "\033[0m", "\n"], '', COH::buffer());
        $file   = str_replace('APP_PATH' . DS, APP_PATH, trim(substr($result, 14)));
        $dir    = dirname($file);

        if (is_file($file)) {
            unlink($file);
        }
        if (is_dir($dir) && str_contains($dir, 'Commands')) {
            rmdir($dir);
        }
    });

    it('Generation de la commande', function (): void {
		command('make:command deliver');

		$file = APP_PATH . 'Commands/Deliver.php';
		expect(file_exists($file))->toBeTruthy();

		$contents = $this->getFileContents($file);
		expect($contents)->toMatch(
			static fn ($actual) => str_contains($actual, 'protected $group = \'App\';')
		);
        expect($contents)->toMatch(
			static fn ($actual) => str_contains($actual, 'protected $name = \'command:name\';')
		);
	});

    it('Generation de la commande avec l\'option "command"', function (): void {
		command('make:command deliver --command=clear:sessions');

		$file = APP_PATH . 'Commands/Deliver.php';
		expect(file_exists($file))->toBeTruthy();

		$contents = $this->getFileContents($file);
		expect($contents)->toMatch(
			static fn ($actual) => str_contains($actual, 'protected $usage = \'clear:sessions [arguments] [options]\';')
		);
        expect($contents)->toMatch(
			static fn ($actual) => str_contains($actual, 'protected $name = \'clear:sessions\';')
		);
	});

	it('Generation de la commande avec l\'option "type=basic"', function (): void {
		command('make:command deliver --type=basic');

		$file = APP_PATH . 'Commands/Deliver.php';
		expect(file_exists($file))->toBeTruthy();

		$contents = $this->getFileContents($file);
		expect($contents)->toMatch(
			static fn ($actual) => str_contains($actual, 'protected $group = \'App\';')
		);
        expect($contents)->toMatch(
			static fn ($actual) => str_contains($actual, 'protected $name = \'command:name\';')
		);
	});

	it('Generation de la commande avec l\'option "type=generator"', function (): void {
		command('make:command deliver --type=generator');

		$file = APP_PATH . 'Commands/Deliver.php';
		expect(file_exists($file))->toBeTruthy();

		$contents = $this->getFileContents($file);
		expect($contents)->toMatch(
			static fn ($actual) => str_contains($actual, 'protected $group = \'App:Generateurs\';')
		);
        expect($contents)->toMatch(
			static fn ($actual) => str_contains($actual, 'protected $name = \'command:name\';')
		);
	});

	it('Generation de la commande avec l\'option "group"', function (): void {
		command('make:command deliver --group=Delivrables');

		expect(COH::buffer())->toMatch(
			static fn ($actual) => str_contains($actual, 'File created: ')
		);

		$file = APP_PATH . 'Commands/Deliver.php';
		expect(file_exists($file))->toBeTruthy();

		$contents = $this->getFileContents($file);
		expect($contents)->toMatch(
			static fn ($actual) => str_contains($actual, 'protected $group = \'Delivrables\';')
		);
	});

	it('Generation de la commande avec l\'option "suffix"', function (): void {
		command('make:command deliver --suffix');

		expect(COH::buffer())->toMatch(
			static fn ($actual) => str_contains($actual, 'File created: ')
		);

		$file = APP_PATH . 'Commands/DeliverCommand.php';
		expect(file_exists($file))->toBeTruthy();
	});

	it('Generation de la commande avec preservation de la casse', function (): void {
		command('make:command TestModule');

		expect(COH::buffer())->toMatch(
			static fn ($actual) => str_contains($actual, 'File created: ')
		);

		$file = APP_PATH . 'Commands/TestModule.php';
		expect(file_exists($file))->toBeTruthy();
	});

	it('Generation de la commande avec preservation de la casse mais en changeant la casse du composant', function (): void {
		command('make:command TestModulecommand');

		expect(COH::buffer())->toMatch(
			static fn ($actual) => str_contains($actual, 'File created: ')
		);

		$file = APP_PATH . 'Commands/TestModuleCommand.php';
		expect(file_exists($file))->toBeTruthy();
	});
});
