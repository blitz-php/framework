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

use function Kahlan\expect;

describe('Publisher / PublisherRestrictions', function (): void {
	it('Registrars non autorisés', function (): void {
        expect(config('publisher.restrictions'))->not->toContainKey(SUPPORT_PATH);
	});

	it('Les restrictions sont immutable', function (): void {
		$publisher = new Publisher();

        // Essai de "hacker" le Publisher en ajoutant notre destination desiree a la config
        $config = config('publisher.restrictions');
        $config[SUPPORT_PATH] = '*';
        config(['publisher.restrictions' => $config]);

        $restrictions = ReflectionHelper::getPrivateProperty($publisher, 'restrictions');

        expect($restrictions)->not->toContainKey(SUPPORT_PATH);

        config()->reset('publisher.restrictions');
	});

	it('Restrictions publiques par defaut', function (): void {
		$paths = [
            'php'  => ['index.php'],
            'exe'  => ['cat.exe'],
            'flat' => ['banana'],
        ];

		config()->reset('publisher.restrictions');
        $pattern = config('publisher.restrictions.' . WEBROOT);

        foreach ($paths as [$path]) {
            $publisher = new Publisher(ROOTPATH, WEBROOT);

            // Use the scratch space to create a file
            $file = $publisher->getScratch() . $path;
            file_put_contents($file, 'To infinity and beyond!');

            $result = $publisher->addFile($file)->merge();
            expect($result)->toBeFalsy();

            $errors = $publisher->getErrors();
            expect($errors)->toHaveLength(1);
            expect(array_keys($errors))->toBe([$file]);

            $expected = lang('Publisher.fileNotAllowed', [$file, WEBROOT, $pattern]);
            expect($errors[$file]->getMessage())->toBe($expected);
        }
	});

	it('Destinations', function (): void {
        config()->set('publisher.restrictions', [
            APP_PATH                   => '',
            WEBROOT                    => '',
            SUPPORT_PATH . 'Files'     => '',
            SUPPORT_PATH . 'Files/../' => '',
        ]);
        $directories = [
            'explicit' => [APP_PATH, true],
            'subdirectory' => [APP_PATH . 'Config', true],
            'relative' => [SUPPORT_PATH . 'Files/able/../', true],
            'parent' => [SUPPORT_PATH, false],
        ];

        foreach ($directories as [$destination, $allowed]) {
            if (! $allowed) {
                expect(fn() => new Publisher(null, $destination))
					->toThrow(PublisherException::destinationNotAllowed($destination));
            } else {
				expect(new Publisher(null, $destination))->toBeAnInstanceOf(Publisher::class);
            }
        }

		config()->reset('publisher.restrictions');
	});
});
