<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Formatter\Formatter;
use BlitzPHP\Formatter\JsonFormatter;
use BlitzPHP\Formatter\XmlFormatter;
use BlitzPHP\Formatter\CsvFormatter;
use BlitzPHP\Formatter\ArrayFormatter;
use BlitzPHP\Exceptions\FormatException;

use function Kahlan\expect;

describe('Formatter', function() {
    describe('::type()', function() {
        it('doit renvoyer le formateur correct pour un type MIME valide', function() {
            expect(Formatter::type('application/json'))->toBeAnInstanceOf(JsonFormatter::class);
            expect(Formatter::type('json'))->toBeAnInstanceOf(JsonFormatter::class);
            expect(Formatter::type('application/xml'))->toBeAnInstanceOf(XmlFormatter::class);
            expect(Formatter::type('text/xml'))->toBeAnInstanceOf(XmlFormatter::class);
            expect(Formatter::type('xml'))->toBeAnInstanceOf(XmlFormatter::class);
            expect(Formatter::type('application/csv'))->toBeAnInstanceOf(CsvFormatter::class);
            expect(Formatter::type('csv'))->toBeAnInstanceOf(CsvFormatter::class);
            expect(Formatter::type('php/array'))->toBeAnInstanceOf(ArrayFormatter::class);
            expect(Formatter::type('array'))->toBeAnInstanceOf(ArrayFormatter::class);
        });

        it('doit lever une exception FormatException si le type MIME n\'est pas valide', function() {
            $closure = function() {
                Formatter::type('invalid/mime');
            };
            expect($closure)->toThrow(FormatException::invalidMime('invalid/mime'));
        });

        it('devrait lever une exception FormatException pour une classe de formateur inexistante', function() {
            // nous devons modifier une propriété protégée pour ce test
            $reflection = new ReflectionClass(Formatter::class);
            $formatters = $reflection->getProperty('formatters');
            $formatters->setAccessible(true);
            $originalFormatters = $formatters->getValue();

            $formatters->setValue(array_merge($originalFormatters, ['test/mime' => 'NonExistentFormatter']));

            $closure = function() {
                Formatter::type('test/mime');
            };
            expect($closure)->toThrow(FormatException::invalidFormatter('NonExistentFormatter'));

            // Restaurer les formatters d'origine
            $formatters->setValue($originalFormatters);
        });

        it('devrait lancer une exception FormatException pour une classe de formateur n\'implémentant pas FormatterInterface', function() {
            // nous devons modifier une propriété protégée pour ce test
            $reflection = new ReflectionClass(Formatter::class);
            $formatters = $reflection->getProperty('formatters');
            $formatters->setAccessible(true);
            $originalFormatters = $formatters->getValue();

            $formatters->setValue(array_merge($originalFormatters, ['test/mime' => stdClass::class]));

            $closure = function() {
                Formatter::type('test/mime');
            };
            expect($closure)->toThrow(FormatException::invalidFormatter('stdClass'));

            // Restaurer les formatters d'origine
            $formatters->setValue($originalFormatters);
        });
    });
});
