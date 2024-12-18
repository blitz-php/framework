<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Formatter\ArrayFormatter;

describe('ArrayFormatter', function () {

    beforeEach(function () {
        $this->formatter = new ArrayFormatter();
    });

    describe('->format()', function () {

        it('devrait formater un tableau simple', function () {
            $data = ['clé' => 'valeur'];
            $result = $this->formatter->format($data);
            expect($result)->toBe($data);
        });

        it('devrait formater un objet en tableau', function () {
            $data = (object) ['clé' => 'valeur'];
            $result = $this->formatter->format($data);
            expect($result)->toBe(['clé' => 'valeur']);
        });

        it('devrait formater un tableau imbriqué', function () {
            $data = ['clé' => ['sous_clé' => 'valeur']];
            $result = $this->formatter->format($data);
            expect($result)->toBe($data);
        });

        it('devrait formater un objet imbriqué en tableau', function () {
            $data = (object) ['clé' => (object) ['sous_clé' => 'valeur']];
            $result = $this->formatter->format($data);
            expect($result)->toBe(['clé' => ['sous_clé' => 'valeur']]);
        });
    });

    describe('->parse()', function () {

        it('devrait retourner une chaîne dans un tableau', function () {
            $data = 'chaîne';
            $result = $this->formatter->parse($data);
            expect($result)->toBe([$data]);
        });
    });
});
