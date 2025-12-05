<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\Iterable\LazyCollection;

use function Kahlan\expect;

describe('Utilities / Iterable / LazyCollection', function (): void {
    describe('Création paresseuse', function (): void {
        it('Doit créer une collection paresseuse avec un générateur', function (): void {
            $generator = function () {
                yield 1;
                yield 2;
                yield 3;
            };

            $collection = new LazyCollection($generator);
            expect($collection->all())->toBe([1, 2, 3]);
        });

        it('Doit évaluer paresseusement les opérations', function (): void {
            $executed = 0;
            $generator = function () use (&$executed) {
                $executed++;
                yield 1;
                $executed++;
                yield 2;
                $executed++;
                yield 3;
            };

            $collection = new LazyCollection($generator);
            $mapped = $collection->map(fn($x) => $x * 2);

            // L'exécution ne s'est pas encore produite
            expect($executed)->toBe(0);

            // Force l'évaluation
            $mapped->all();
            expect($executed)->toBe(3);
        });
    });

    describe('Opérations paresseuses', function (): void {
        it('Doit filtrer paresseusement', function (): void {
            $collection = LazyCollection::make(function () {
                yield 1;
                yield 2;
                yield 3;
                yield 4;
                yield 5;
            });

            $filtered = $collection->filter(fn($x) => $x % 2 === 0);
            expect($filtered->values()->all())->toBe([2, 4]);
        });

        it('Doit prendre des éléments paresseusement', function (): void {
            $collection = LazyCollection::make(function () {
                yield 1;
                yield 2;
                yield 3;
                yield 4;
                yield 5;
            });

            $taken = $collection->take(3);
            expect($taken->all())->toBe([1, 2, 3]);
        });

        it('Doit sauter des éléments paresseusement', function (): void {
            $collection = LazyCollection::make(function () {
                yield 1;
                yield 2;
                yield 3;
                yield 4;
                yield 5;
            });

            $skipped = $collection->skip(2);
            expect($skipped->values()->all())->toBe([3, 4, 5]);
        });
    });

    describe('Chunking paresseux', function (): void {
        it('Doit diviser en chunks paresseusement', function (): void {
            $collection = LazyCollection::make(function () {
                yield 1;
                yield 2;
                yield 3;
                yield 4;
            });

            $chunks = $collection->chunk(2);
            $chunkArray = $chunks->all();

            expect($chunkArray)->toHaveLength(2);
            expect($chunkArray[0]->all())->toBe([1, 2]);
            expect($chunkArray[1]->values()->all())->toBe([3, 4]);
        });
    });

    describe('Performance', function (): void {
        it('Doit gérer de grandes quantités de données sans mémoire excessive', function (): void {
            $largeGenerator = function () {
                for ($i = 0; $i < 10000; $i++) {
                    yield $i;
                }
            };

            $memoryBefore = memory_get_usage();
            $collection = new LazyCollection($largeGenerator);
            $filtered = $collection->filter(fn($x) => $x % 2 === 0)->take(10);
            $memoryDuring = memory_get_usage();

            expect($filtered->all())->toHaveLength(10);
            // La mémoire ne devrait pas augmenter significativement
            expect($memoryDuring - $memoryBefore)->toBeLessThan(1024 * 1024); // < 1MB
        });
    });
});
