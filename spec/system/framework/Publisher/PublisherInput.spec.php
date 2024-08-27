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
use BlitzPHP\Spec\ReflectionHelper;
use BlitzPHP\Utilities\Helpers;

use function Kahlan\expect;

describe('Publisher / PublisherInput', function (): void {
	beforeAll(function (): void {
		$restrictions = config('publisher.restrictions');
		config(['publisher.restrictions' => array_merge($restrictions, [__DIR__ => '*'])]);
		helper(['filesystem']);

		$this->file 	 = str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/baker/banana.php');
		$this->directory = str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/able/');
	});
	afterAll(function (): void {
		config()->reset('publisher.restrictions');
	});

	it('AddPathFile', function (): void {
		$publisher = new Publisher(SUPPORT_PATH . 'Files');

        $publisher->addPath('baker/banana.php');

		expect($publisher->get())->toBe([$this->file]);
	});

	it('AddPathFile recursive', function (): void {
		$publisher = new Publisher(SUPPORT_PATH . 'Files');

        $publisher->addPath('baker/banana.php', true);

        expect($publisher->get())->toBe([$this->file]);
	});

	it('AddPathDirectory', function (): void {
		$publisher = new Publisher(SUPPORT_PATH . 'Files');

        $expected = [
            $this->directory . 'apple.php',
            $this->directory . 'fig_3.php',
            $this->directory . 'prune_ripe.php',
        ];

        $publisher->addPath('able');

        expect($publisher->get())->toBe($expected);
	});

	it('AddPathDirectory recursive', function (): void {
		$publisher = new Publisher(SUPPORT_PATH);

        $expected = [
			$this->directory . 'apple.php',
            $this->directory . 'fig_3.php',
            $this->directory . 'prune_ripe.php',
            str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/baker/banana.php'),
            str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/baker/fig_3.php.txt'),
        ];

        $publisher->addPath('Files');

        expect($publisher->get())->toBe($expected);
	});

	it('AddPaths', function (): void {
		$publisher = new Publisher(SUPPORT_PATH . 'Files');

        $expected = [
            $this->directory . 'apple.php',
            $this->directory . 'fig_3.php',
            $this->directory . 'prune_ripe.php',
            str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/baker/banana.php'),
        ];

		$publisher->addPaths([
            'able',
            'baker/banana.php',
        ]);

		expect($publisher->get())->toBe($expected);
	});

    it('AddPaths Recursive', function (): void {
		$publisher = new Publisher(SUPPORT_PATH);

        $expected = [
            $this->directory . 'apple.php',
            $this->directory . 'fig_3.php',
            $this->directory . 'prune_ripe.php',
            str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/baker/banana.php'),
            str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/baker/fig_3.php.txt'),
            str_replace(['/', '\\'], DS, SUPPORT_PATH . 'module/Config/routes.php'),
        ];

		$publisher->addPaths([
            'Files',
            'module',
        ], true);

		expect($publisher->get())->toBe($expected);
	});

	it('AddUri', function (): void {
		skipIf(!Helpers::isConnected());

		$publisher = new Publisher();
        $publisher->addUri('https://raw.githubusercontent.com/blitz-php/framework/devs/composer.json');

        $scratch = ReflectionHelper::getPrivateProperty($publisher, 'scratch');

		expect($publisher->get())->toBe([$scratch . 'composer.json']);
	});

	it('AddUris', function (): void {
		skipIf(!Helpers::isConnected());

		$publisher = new Publisher();
        $publisher->addUris([
			'https://raw.githubusercontent.com/blitz-php/framework/devs/LICENSE',
			'https://raw.githubusercontent.com/blitz-php/framework/devs/composer.json'
		]);

        $scratch = ReflectionHelper::getPrivateProperty($publisher, 'scratch');

		expect($publisher->get())->toBe([$scratch . 'LICENSE', $scratch . 'composer.json']);
	});
});
