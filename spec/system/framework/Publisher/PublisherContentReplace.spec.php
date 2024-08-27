<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Publisher\Publisher;

use function Kahlan\expect;

describe('Publisher / PublisherContentReplacer', function (): void {
	beforeAll(function (): void {
		$restrictions = config('publisher.restrictions');
		config(['publisher.restrictions' => array_merge($restrictions, [__DIR__ => '*'])]);

		$this->publisher = new Publisher(__DIR__, __DIR__);
		$this->file 	 = __DIR__ . '/app.php';
	});
	beforeEach(function (): void {
        copy(CONFIG_PATH . 'app.php', $this->file);
	});
	afterAll(function (): void {
		config()->reset('publisher.restrictions');
	});
	afterEach(function (): void {
		unlink($this->file);
	});

	it('addLineAfter', function (): void {
		$result = $this->publisher->addLineAfter(
            $this->file,
            '\'csp_enabled\' => false,',
            '	\'permitted_uri_chars\'          => \'a-z 0-9~%.:_\-\',',
        );

        expect($result)->toBeTruthy();
		expect(file_get_contents($this->file))->toMatch(
			fn($actual) => str_contains($actual, "\n\t'permitted_uri_chars'          => 'a-z 0-9~%.:_\\-',\n'csp_enabled' => false,")
		);
	});

	it('addLineBefore', function (): void {
		$result = $this->publisher->addLineBefore(
            $this->file,
            '\'csp_enabled\' => false,',
            '	\'permitted_uri_chars\'          => \'a-z 0-9~%.:_\-\',',
        );

        expect($result)->toBeTruthy();
		expect(file_get_contents($this->file))->toMatch(
			fn($actual) => str_contains($actual, "\n'csp_enabled' => false,\n\t'permitted_uri_chars'          => 'a-z 0-9~%.:_\\-',\n")
		);
	});

	it('replace', function (): void {
		$result = $this->publisher->replace(
            $this->file,
            [
				'	\'permitted_uri_chars\'          => \'a-z 0-9~%.:_\-\',' => '	\'permitted_uri_chars\'          => \'\',',
				'	\'force_global_secure_requests\' => false,' => '	\'force_global_secure_requests\' => true,'
			]
        );

        expect($result)->toBeTruthy();
		expect(file_get_contents($this->file))->toMatch(
			fn($actual) => str_contains($actual, "\n\t'permitted_uri_chars'          => '',\n")
		);
		expect(file_get_contents($this->file))->toMatch(
			fn($actual) => str_contains($actual, "\n\t'force_global_secure_requests' => true,\n")
		);
	});
});
