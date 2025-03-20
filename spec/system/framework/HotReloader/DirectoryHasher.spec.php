<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Exceptions\FrameworkException;
use BlitzPHP\HotReloader\DirectoryHasher;

use function Kahlan\expect;

describe('HotReloader / DirectoryHasher', function (): void {
    beforeAll(function (): void {
		$this->hasher = new DirectoryHasher();
	});
    
    it('hashApp', function (): void {
        $results = $this->hasher->hashApp();

        expect($results)->toBeA('array');
        expect($results)->toContainKey('app');
    });

    it('Leve une exception si on essai de hasher un dossier invalide', function (): void {
        $path = $path = APP_PATH . 'Foo';

        expect(fn() => $this->hasher->hashDirectory($path))
            ->toThrow(FrameworkException::invalidDirectory($path));
    });

    it('Chaque dossier a un hash unique', function (): void {
        $hash1 = $this->hasher->hashDirectory(APP_PATH);
        $hash2 = $this->hasher->hashDirectory(SYST_PATH);

        expect($hash1)->not->toBe($hash2);
    });

    it('Un meme dossier produira le meme hash', function (): void {
        $hash1 = $this->hasher->hashDirectory(APP_PATH);
        $hash2 = $this->hasher->hashDirectory(APP_PATH);

        expect($hash1)->toBe($hash2);
    });

    it ('hash', function (): void {
        $expected = md5(implode('', $this->hasher->hashApp()));

        expect($expected)->toBe($this->hasher->hash());
    });
});
