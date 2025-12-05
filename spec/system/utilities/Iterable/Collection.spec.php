<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\Iterable\Collection;

use function Kahlan\expect;

describe('Utilities / Iterable / Collection', function (): void {
    describe('Création', function (): void {
        it('Doit créer une collection vide', function (): void {
            $collection = new Collection();
            expect($collection->all())->toBe([]);
            expect($collection->isEmpty())->toBe(true);
        });

        it('Doit créer une collection avec des éléments', function (): void {
            $collection = new Collection([1, 2, 3]);
            expect($collection->all())->toBe([1, 2, 3]);
            expect($collection->count())->toBe(3);
        });

        it('Doit créer une collection avec make()', function (): void {
            $collection = Collection::make([1, 2, 3]);
            expect($collection)->toBeAnInstanceOf(Collection::class);
            expect($collection->all())->toBe([1, 2, 3]);
        });
    });

    describe('Manipulation', function (): void {
        it('Doit ajouter des éléments', function (): void {
            $collection = new Collection([1, 2]);
            $collection->add(3);

            expect($collection->all())->toBe([1, 2, 3]);
        });

        it('Doit pousser des éléments', function (): void {
            $collection = new Collection([1, 2]);
            $collection->push(3, 4);

            expect($collection->all())->toBe([1, 2, 3, 4]);
        });

        it('Doit prepend des éléments', function (): void {
            $collection = new Collection([2, 3]);
            $collection->prepend(1);

            expect($collection->all())->toBe([1, 2, 3]);
        });
    });

    describe('Filtrage', function (): void {
        it('Doit filtrer les éléments', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $filtered = $collection->filter(fn($item) => $item > 2);

            expect($filtered->values()->all())->toBe([3, 4, 5]);
        });

        it('Doit rejeter des éléments', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $rejected = $collection->reject(fn($item) => $item <= 2);

            expect($rejected->values()->all())->toBe([3, 4, 5]);
        });

        it('Doit vérifier contains', function (): void {
            $collection = new Collection([1, 2, 3]);
            expect($collection->contains(2))->toBe(true);
            expect($collection->contains(5))->toBe(false);
        });
    });

    describe('Transformation', function (): void {
        it('Doit mapper les éléments', function (): void {
            $collection = new Collection([1, 2, 3]);
            $mapped = $collection->map(fn($item) => $item * 2);

            expect($mapped->all())->toBe([2, 4, 6]);
        });

        it('Doit extraire avec pluck', function (): void {
            $collection = new Collection([
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25]
            ]);

            $names = $collection->pluck('name');

            expect($names->all())->toBe(['John', 'Jane']);
        });

        it('Doit grouper les éléments', function (): void {
            $collection = new Collection([
                ['category' => 'A', 'value' => 1],
                ['category' => 'B', 'value' => 2],
                ['category' => 'A', 'value' => 3]
            ]);

            $grouped = $collection->groupBy('category');

            expect($grouped->has('A'))->toBe(true);
            expect($grouped->get('A')->count())->toBe(2);
        });
    });

    describe('Tri', function (): void {
        it('Doit trier les éléments', function (): void {
            $collection = new Collection([3, 1, 2]);
            $sorted = $collection->sort();

            expect($sorted->values()->all())->toBe([1, 2, 3]);
        });

        it('Doit trier par clé', function (): void {
            $collection = new Collection(['b' => 2, 'a' => 1, 'c' => 3]);
            $sorted = $collection->sortKeys();

            expect($sorted->keys()->all())->toBe(['a', 'b', 'c']);
        });
    });

    describe('Agrégation', function (): void {
        it('Doit calculer la somme', function (): void {
            $collection = new Collection([1, 2, 3, 4]);
            expect($collection->sum())->toBe(10);
        });

        it('Doit calculer la moyenne', function (): void {
            $collection = new Collection([1, 2, 3, 4]);
            expect($collection->average())->toBe(2.5);
        });

        it('Doit trouver le max et min', function (): void {
            $collection = new Collection([1, 5, 3, 2]);
            expect($collection->max())->toBe(5);
            expect($collection->min())->toBe(1);
        });
    });
});
