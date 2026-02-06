<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Autoloader\Autoloader;
use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Autoloader\LocatorCached;
use BlitzPHP\Cache\Handlers\FileVarExportHandler;

use function Kahlan\expect;

describe('Autoloader / LocatorCached', function (): void {
    beforeEach(function (): void {
        $autoloader = new Autoloader(config('autoload'));
        $autoloader->initialize();
        $autoloader->addNamespace([
            'Unknown'       => '/i/do/not/exist',
            'Tests/Support' => TEST_PATH . '_support/',
            'App'           => APP_PATH,
            'BlitzPHP'   => [
                TEST_PATH,
                SYST_PATH,
            ],
            'Errors'              => APP_PATH . 'Views/errors',
            'System'              => SUPPORT_PATH . 'Autoloader/system',
            'Acme\SampleProject' => TEST_PATH . '_support',
            'Acme\Sample'        => TEST_PATH . '_support/does/not/exists',
        ]);

        $this->handler = new FileVarExportHandler();
        $fileLocator   = new Locator($autoloader);
        $this->locator = new LocatorCached($fileLocator, $this->handler);
    });

    afterEach(function (): void {
        $this->locator->__destruct();
    });

	afterAll(function (): void {
		// Nettoyage des fichiers de cache
        $autoloader  = new Autoloader();
        $handler     = new FileVarExportHandler();
        $fileLocator = new Locator($autoloader);
        $locator     = new LocatorCached($fileLocator, $handler);
        $locator->deleteCache();
	});

    it('Test de la suppression du cache', function (): void {
       expect($this->handler->get('FileLocatorCache'))->not->toBe([]);

	   $this->locator->deleteCache();

       expect($this->handler->get('FileLocatorCache'))->toBeFalsy();
	});
});
