<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Exceptions\PublisherException;
use BlitzPHP\Publisher\Publisher;
use BlitzPHP\Spec\ReflectionHelper;
use Spec\BlitzPHP\App\Publishers\TestPublisher;

use function Kahlan\expect;

describe('Publisher / PublisherSupport', function (): void {
	beforeAll(function () {
		helper('filesystem');

		$this->file = str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/baker/banana.php');
		$this->directory = str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/able/');
	});

	it('Decouverte par defaut', function (): void {
		$result = Publisher::discover();

		expect($result)->toHaveLength(1);
		expect($result[0])->toBeAnInstanceOf(TestPublisher::class);
	});

	it('Decouverte dans un dossiers non existant', function (): void {
		$result = Publisher::discover('Nothing');

		expect($result)->toBe([]);
	});

	it('DiscoverStores', function (): void {
		$publisher = Publisher::discover()[0];
        $publisher->set([])->addFile($this->file);

        $result = Publisher::discover();
        expect($publisher)->toBe($result[0]);
        expect([$this->file])->toBe($result[0]->get());
	});

	it('Recuperation de la source et destination', function (): void {
		$publisher = new Publisher(ROOTPATH);
       	expect($publisher->getSource())->toBe(ROOTPATH);

		config()->set('publisher.restrictions', [SUPPORT_PATH => '']);

		$publisher = new Publisher(ROOTPATH, SUPPORT_PATH);
		expect($publisher->getDestination())->toBe(SUPPORT_PATH);

		config()->reset('publisher.restrictions');
	});

	it('Recuperation du scratch', function (): void {
        $publisher = new Publisher();
		expect(ReflectionHelper::getPrivateProperty($publisher, 'scratch'))->toBeNull();

        $scratch = $publisher->getScratch();

		expect(is_string($scratch))->toBeTruthy();
		expect(is_dir($scratch))->toBeTruthy();
		expect(is_writable($scratch))->toBeTruthy();
		expect(ReflectionHelper::getPrivateProperty($publisher, 'scratch'))->not->toBeNull();

        // Le dossier et les contenus vont etre supprimes lors du __destruct()
        $file = $scratch . 'obvious_statement.txt';
        file_put_contents($file, 'Bananas are a most peculiar fruit');

        $publisher->__destruct();

        expect(is_file($file))->toBeFalsy();
		expect(is_dir($scratch))->toBeFalsy();
	});

	it('Recuperation des erreurs', function () {
		$publisher = new Publisher();
        expect($publisher->getErrors())->toBe([]);

        $expected = [
            $this->file => PublisherException::collision($this->file, $this->file),
        ];

		ReflectionHelper::setPrivateProperty($publisher, 'errors', $expected);

        expect($publisher->getErrors())->toBe($expected);
	});

	it('wipeDirectory', function () {
		$directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        expect(is_dir($directory))->toBeTruthy();

        $method = ReflectionHelper::getPrivateMethodInvoker(Publisher::class, 'wipeDirectory');
        $method($directory);

        expect(is_dir($directory))->toBeFalsy();
	});

	it('wipeIgnoresFiles', function () {
		$method = ReflectionHelper::getPrivateMethodInvoker(Publisher::class, 'wipeDirectory');
        $method($this->file);

        expect(is_file($this->file))->toBeTruthy();
	});

	it('wipe', function () {
		$directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . bin2hex(random_bytes(6));
        mkdir($directory, 0700);
		$directory = realpath($directory) ?: $directory;
        expect(is_dir($directory))->toBeTruthy();

		$restrictions = config('publisher.restrictions');
		$restrictions[$directory] = ''; // On autorise ce dossier
		config(['publisher.restrictions' => $restrictions]);

		$publisher = new Publisher($this->directory, $directory);
        $publisher->wipe();

        expect(is_dir($directory))->toBeFalsy();
	});
});
