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

describe('JsonFormatter', function(): void {
    beforeEach(function(): void {
        $this->formatter = new JsonFormatter();
    });

    describe('->format()', function(): void {
        it('devrait formater un tableau simple en JSON', function(): void {
            $data = ['nom' => 'Jean', 'age' => 30];
            $expected = '{"nom":"Jean","age":30}';
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('devrait gérer les caractères Unicode', function(): void {
            $data = ['nom' => 'Éloïse'];
            $expected = '{"nom":"Éloïse"}';
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('devrait gérer les barres obliques', function(): void {
            $data = ['url' => 'http://exemple.com/chemin/vers/ressource'];
            $expected = '{"url":"http://exemple.com/chemin/vers/ressource"}';
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('devrait gérer le rappel JSONP valide', function(): void {
			$request = service('request');

			Services::override(
				Request::class,
				$request->withQueryParams(['callback' => 'maFonction'])
			);

            $data = ['nom' => 'Jean'];
            $expected = 'maFonction({"nom":"Jean"});';
            expect($this->formatter->format($data))->toBe($expected);

			Services::override(Request::class, $request);
        });

        it('devrait gérer le rappel JSONP invalide', function(): void {
			$request = service('request');

            Services::override(
				Request::class,
				$request->withQueryParams(['callback' => 'fonction invalide'])
			);

			$data = ['nom' => 'Jean'];
            $expected = '{"nom":"Jean","warning":"INVALID JSONP CALLBACK: fonction invalide"}';
            expect($this->formatter->format($data))->toBe($expected);

			Services::override(Request::class, $request);
        });
    });

    describe('->parse()', function(): void {
        it('devrait analyser une chaîne JSON en tableau', function(): void {
            $json = '{"nom":"Jean","age":30}';
            $expected = ['nom' => 'Jean', 'age' => 30];
            expect($this->formatter->parse($json))->toBe($expected);
        });

        it('devrait retourner un tableau vide pour une chaîne vide', function(): void {
            expect($this->formatter->parse(''))->toBe([]);
        });

        it('devrait supprimer les espaces blancs avant et après', function(): void {
            $json = '  {"nom":"Jean"}  ';
            $expected = ['nom' => 'Jean'];
            expect($this->formatter->parse($json))->toBe($expected);
        });
    });
});
