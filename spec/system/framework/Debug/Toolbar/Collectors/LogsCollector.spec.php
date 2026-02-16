<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\Services;
use BlitzPHP\Debug\Logger;
use BlitzPHP\Debug\Toolbar\Collectors\LogsCollector;

use function Kahlan\expect;

describe('Debug / Toolbar / Collectors / LogsCollector', function(): void {
    beforeEach(function(): void {
        $this->logger = new Logger(debug: true);
        Services::injectMock('logger', $this->logger);
    });

    describe('display', function(): void {
        it('devrait afficher correctement les logs', function(): void {
            // log_message() crée toujours une nouvelle instance TestLogger pendant le test,
			// nous devons donc enregistrer directement dans notre instance.
            $this->logger->error('Test error');
            $this->logger->info('Test info');

            $collector = new LogsCollector();
            $result = $collector->display();

            expect($result)->toContainKey('logs');
            expect($result['logs'])->toHaveLength(2);
            expect($result['logs'][0]['level'])->toBe('error');
            expect($result['logs'][0]['msg'])->toBe('Test error');
            expect($result['logs'][1]['level'])->toBe('info');
            expect($result['logs'][1]['msg'])->toBe('Test info');
        });
    });

    describe('isEmpty', function(): void {
        it("doit renvoyer « true » lorsqu'il est vide", function(): void {
            $collector = new LogsCollector();
            expect($collector->isEmpty())->toBeTruthy();
        });

        it("doit renvoyer « false » lorsqu'il n'est pas vide", function(): void {
            $this->logger->warning('Test warning');

            $collector = new LogsCollector();
            expect($collector->isEmpty())->toBeFalsy();
        });
    });
});
