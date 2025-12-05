<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\Iterable\Arr;

use function Kahlan\expect;

describe('Utilities / Iterable / Arr', function (): void {
    describe('Accès aux données', function (): void {
        it('Doit récupérer avec notation point', function (): void {
            $array = ['user' => ['name' => 'John', 'age' => 30]];
            expect(Arr::get($array, 'user.name'))->toBe('John');
        });

        it('Doit retourner la valeur par défaut si clé inexistante', function (): void {
            $array = ['user' => ['name' => 'John']];
            expect(Arr::get($array, 'user.age', 25))->toBe(25);
        });

        it('Doit vérifier l\'existence avec has()', function (): void {
            $array = ['user' => ['name' => 'John']];
            expect(Arr::has($array, 'user.name'))->toBe(true);
            expect(Arr::has($array, 'user.age'))->toBe(false);
        });
    });

    describe('Manipulation', function (): void {
        it('Doit définir avec notation point', function (): void {
            $array = [];
            Arr::set($array, 'user.name', 'John');
            expect($array)->toBe(['user' => ['name' => 'John']]);
        });

        it('Doit ajouter si non existant', function (): void {
            $array = ['user' => ['name' => 'John']];
            $array = Arr::add($array, 'user.age', 30);
            $array = Arr::add($array, 'user.name', 'Jane'); // Ne devrait pas écraser

            expect($array['user']['age'])->toBe(30);
            expect($array['user']['name'])->toBe('John');
        });

        it('Doit oublier avec notation point', function (): void {
            $array = ['user' => ['name' => 'John', 'age' => 30]];
            Arr::forget($array, 'user.age');

            expect($array)->toBe(['user' => ['name' => 'John']]);
        });
    });

    describe('Extraction et Filtrage', function (): void {
        it('Doit extraire avec pluck', function (): void {
            $array = [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25]
            ];

            $names = Arr::pluck($array, 'name');
            expect($names)->toBe(['John', 'Jane']);
        });

        it('Doit filtrer avec where', function (): void {
            $array = [
                ['name' => 'John', 'active' => true],
                ['name' => 'Jane', 'active' => false],
                ['name' => 'Bob', 'active' => true]
            ];

            $active = Arr::where($array, fn($item) => $item['active']);
            expect($active)->toHaveLength(2);
        });

        it('Doit filtrer les valeurs non nulles', function (): void {
            $array = [1, null, 2, '', 3, false];
            $filtered = Arr::whereNotNull($array);

            expect(array_values($filtered))->toBe([1, 2, '', 3, false]);
        });
    });

    describe('Transformation', function (): void {
        it('Doit aplatir avec dot notation', function (): void {
            $array = ['user' => ['name' => 'John', 'prefs' => ['theme' => 'dark']]];
            $flattened = Arr::dot($array);

            expect($flattened)->toBe([
                'user.name' => 'John',
                'user.prefs.theme' => 'dark'
            ]);
        });

        it('Doit reconstruire depuis dot notation', function (): void {
            $array = [
                'user.name' => 'John',
                'user.prefs.theme' => 'dark'
            ];

            $expanded = Arr::undot($array);
            expect($expanded)->toBe(['user' => ['name' => 'John', 'prefs' => ['theme' => 'dark']]]);
        });

        it('Doit mapper avec les clés', function (): void {
            $array = ['a' => 1, 'b' => 2, 'c' => 3];
            $mapped = Arr::map($array, fn($value, $key) => $key . ':' . $value);

            expect($mapped)->toBe(['a' => 'a:1', 'b' => 'b:2', 'c' => 'c:3']);
        });
    });

    describe('Validation et Vérification', function (): void {
        it('Doit identifier un tableau associatif', function (): void {
            expect(Arr::isAssoc(['a' => 1, 'b' => 2]))->toBe(true);
            expect(Arr::isAssoc([1, 2, 3]))->toBe(false);
        });

        it('Doit identifier un tableau liste', function (): void {
            expect(Arr::isList([1, 2, 3]))->toBe(true);
            expect(Arr::isList(['a' => 1, 'b' => 2]))->toBe(false);
        });

        it('Doit vérifier si accessible', function (): void {
            expect(Arr::accessible([]))->toBe(true);
            expect(Arr::accessible(new ArrayObject()))->toBe(true);
            expect(Arr::accessible('string'))->toBe(false);
        });
    });

    describe('Tri', function (): void {
        it('Doit trier récursivement', function (): void {
            $array = ['c' => 3, 'a' => ['z' => 2, 'x' => 1], 'b' => 2];
            $sorted = Arr::sortRecursive($array);

            expect(array_keys($sorted))->toBe(['a', 'b', 'c']);
            expect(array_keys($sorted['a']))->toBe(['x', 'z']);
        });
    });
});
