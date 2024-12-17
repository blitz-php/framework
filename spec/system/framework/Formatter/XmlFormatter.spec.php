<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Formatter\XmlFormatter;
use BlitzPHP\Exceptions\FormatException;

use function Kahlan\expect;

describe('XmlFormatter', function() {
    beforeEach(function() {
        $this->formatter = new XmlFormatter();
    });

    describe('->format()', function() {
        it('doit formater un simple tableau en XML', function() {
            $data = ['name' => 'John', 'age' => 30];
            $expected = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<xml><name>John</name><age>30</age></xml>\n";
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('doit gérer les tableaux imbriqués', function() {
            $data = ['person' => ['name' => 'John', 'age' => 30]];
            $expected = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<xml><person><name>John</name><age>30</age></person></xml>\n";
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('doit convertir les valeurs booleennes en entier', function() {
            $data = ['active' => true, 'inactive' => false];
            $expected = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<xml><active>1</active><inactive>0</inactive></xml>\n";
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('doit gérer les clés numériques', function() {
            $data = ['items' => [1, 2, 3]];
            $expected = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<xml><items><item>1</item><item>2</item><item>3</item></items></xml>\n";
            expect($this->formatter->format($data))->toBe($expected);
        });

        it('doit gérer les attribues', function() {
            $data = ['element' => ['_attributes' => ['id' => '1', 'class' => 'test'], 'value' => 'content']];
            $expected = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<xml><element id=\"1\" class=\"test\"><value>content</value></element></xml>\n";
            expect($this->formatter->format($data))->toBe($expected);
        });
    });

    describe('->parse()', function() {
        it('doit analyser le XML et le convertir en un tableau', function() {
            $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><response><name>John</name><age>30</age></response>";
            $expected = ['name' => 'John', 'age' => '30'];
            expect($this->formatter->parse($xml))->toBe($expected);
        });

        it('doit gérer élements imbriqués', function() {
            $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><xml><person><name>John</name><age>30</age></person></xml>";
            $expected = ['person' => ['name' => 'John', 'age' => '30']];
            expect($this->formatter->parse($xml))->toBe($expected);
        });

        it('doit retourner un tableau vide pour les XML invalide', function() {
            $xml = "This is not valid XML";
            expect($this->formatter->parse($xml))->toBe([]);
        });

        it('doit gérer les attribues', function() {
            $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><xml><element id=\"1\" class=\"test\"><value>content</value></element></xml>";
            $expected = ['element' => ['@attributes' => ['id' => '1', 'class' => 'test'], 'value' => 'content']];
            expect($this->formatter->parse($xml))->toBe($expected);
        });
    });

    describe('__construct()', function() {
        xit('doit lever une exception FormatException si l\'extension simplexml n\'est pas chargée', function() {
            allow('extension_loaded')->toBeCalled()->with('simplexml')->andReturn(false);

            $closure = function() {
                new XmlFormatter();
            };

            expect($closure)->toThrow(FormatException::missingExtension());
        });
    });
});
