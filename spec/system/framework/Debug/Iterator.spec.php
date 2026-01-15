<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Debug\Iterator;

use function Kahlan\expect;

describe('Debug / Iterator', function (): void {
    beforeEach(function (): void {
        $this->iterator = new Iterator();
    });

    it('ajoute un test avec succès', function (): void {
        $result = $this->iterator->add('test1', function () {
            return 'result';
        });

        expect($result)->toBe($this->iterator);
    });

    it('convertit le nom en minuscules', function (): void {
        $this->iterator->add('TEST_NAME', function () {
            return 'result';
        });

        // Utiliser la réflexion pour vérifier le nom stocké
        $reflection = new ReflectionClass($this->iterator);
        $property = $reflection->getProperty('tests');
        $property->setAccessible(true);
        $tests = $property->getValue($this->iterator);

        expect($tests)->toContainKey('test_name');
    });

    it('exécute les tests et génère des résultats', function (): void {
        $this->iterator->add('fast_test', function () {
            sleep(1); // 1 s
            return 'fast';
        });

        $this->iterator->add('slow_test', function () {
            sleep(2); // 2s
            return 'slow';
        });

        $result = $this->iterator->run(10, false);

        expect($result)->toBeNull();

        $reflection = new ReflectionClass($this->iterator);
        $property = $reflection->getProperty('results');
        $property->setAccessible(true);
        $results = $property->getValue($this->iterator);

        expect($results)->toContainKey('fast_test');
        expect($results)->toContainKey('slow_test');
        expect($results['fast_test'])->toContainKey('time');
        expect($results['fast_test'])->toContainKey('memory');
        expect($results['fast_test'])->toContainKey('n');
        expect($results['fast_test']['n'])->toBe(10);

        // Le test lent devrait prendre plus de temps
        expect($results['slow_test']['time'] > $results['fast_test']['time'])->toBeTruthy();
    });

    it('génère un rapport HTML avec les résultats', function (): void {
        $this->iterator->add('test1', function () {
            usleep(100);
            return 'result1';
        });

        $this->iterator->add('test2', function () {
            usleep(200);
            return 'result2';
        });

        $this->iterator->run(5, false);
        $report = $this->iterator->getReport();

        expect($report)->toBeA('string');
        expect($report)->toContain('<table>');
        expect($report)->toContain('test1');
        expect($report)->toContain('test2');
        expect($report)->toContain('</table>');
    });

    it('retourne un message si aucun résultat', function (): void {
        $report = $this->iterator->getReport();

        expect($report)->toBe('No results to display.');
    });

    it('gère différentes itérations', function (): void {
        $counter = 0;

        $this->iterator->add('counter_test', function () use (&$counter) {
            $counter++;
            return $counter;
        });

        $iterations = 50;
        $this->iterator->run($iterations, false);

        expect($counter)->toBe($iterations);
    });

    it('produit un rapport avec sortie activée', function (): void {
        $this->iterator->add('output_test', function () {
            usleep(100);
            return 'output';
        });

        $report = $this->iterator->run(5, true);

        expect($report)->toBeA('string');
        expect($report)->toContain('output_test');
        expect($report)->toContain('<table>');
    });

    it('formate correctement la mémoire dans le rapport', function (): void {
        $this->iterator->add('format_test', function () {
            // Allouer ~1KB de mémoire
            $string = str_repeat('x', 1024);
            return $string;
        });

        $this->iterator->run(1, false);
        $report = $this->iterator->getReport();

        // Devrait contenir une unité de mémoire
        expect($report)->toMatch('/\d+(\.\d+)? (B|KB|MB|GB|TB)/');
    });
});
