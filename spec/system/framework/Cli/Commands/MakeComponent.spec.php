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

describe('Commandes / MakeComponent', function (): void {
    beforeAll(function (): void {
        COH::setUpBeforeClass();

		$this->getFileContents = function(string $filepath): string {
			if (! is_file($filepath)) {
				return '';
			}

			return file_get_contents($filepath) ?: '';
		};

		$this->componentsDirName = APP_PATH . 'Components';
		$this->undeletedFiles    = is_dir($this->componentsDirName) ? array_diff(scandir($this->componentsDirName), ['.', '..']) : [];
	});

    afterAll(function (): void {
        COH::tearDownAfterClass();

        if (is_dir($dirName = $this->componentsDirName)) {
			$files   = array_diff(scandir($dirName), ['.', '..']);
			$nbFiles = count($files);

            foreach ($files as $file) {
				if (!in_array($file, $this->undeletedFiles)) {
					(is_dir("{$dirName}/{$file}")) ? rmdir("{$dirName}/{$file}") : unlink("{$dirName}/{$file}");
					$nbFiles--;
				}
            }

			if ($nbFiles == 0) {
				rmdir($dirName);
			}
        }
    });

    beforeEach(function (): void {
        COH::setUp();
    });

    afterEach(function (): void {
        COH::tearDown();
    });

    it('Generation de composant', function (): void {
		command('make:component RecentComponent');

		$buffer = COH::buffer();
		$files  = [
			APP_PATH . 'Components' . DS . 'RecentComponent.php'  => 'class RecentComponent extends Component',
			APP_PATH . 'Components' . DS . 'recent-component.php' => "<!-- Votre HTML ici -->"
		];

		foreach ($files as $file => $content) {
			expect(file_exists($file))->toBeTruthy();

			expect($buffer)->toMatch(
				static fn ($actual) => str_contains($actual, 'File created: ' . clean_path($file))
			);

			expect($this->getFileContents($file))->toMatch(
				static fn ($actual) => str_contains($actual, $content)
			);
		}
	});

	it('Generation de composant avec un nom sans le suffixe', function (): void {
		command('make:component Another');

		$buffer = COH::buffer();
		$files  = [
			APP_PATH . 'Components' . DS . 'AnotherComponent.php'  => 'class AnotherComponent extends Component',
			APP_PATH . 'Components' . DS . 'another-component.php' => "<!-- Votre HTML ici -->"
		];

		foreach ($files as $file => $content) {
			expect(file_exists($file))->toBeTruthy();

			expect($buffer)->toMatch(
				static fn ($actual) => str_contains($actual, 'File created: ' . clean_path($file))
			);

			expect($this->getFileContents($file))->toMatch(
				static fn ($actual) => str_contains($actual, $content)
			);
		}
	});

	it('Generation de composant avec le mot "Component" dans le nom de la classe', function (): void {
		command('make:component OneComponentForm');

		$buffer = COH::buffer();
		$files  = [
			APP_PATH . 'Components' . DS . 'OneComponentFormComponent.php'  => 'class OneComponentFormComponent extends Component',
			APP_PATH . 'Components' . DS . 'one-component-form-component.php' => "<!-- Votre HTML ici -->"
		];

		foreach ($files as $file => $content) {
			expect(file_exists($file))->toBeTruthy();

			expect($buffer)->toMatch(
				static fn ($actual) => str_contains($actual, 'File created: ' . clean_path($file))
			);

			expect($this->getFileContents($file))->toMatch(
				static fn ($actual) => str_contains($actual, $content)
			);
		}
	});
});
