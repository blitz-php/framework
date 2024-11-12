<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Cli\Console\Console;
use BlitzPHP\Spec\CliOutputHelper as COH;

use function Kahlan\expect;

describe('Commandes / TranslationsFinder', function (): void {
    afterEach(function (): void {
        COH::tearDown();

		$this->clearGeneratedFiles();
    });

	beforeEach(function (): void {
        COH::setUp();
    });

    afterAll(function (): void {
        COH::tearDownAfterClass();
    });

	beforeAll(function () {
		COH::setUpBeforeClass();

		$this->locale           = Locale::getDefault();
        $this->languageTestPath = ROOTPATH . 'Translations' . DS;

		$this->getActualTranslationOneKeys = function(): array  {
			return [
				'title'                  => 'TranslationOne.title',
				'DESCRIPTION'            => 'TranslationOne.DESCRIPTION',
				'subTitle'               => 'TranslationOne.subTitle',
				'overflow_style'         => 'TranslationOne.overflow_style',
				'metaTags'               => 'TranslationOne.metaTags',
				'Copyright'              => 'TranslationOne.Copyright',
				'last_operation_success' => 'TranslationOne.last_operation_success',
			];
		};

		$this->getActualTranslationThreeKeys = function(): array {
			return [
				'alerts' => [
					'created'       => 'TranslationThree.alerts.created',
					'failed_insert' => 'TranslationThree.alerts.failed_insert',
					'CANCELED'      => 'TranslationThree.alerts.CANCELED',
					'missing_keys'  => 'TranslationThree.alerts.missing_keys',
					'Updated'       => 'TranslationThree.alerts.Updated',
					'DELETED'       => 'TranslationThree.alerts.DELETED',
				],
				'formFields' => [
					'new' => [
						'name'      => 'TranslationThree.formFields.new.name',
						'TEXT'      => 'TranslationThree.formFields.new.TEXT',
						'short_tag' => 'TranslationThree.formFields.new.short_tag',
					],
					'edit' => [
						'name'      => 'TranslationThree.formFields.edit.name',
						'TEXT'      => 'TranslationThree.formFields.edit.TEXT',
						'short_tag' => 'TranslationThree.formFields.edit.short_tag',
					],
				],
				'formErrors' => [
					'edit' => [
						'empty_name'        => 'TranslationThree.formErrors.edit.empty_name',
						'INVALID_TEXT'      => 'TranslationThree.formErrors.edit.INVALID_TEXT',
						'missing_short_tag' => 'TranslationThree.formErrors.edit.missing_short_tag',
					],
				],
			];
		};

    	$this->getActualTranslationFourKeys = function(): array {
			return [
				'dashed' => [
					'key-with-dash'     => 'Translation-Four.dashed.key-with-dash',
					'key-with-dash-two' => 'Translation-Four.dashed.key-with-dash-two',
				],
			];
		};

		$this->assertTranslationsExistAndHaveTranslatedKeys = function(): void {
			expect(file_exists($this->languageTestPath . $this->locale . '/TranslationOne.php'))->toBeTruthy();
			expect(file_exists($this->languageTestPath . $this->locale . '/TranslationThree.php'))->toBeTruthy();
			expect(file_exists($this->languageTestPath . $this->locale . '/Translation-Four.php'))->toBeTruthy();

			$translationOneKeys   = require $this->languageTestPath . $this->locale . '/TranslationOne.php';
			$translationThreeKeys = require $this->languageTestPath . $this->locale . '/TranslationThree.php';
			$translationFourKeys  = require $this->languageTestPath . $this->locale . '/Translation-Four.php';

        	expect($translationOneKeys,)->toBe($this->getActualTranslationOneKeys());
        	expect($translationThreeKeys)->toBe($this->getActualTranslationThreeKeys());
        	expect($translationFourKeys)->toBe($this->getActualTranslationFourKeys());
    	};

    	$this->makeLocaleDirectory = function(): void {
        	@mkdir($this->languageTestPath . $this->locale, 0777, true);
    	};

		$this->clearGeneratedFiles = function(): void {
			if (is_file($this->languageTestPath . $this->locale . '/TranslationOne.php')) {
				unlink($this->languageTestPath . $this->locale . '/TranslationOne.php');
			}

			if (is_file($this->languageTestPath . $this->locale . '/TranslationThree.php')) {
				unlink($this->languageTestPath . $this->locale . '/TranslationThree.php');
			}

			if (is_file($this->languageTestPath . $this->locale . '/Translation-Four.php')) {
				unlink($this->languageTestPath . $this->locale . '/Translation-Four.php');
			}

			if (is_dir($this->languageTestPath . '/test_locale_incorrect')) {
				rmdir($this->languageTestPath . '/test_locale_incorrect');
			}
		};

		$this->getActualTableWithNewKeys = function(): string {
			return <<<'TEXT_WRAP'
				+------------------+----------------------------------------------------+
				| File             | Key                                                |
				+------------------+----------------------------------------------------+
				| Translation-Four | Translation-Four.dashed.key-with-dash              |
				| Translation-Four | Translation-Four.dashed.key-with-dash-two          |
				| TranslationOne   | TranslationOne.Copyright                           |
				| TranslationOne   | TranslationOne.DESCRIPTION                         |
				| TranslationOne   | TranslationOne.last_operation_success              |
				| TranslationOne   | TranslationOne.metaTags                            |
				| TranslationOne   | TranslationOne.overflow_style                      |
				| TranslationOne   | TranslationOne.subTitle                            |
				| TranslationOne   | TranslationOne.title                               |
				| TranslationThree | TranslationThree.alerts.CANCELED                   |
				| TranslationThree | TranslationThree.alerts.DELETED                    |
				| TranslationThree | TranslationThree.alerts.Updated                    |
				| TranslationThree | TranslationThree.alerts.created                    |
				| TranslationThree | TranslationThree.alerts.failed_insert              |
				| TranslationThree | TranslationThree.alerts.missing_keys               |
				| TranslationThree | TranslationThree.formErrors.edit.INVALID_TEXT      |
				| TranslationThree | TranslationThree.formErrors.edit.empty_name        |
				| TranslationThree | TranslationThree.formErrors.edit.missing_short_tag |
				| TranslationThree | TranslationThree.formFields.edit.TEXT              |
				| TranslationThree | TranslationThree.formFields.edit.name              |
				| TranslationThree | TranslationThree.formFields.edit.short_tag         |
				| TranslationThree | TranslationThree.formFields.new.TEXT               |
				| TranslationThree | TranslationThree.formFields.new.name               |
				| TranslationThree | TranslationThree.formFields.new.short_tag          |
				+------------------+----------------------------------------------------+
				TEXT_WRAP;
		};

		$this->getActualTableWithBadKeys = function(): string {
			return <<<'TEXT_WRAP'
				+------------------------+-----------------------------------------+
				| Bad Key                | Filepath                                |
				+------------------------+-----------------------------------------+
				| ..invalid_nested_key.. | Services\Translation\TranslationTwo.php |
				| ..invalid_nested_key.. | Services\Translation\TranslationTwo.php |
				| .invalid_key           | Services\Translation\TranslationTwo.php |
				| .invalid_key           | Services\Translation\TranslationTwo.php |
				| TranslationTwo         | Services\Translation\TranslationTwo.php |
				| TranslationTwo         | Services\Translation\TranslationTwo.php |
				| TranslationTwo.        | Services\Translation\TranslationTwo.php |
				| TranslationTwo.        | Services\Translation\TranslationTwo.php |
				| TranslationTwo...      | Services\Translation\TranslationTwo.php |
				| TranslationTwo...      | Services\Translation\TranslationTwo.php |
				+------------------------+-----------------------------------------+
				TEXT_WRAP;
		};
	});


    it('update locale', function (): void {
		$this->makeLocaleDirectory();

        command('translations:find --dir Translation');

		$this->assertTranslationsExistAndHaveTranslatedKeys();
	});

    it('update avec l\'option locale', function (): void {
		$this->locale = config('app.supported_locales')[0];
        $this->makeLocaleDirectory();

        command('translations:find --dir Translation --locale ' . $this->locale);

		$this->assertTranslationsExistAndHaveTranslatedKeys();
	});

    it('update avec une locale invalide', function (): void {
		$this->locale = 'test_locale_incorrect';
        $this->makeLocaleDirectory();

		$status = service(Console::class)->call('translations:find', [
            'dir'    => 'Translation',
            'locale' => $this->locale,
        ]);

        expect($status)->toBe(EXIT_USER_INPUT);
	});

    it('update avec aucune option', function (): void {
		$this->makeLocaleDirectory();

        command('translations:find');

		$this->assertTranslationsExistAndHaveTranslatedKeys();
	});

    it('update avec une option dir invalide', function (): void {
		$this->makeLocaleDirectory();

		$status = service(Console::class)->call('translations:find', [
            'dir' => 'Translation/NotExistFolder',
        ]);

        expect($status)->toBe(EXIT_USER_INPUT);
	});

	it('Affichage des nouvelles traductions', function (): void {
		$this->makeLocaleDirectory();

        command('translations:find --dir Translation --show-new');

		$buffer = COH::buffer();
		$lines  = explode("\n", $this->getActualTableWithNewKeys());

		foreach ($lines as $line) {
			expect($buffer)->toMatch(fn($actual) => str_contains($actual, $line));
		}
	});

	it('Affichage des mauvaises traductions', function (): void {
		$this->makeLocaleDirectory();

        command('translations:find --dir Translation --verbose');

		$buffer = COH::buffer();
		$lines  = explode("\n", $this->getActualTableWithBadKeys());
		// hack pour les systemes linux (github actions)
		$lines = array_map(fn($line) => str_replace(['Services\\', 'Translation\\'], ['Services' . DS, 'Translation' . DS], $line), $lines);

		foreach ($lines as $line) {
			expect($buffer)->toMatch(fn($actual) => str_contains($actual, $line));
		}
	});
});
