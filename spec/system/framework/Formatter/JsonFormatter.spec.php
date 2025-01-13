<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Formatter\JsonFormatter;
use BlitzPHP\Container\Services;
use BlitzPHP\Http\Request;

describe('JsonFormatter', function() {
    beforeEach(function() {
        $this->formatter = new JsonFormatter();
    });

    describe('->format()', function() {
        it('devrait formater un tableau simple en JSON', function() {
            $data = ['nom' => 'Jean', 'age' => 30];
            $expected = '{"nom":"Jean","age":30}';
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('devrait gérer les caractères Unicode', function() {
            $data = ['nom' => 'Éloïse'];
            $expected = '{"nom":"Éloïse"}';
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('devrait gérer les barres obliques', function() {
            $data = ['url' => 'http://exemple.com/chemin/vers/ressource'];
            $expected = '{"url":"http://exemple.com/chemin/vers/ressource"}';
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('devrait gérer le rappel JSONP valide', function() {
			Services::set(
				Request::class,
				service('request')->withQueryParams(['callback' => 'maFonction'])
			);

            $data = ['nom' => 'Jean'];
            $expected = 'maFonction({"nom":"Jean"});';
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('devrait gérer le rappel JSONP invalide', function() {
            Services::set(
				Request::class,
				service('request')->withQueryParams(['callback' => 'fonction invalide'])
			);

			$data = ['nom' => 'Jean'];
            $expected = '{"nom":"Jean","warning":"INVALID JSONP CALLBACK: fonction invalide"}';
            expect($this->formatter->format($data))->toBe($expected);
        });
    });

    describe('->parse()', function() {
        it('devrait analyser une chaîne JSON en tableau', function() {
            $json = '{"nom":"Jean","age":30}';
            $expected = ['nom' => 'Jean', 'age' => 30];
            expect($this->formatter->parse($json))->toBe($expected);
        });

        it('devrait retourner un tableau vide pour une chaîne vide', function() {
            expect($this->formatter->parse(''))->toBe([]);
        });

        it('devrait supprimer les espaces blancs avant et après', function() {
            $json = '  {"nom":"Jean"}  ';
            $expected = ['nom' => 'Jean'];
            expect($this->formatter->parse($json))->toBe($expected);
        });
    });
});
