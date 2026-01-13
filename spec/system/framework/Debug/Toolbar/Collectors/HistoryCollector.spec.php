<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Debug\Toolbar\Collectors\HistoryCollector;

use function Kahlan\expect;

describe('Debug / Toolbar / Collectors / HistoryCollector', function() {
    beforeAll(function() {
		$this->STEP = 0.000001;

		$this->createDummyDebugbarJson = function() {
        	$time = $this->time;
			$path = FRAMEWORK_STORAGE_PATH . 'debugbar' . DS . "debugbar_{$time}.json";

			if (! is_dir($dir = dirname($path))) {
				mkdir($dir, 0755, true);
			}

        	$dummyData = [
            	'vars' => [
                	'response' => [
                    	'statusCode'  => 200,
                    	'contentType' => 'text/html; charset=UTF-8',
                	],
            	],
            	'method' => 'get',
            	'url'    => 'localhost',
            	'isAJAX' => false,
        	];

			// creer 20 faux ficher json de debugbar
			for ($i = 0; $i < 20; $i++) {
				$path = str_replace((string) $time, sprintf('%.6F', $time - $this->STEP), $path);
				file_put_contents($path, json_encode($dummyData));
				$time = sprintf('%.6F', $time - $this->STEP);
			}
    	};
    });

    beforeEach(function() {
        $this->time = (float) sprintf('%.6F', microtime(true));
    });

    afterEach(function() {
        command('debugbar:clear');
    });


    describe('setFiles', function() {
        it('Devrait configurer les fichiers correctement', function() {
            $time = $this->time;

            // Le répertoire test est désormais rempli avec json.
            $this->createDummyDebugbarJson();

            $activeRowTime = $time = sprintf('%.6F', $time - $this->STEP);

            $history = new HistoryCollector();
            $history->setFiles($time, 20);

            $display = $history->display();

            expect($display)->toContainKey('files');
            expect($display['files'])->not->toBeEmpty();

            foreach ($display['files'] as $request) {
                expect($request['time'])->toBe(sprintf('%.6F', $time));
                expect($request['datetime'])->toBe(
                    DateTime::createFromFormat('U.u', $time)->format('Y-m-d H:i:s.u')
                );
                expect($request['active'])->toBe(($time === $activeRowTime));

                $time = sprintf('%.6F', $time - $this->STEP);
            }
        });
    });
});
