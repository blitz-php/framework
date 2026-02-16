<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Formatter\CsvFormatter;

use function Kahlan\allow;
use function Kahlan\expect;

describe('CsvFormatter', function(): void {
    beforeEach(function(): void {
        $this->formatter = new CsvFormatter();
    });

    describe('->format()', function(): void {
        it('devrait formater un tableau simple en CSV', function(): void {
            $data = ['nom' => 'Jean', 'age' => 30];
            $result = $this->formatter->format($data);
            $expected = mb_convert_encoding("nom,age\nJean,30\n", 'UTF-16LE', 'UTF-8');
            expect($result)->toBe($expected);
        });

        it('devrait formater un tableau multidimensionnel en CSV', function(): void {
            $data = [
                ['nom' => 'Jean', 'age' => 30],
                ['nom' => 'Marie', 'age' => 25]
            ];
            $result = $this->formatter->format($data);
            $expected = mb_convert_encoding("nom,age\nJean,30\nMarie,25\n", 'UTF-16LE', 'UTF-8');
            expect($result)->toBe($expected);
        });

        xit('devrait retourner null si l\'ouverture du fichier temporaire échoue', function(): void {
            allow('fopen')->toBeCalled()->andReturn(false);
            expect($this->formatter->format(['test' => 'data']))->toBeNull();
        });
    });

    describe('->parse()', function(): void {
        it('devrait analyser une chaîne CSV en tableau', function(): void {
            $csv = "nom,age\nJean,30\nMarie,25";
            $expected = [
                ['nom' => 'Jean', 'age' => '30'],
                ['nom' => 'Marie', 'age' => '25']
            ];
            expect($this->formatter->parse($csv))->toBe($expected);
        });

		it('devrait analyser une chaîne CSV en tableau', function(): void {
            $csv = "nom,age,sexe";
            $expected = ['nom', 'age', 'sexe'];
            expect($this->formatter->parse($csv))->toBe($expected);
        });
    });

    describe('->setDelimiter()', function(): void {
        it('devrait définir le délimiteur', function(): void {
            $this->formatter->setDelimiter(';');
            expect($this->formatter->getDelimiter())->toBe(';');
        });

        it('devrait utiliser le premier caractère si une chaîne plus longue est fournie', function(): void {
            $this->formatter->setDelimiter('abc');
            expect($this->formatter->getDelimiter())->toBe('a');
        });

        it('devrait utiliser la virgule par défaut si une chaîne vide est fournie', function(): void {
            $this->formatter->setDelimiter('');
            expect($this->formatter->getDelimiter())->toBe(',');
        });
    });

    describe('->setEnclosure()', function(): void {
        it('devrait définir l\'encadrement', function(): void {
            $this->formatter->setEnclosure('\'');
            expect($this->formatter->getEnclosure())->toBe('\'');
        });

        it('devrait utiliser le premier caractère si une chaîne plus longue est fournie', function(): void {
            $this->formatter->setEnclosure('abc');
            expect($this->formatter->getEnclosure())->toBe('a');
        });

        it('devrait utiliser les guillemets doubles par défaut si une chaîne vide est fournie', function(): void {
            $this->formatter->setEnclosure('');
            expect($this->formatter->getEnclosure())->toBe('"');
        });
    });
});
