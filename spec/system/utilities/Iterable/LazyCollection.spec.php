<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use BlitzPHP\Utilities\Exceptions\ItemNotFoundException;
use BlitzPHP\Utilities\Iterable\LazyCollection;
use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Utilities\Helpers;

use function Kahlan\expect;

describe('Utilities / Iterable / LazyCollection', function (): void {
    describe('Initialisation', function (): void {
        it('Doit créer une collection vide', function (): void {
            $collection = new LazyCollection();
            expect($collection->all())->toBe([]);
        });

        it('Doit créer une collection à partir d\'un tableau', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            expect($collection->all())->toBe([1, 2, 3]);
        });

        it('Doit créer une collection à partir d\'une closure qui retourne un générateur', function (): void {
            $collection = new LazyCollection(function () {
                yield 1;
                yield 2;
                yield 3;
            });
            expect($collection->all())->toBe([1, 2, 3]);
        });

        it('Doit créer une collection à partir d\'un Arrayable', function (): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array { return ['a' => 1, 'b' => 2]; }
            };
            $collection = new LazyCollection($arrayable);
            expect($collection->all())->toBe(['a' => 1, 'b' => 2]);
        });

        it('Doit créer une collection à partir d\'un autre LazyCollection', function (): void {
            $collection1 = new LazyCollection([1, 2, 3]);
            $collection2 = new LazyCollection($collection1);
            expect($collection2->all())->toBe([1, 2, 3]);
        });

        it('Doit lever une exception pour un générateur direct', function (): void {
            expect(function (): void {
                $generator = (function () { yield 1; })();
                new LazyCollection($generator);
            })->toThrow(new InvalidArgumentException());
        });
    });

    describe('Méthode make', function (): void {
        it('Doit créer une collection avec make()', function (): void {
            $collection = LazyCollection::make([1, 2, 3]);
            expect($collection->all())->toBe([1, 2, 3]);
        });

        it('Doit créer une collection vide avec make() sans paramètre', function (): void {
            $collection = LazyCollection::make();
            expect($collection->all())->toBe([]);
        });
    });

    describe('Méthode range', function (): void {
        it('Doit créer une plage ascendante', function (): void {
            $collection = LazyCollection::range(1, 5);
            expect($collection->all())->toBe([1, 2, 3, 4, 5]);
        });

        it('Doit créer une plage descendante', function (): void {
            $collection = LazyCollection::range(5, 1);
            expect($collection->all())->toBe([5, 4, 3, 2, 1]);
        });

        it('Doit créer une plage avec un pas', function (): void {
            $collection = LazyCollection::range(1, 10, 2);
            expect($collection->all())->toBe([1, 3, 5, 7, 9]);
        });

        it('Doit lever une exception pour un pas de zéro', function (): void {
            expect(function (): void {
                LazyCollection::range(1, 5, 0);
            })->toThrow(new InvalidArgumentException());
        });
    });

    describe('Méthode all', function (): void {
        it('Doit retourner tous les éléments', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            expect($collection->all())->toBe([1, 2, 3]);
        });

        it('Doit retourner tous les éléments d\'une source lazy', function (): void {
            $collection = new LazyCollection(function () {
                yield 'a';
                yield 'b';
                yield 'c';
            });
            expect($collection->all())->toBe(['a', 'b', 'c']);
        });
    });

    describe('Méthode eager', function (): void {
        it('Doit créer une collection eager', function (): void {
            $collection = new LazyCollection(function () {
                yield 1;
                yield 2;
            });
            $eager = $collection->eager();
            expect($eager->all())->toBe([1, 2]);
        });
    });

    describe('Méthode remember', function (): void {
        it('Doit mémoriser les valeurs énumérées', function (): void {
            $count = 0;
            $collection = new LazyCollection(function () use (&$count) {
                $count++;
                yield 1;
                yield 2;
                yield 3;
            });

            $remembered = $collection->remember();
            $first = $remembered->all();
            $second = $remembered->all();

            expect($count)->toBe(1); // Le générateur ne devrait être appelé qu'une fois
            expect($first)->toBe([1, 2, 3]);
            expect($second)->toBe([1, 2, 3]);
        });
    });

    describe('Méthode contains', function (): void {
        it('Doit vérifier la présence d\'une valeur', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            expect($collection->contains(2))->toBe(true);
            expect($collection->contains(4))->toBe(false);
        });

        it('Doit vérifier la présence avec un callback', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            expect($collection->contains(fn ($value): bool => $value > 2))->toBe(true);
            expect($collection->contains(fn ($value): bool => $value > 5))->toBe(false);
        });

        it('Doit vérifier la présence avec opérateur', function (): void {
            $collection = new LazyCollection([['id' => 1], ['id' => 2], ['id' => 3]]);
            expect($collection->contains('id', '=', 2))->toBe(true);
        });
    });

    describe('Méthode containsStrict', function (): void {
        it('Doit vérifier la présence stricte', function (): void {
            $collection = new LazyCollection(['1', '2', '3']);
            expect($collection->containsStrict('2'))->toBe(true);
            expect($collection->containsStrict(2))->toBe(false); // Type différent
        });
    });

    describe('Méthode doesntContain', function (): void {
        it('Doit vérifier l\'absence d\'une valeur', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            expect($collection->doesntContain(4))->toBe(true);
            expect($collection->doesntContain(2))->toBe(false);
        });
    });

    describe('Méthode countBy', function (): void {
        it('Doit compter les occurrences', function (): void {
            $collection = new LazyCollection(['a', 'b', 'a', 'c', 'b', 'b']);
            $counted = $collection->countBy();
            expect($counted->all())->toBe(['a' => 2, 'b' => 3, 'c' => 1]);
        });

        it('Doit compter avec un callback', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $counted = $collection->countBy(fn ($value): string => $value % 2 === 0 ? 'pair' : 'impair');
            expect($counted->all())->toBe(['impair' => 3, 'pair' => 2]);
        });
    });

    describe('Méthode filter', function (): void {
        it('Doit filtrer avec un callback', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $filtered = $collection->filter(fn ($value): bool => $value > 2);
            expect(array_values($filtered->all()))->toBe([3, 4, 5]);
        });

        it('Doit filtrer les valeurs falsy sans callback', function (): void {
            $collection = new LazyCollection([0, 1, false, 2, null, 3, '']);
            $filtered = $collection->filter();
            expect(array_values($filtered->all()))->toBe([1, 2, 3]);
        });
    });

    describe('Méthode first', function (): void {
        it('Doit retourner le premier élément', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            expect($collection->first())->toBe(1);
        });

        it('Doit retourner le premier élément correspondant au callback', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $result = $collection->first(fn ($value): bool => $value > 3);
            expect($result)->toBe(4);
        });

        it('Doit retourner la valeur par défaut si vide', function (): void {
            $collection = new LazyCollection([]);
            expect($collection->first(null, 'default'))->toBe('default');
        });
    });

    describe('Méthode flatten', function (): void {
        it('Doit aplatir un tableau multidimensionnel', function (): void {
            $collection = new LazyCollection([[1, 2], [3, 4], [5]]);
            $flattened = $collection->flatten();
            expect($flattened->all())->toBe([1, 2, 3, 4, 5]);
        });

        it('Doit aplatir avec une profondeur spécifique', function (): void {
            $collection = new LazyCollection([[1, [2, 3]], [4, [5, 6]]]);
            $flattened = $collection->flatten(1);
            expect($flattened->all())->toBe([1, [2, 3], 4, [5, 6]]);
        });
    });

    describe('Méthode flip', function (): void {
        it('Doit inverser les clés et valeurs', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3]);
            $flipped = $collection->flip();
            expect($flipped->all())->toBe([1 => 'a', 2 => 'b', 3 => 'c']);
        });
    });

    describe('Méthode get', function (): void {
        it('Doit récupérer une valeur par clé', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3]);
            expect($collection->get('b'))->toBe(2);
        });

        it('Doit retourner la valeur par défaut si clé non trouvée', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2]);
            expect($collection->get('c', 'default'))->toBe('default');
        });

        it('Doit retourner null pour une clé null', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2]);
            expect($collection->get(null))->toBe(null);
        });
    });

    describe('Méthode has', function (): void {
        it('Doit vérifier la présence d\'une clé', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3]);
            expect($collection->has('b'))->toBe(true);
            expect($collection->has('d'))->toBe(false);
        });

        it('Doit vérifier la présence de plusieurs clés', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3]);
            expect($collection->has(['a', 'b']))->toBe(true);
            expect($collection->has(['a', 'd']))->toBe(false);
        });
    });

    describe('Méthode hasAny', function (): void {
        it('Doit vérifier la présence d\'au moins une clé', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2]);
            expect($collection->hasAny(['a', 'c']))->toBe(true);
            expect($collection->hasAny(['c', 'd']))->toBe(false);
        });
    });

    describe('Méthode isEmpty', function (): void {
        it('Doit vérifier si la collection est vide', function (): void {
            $collection1 = new LazyCollection([]);
            $collection2 = new LazyCollection([1, 2, 3]);

            expect($collection1->isEmpty())->toBe(true);
            expect($collection2->isEmpty())->toBe(false);
        });
    });

    describe('Méthode containsOneItem', function (): void {
        it('Doit vérifier si la collection contient exactement un élément', function (): void {
            $collection1 = new LazyCollection([1]);
            $collection2 = new LazyCollection([1, 2]);
            $collection3 = new LazyCollection([]);

            expect($collection1->containsOneItem())->toBe(true);
            expect($collection2->containsOneItem())->toBe(false);
            expect($collection3->containsOneItem())->toBe(false);
        });
    });

    describe('Méthode keys', function (): void {
        it('Doit retourner les clés', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3]);
            expect($collection->keys()->all())->toBe(['a', 'b', 'c']);
        });
    });

    describe('Méthode last', function (): void {
        it('Doit retourner le dernier élément', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            expect($collection->last())->toBe(3);
        });

        it('Doit retourner le dernier élément correspondant au callback', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $result = $collection->last(fn ($value): bool => $value < 4);
            expect($result)->toBe(3);
        });

        it('Doit retourner la valeur par défaut si vide', function (): void {
            $collection = new LazyCollection([]);
            expect($collection->last(null, 'default'))->toBe('default');
        });
    });

    describe('Méthode pluck', function (): void {
        it('Doit extraire les valeurs d\'une clé', function (): void {
            $collection = new LazyCollection([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
                ['id' => 3, 'name' => 'Bob']
            ]);
            $plucked = $collection->pluck('name');
            expect($plucked->all())->toBe(['John', 'Jane', 'Bob']);
        });

        it('Doit extraire avec clé personnalisée', function (): void {
            $collection = new LazyCollection([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
                ['id' => 3, 'name' => 'Bob']
            ]);
            $plucked = $collection->pluck('name', 'id');
            expect($plucked->all())->toBe([1 => 'John', 2 => 'Jane', 3 => 'Bob']);
        });
    });

    describe('Méthode map', function (): void {
        it('Doit transformer chaque élément', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            $mapped = $collection->map(fn ($value): int => $value * 2);
            expect($mapped->all())->toBe([2, 4, 6]);
        });

        it('Doit préserver les clés', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3]);
            $mapped = $collection->map(fn ($value): int => $value * 2);
            expect($mapped->all())->toBe(['a' => 2, 'b' => 4, 'c' => 6]);
        });
    });

    describe('Méthode mapWithKeys', function (): void {
        it('Doit transformer avec nouvelles clés', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            $mapped = $collection->mapWithKeys(fn ($value): array => ["key_$value" => $value * 2]);
            expect($mapped->all())->toBe(['key_1' => 2, 'key_2' => 4, 'key_3' => 6]);
        });
    });

    describe('Méthode combine', function (): void {
        it('Doit combiner avec un autre itérable', function (): void {
            $collection = new LazyCollection(['a', 'b', 'c']);
            $combined = $collection->combine([1, 2, 3]);
            expect($combined->all())->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
        });
    });

    describe('Méthode nth', function (): void {
        it('Doit retourner chaque n-ième élément', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5, 6, 7, 8, 9]);
            $nth = $collection->nth(3);
            expect($nth->all())->toBe([1, 4, 7]);
        });

        it('Doit retourner chaque n-ième élément avec offset', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5, 6, 7, 8, 9]);
            $nth = $collection->nth(3, 1);
            expect($nth->all())->toBe([2, 5, 8]);
        });
    });

    describe('Méthode only', function (): void {
        it('Doit filtrer pour ne garder que certaines clés', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
            $only = $collection->only(['a', 'c']);
            expect($only->all())->toBe(['a' => 1, 'c' => 3]);
        });
    });

    describe('Méthode concat', function (): void {
        it('Doit concaténer avec un autre itérable', function (): void {
            $collection1 = new LazyCollection([1, 2, 3]);
            $collection2 = [4, 5, 6];
            $concatenated = $collection1->concat($collection2);
            expect($concatenated->all())->toBe([1, 2, 3, 4, 5, 6]);
        });
    });

    describe('Méthode random', function (): void {
        it('Doit retourner un élément aléatoire', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $random = $collection->random();
            expect(in_array($random, [1, 2, 3, 4, 5]))->toBe(true);
        });

        it('Doit retourner plusieurs éléments aléatoires', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $random = $collection->random(3);
            expect($random)->toBeAnInstanceOf(LazyCollection::class);
            expect($random->count())->toBe(3);
        });
    });

    describe('Méthode replace', function (): void {
        it('Doit remplacer les valeurs', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3]);
            $replaced = $collection->replace(['b' => 20, 'd' => 4]);
            expect($replaced->all())->toBe(['a' => 1, 'b' => 20, 'c' => 3, 'd' => 4]);
        });
    });

    describe('Méthode search', function (): void {
        it('Doit rechercher une valeur et retourner sa clé', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3]);
            expect($collection->search(2))->toBe('b');
            expect($collection->search(4))->toBe(false);
        });

        it('Doit rechercher avec un callback', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3]);
            $result = $collection->search(fn ($value): bool => $value > 1);
            expect($result)->toBe('b');
        });
    });

    describe('Méthodes before/after', function (): void {
        it('Doit trouver l\'élément avant', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            expect($collection->before(3))->toBe(2);
            expect($collection->before(1))->toBe(null);
        });

        it('Doit trouver l\'élément après', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            expect($collection->after(3))->toBe(4);
            expect($collection->after(5))->toBe(null);
        });
    });

    describe('Méthode sliding', function (): void {
        it('Doit créer des fenêtres glissantes', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $windows = $collection->sliding(3);
		    $expected = [
                [1, 2, 3],
                [2, 3, 4],
                [3, 4, 5]
            ];
            expect(
				array_map(fn($window): array => array_values($window->all()), $windows->all())
			)->toEqual($expected);
        });
    });

    describe('Méthode skip', function (): void {
        it('Doit sauter des éléments', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $skipped = $collection->skip(2);
            expect(array_values($skipped->all()))->toBe([3, 4, 5]);
        });
    });

    describe('Méthode skipUntil', function (): void {
        it('Doit sauter jusqu\'à une valeur', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $result = $collection->skipUntil(3);
            expect(array_values($result->all()))->toBe([3, 4, 5]);
        });

        it('Doit sauter jusqu\'à un callback', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $result = $collection->skipUntil(fn ($value): bool => $value > 2);
            expect(array_values($result->all()))->toBe([3, 4, 5]);
        });
    });

    describe('Méthode skipWhile', function (): void {
        it('Doit sauter tant qu\'une condition est vraie', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $result = $collection->skipWhile(fn ($value): bool => $value < 3);
            expect(array_values($result->all()))->toBe([3, 4, 5]);
        });
    });

    describe('Méthode slice', function (): void {
        it('Doit extraire une tranche', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $slice = $collection->slice(1, 3);
            expect(array_values($slice->all()))->toBe([2, 3, 4]);
        });

        it('Doit extraire jusqu\'à la fin sans longueur spécifiée', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $slice = $collection->slice(2);
            expect(array_values($slice->all()))->toBe([3, 4, 5]);
        });
    });

    describe('Méthode sole', function (): void {
        it('Doit retourner l\'élément unique', function (): void {
            $collection = new LazyCollection([['id' => 1, 'name' => 'John']]);
            expect($collection->sole('name', '=', 'John')['id'])->toBe(1);
        });

        it('Doit lever une exception si aucun élément', function (): void {
            expect(function (): void {
                $collection = new LazyCollection([]);
                $collection->sole();
            })->toThrow(new ItemNotFoundException());
        });

        it('Doit lever une exception si plusieurs éléments', function (): void {
            expect(function (): void {
                $collection = new LazyCollection([1, 2, 3]);
                $collection->sole();
            })->toThrow();
        });
    });

    describe('Méthode firstOrFail', function (): void {
        it('Doit retourner le premier élément', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            expect($collection->firstOrFail())->toBe(1);
        });

        it('Doit lever une exception si aucun élément', function (): void {
            expect(function (): void {
                $collection = new LazyCollection([]);
                $collection->firstOrFail();
            })->toThrow(new ItemNotFoundException());
        });
    });

    describe('Méthode chunk', function (): void {
        it('Doit diviser en morceaux', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $chunks = $collection->chunk(2);
            $expected = [
                [1, 2],
                [3, 4],
                [5]
            ];
            expect(
				array_map(fn($chunk): array => array_values($chunk->all()), $chunks->all())
			)->toBe($expected);
        });

        it('Doit retourner une collection vide pour une taille <= 0', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            $chunks = $collection->chunk(0);
            expect($chunks->all())->toBe([]);
        });
    });

    describe('Méthode split', function (): void {
        it('Doit diviser en groupes', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $split = $collection->split(3);
            expect($split->count())->toBe(3);
        });
    });

    describe('Méthode splitIn', function (): void {
        it('Doit diviser en groupes avec distribution égale', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $split = $collection->splitIn(2);
            expect($split->count())->toBe(2);
        });
    });

    describe('Méthode chunkWhile', function (): void {
        it('Doit créer des morceaux conditionnels', function (): void {
            $collection = new LazyCollection([1, 2, 3, 6, 7, 8, 12, 13, 14]);
            $chunks = $collection->chunkWhile(fn($current, $key, $chunk) => empty($chunk) || $current === Helpers::last($chunk->all()) + 1);
            $expected = [
                [1, 2, 3],
                [6, 7, 8],
                [12, 13, 14]
            ];
            expect(
				array_map(fn($chunk): array => array_values($chunk->all()), $chunks->all())
			)->toEqual($expected);
        });
    });

    describe('Méthode take', function (): void {
        it('Doit prendre les premiers éléments', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $taken = $collection->take(3);
            expect($taken->all())->toBe([1, 2, 3]);
        });

        it('Doit prendre les derniers éléments avec limite négative', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $taken = $collection->take(-2);
            expect(array_values($taken->all()))->toBe([4, 5]);
        });
    });

    describe('Méthode takeUntil', function (): void {
        it('Doit prendre jusqu\'à une valeur', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $result = $collection->takeUntil(4);
            expect($result->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode takeWhile', function (): void {
        it('Doit prendre tant qu\'une condition est vraie', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $result = $collection->takeWhile(fn ($value): bool => $value < 4);
            expect($result->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode tapEach', function (): void {
        it('Doit exécuter un callback sur chaque élément', function (): void {
            $sum = 0;
            $collection = new LazyCollection([1, 2, 3]);
            $tapped = $collection->tapEach(function ($value) use (&$sum): void {
                $sum += $value;
            });
            $tapped->all(); // Force l'énumération
            expect($sum)->toBe(6);
        });
    });

    describe('Méthode throttle', function (): void {
        it('Doit limiter le débit', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $throttled = $collection->throttle(0.1); // 0.1 seconde entre chaque élément
            expect($throttled->all())->toBe([1, 2, 3, 4, 5]);
        });
    });

    describe('Méthode unique', function (): void {
        it('Doit supprimer les doublons', function (): void {
            $collection = new LazyCollection([1, 2, 2, 3, 4, 4, 5]);
            $unique = $collection->unique();
            expect(array_values($unique->all()))->toBe([1, 2, 3, 4, 5]);
        });

        it('Doit supprimer les doublons avec un callback', function (): void {
            $collection = new LazyCollection([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
                ['id' => 1, 'name' => 'Johnny']
            ]);
            $unique = $collection->unique('id');
            expect($unique->count())->toBe(2);
        });
    });

    describe('Méthode values', function (): void {
        it('Doit réinitialiser les clés', function (): void {
            $collection = new LazyCollection(['a' => 1, 'b' => 2, 'c' => 3]);
            $values = $collection->values();
            expect($values->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode zip', function (): void {
        it('Doit fusionner avec d\'autres itérables', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            $zipped = $collection->zip(['a', 'b', 'c'], ['x', 'y', 'z']);
            $expected = [
                [1, 'a', 'x'],
                [2, 'b', 'y'],
                [3, 'c', 'z']
            ];
            expect(
				array_map(fn($item): array => $item->all(), $zipped->all())
			)->toEqual($expected);
        });
    });

    describe('Méthode pad', function (): void {
        it('Doit compléter la collection', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            $padded = $collection->pad(5, 0);
            expect($padded->all())->toBe([1, 2, 3, 0, 0]);
        });

        it('Doit compléter avec une taille négative', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            $padded = $collection->pad(-5, 0);
            expect($padded->count())->toBe(5);
        });
    });

    describe('Méthode getIterator', function (): void {
        it('Doit retourner un itérateur', function (): void {
            $collection = new LazyCollection([1, 2, 3]);
            $iterator = $collection->getIterator();
            expect($iterator)->toBeAnInstanceOf(Traversable::class);
        });
    });

    describe('Méthode count', function (): void {
        it('Doit compter les éléments', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            expect($collection->count())->toBe(5);
        });

        it('Doit retourner 0 pour une collection vide', function (): void {
            $collection = new LazyCollection([]);
            expect($collection->count())->toBe(0);
        });

        it('Doit compter les éléments d\'une source lazy', function (): void {
            $collection = new LazyCollection(function () {
                yield 1;
                yield 2;
                yield 3;
            });
            expect($collection->count())->toBe(3);
        });
    });

    describe('Méthode implode', function (): void {
        it('Doit imploser les valeurs', function (): void {
            $collection = new LazyCollection(['a', 'b', 'c']);
            expect($collection->implode(','))->toBe('a,b,c');
        });

        it('Doit imploser avec clé/valeur', function (): void {
            $collection = new LazyCollection([['name' => 'John'], ['name' => 'Jane']]);
            expect($collection->implode('name', ', '))->toBe('John, Jane');
        });
    });

    describe('Méthode collapse', function (): void {
        it('Doit réduire les tableaux imbriqués', function (): void {
            $collection = new LazyCollection([[1, 2], [3, 4], [5]]);
            $collapsed = $collection->collapse();
            expect($collapsed->all())->toBe([1, 2, 3, 4, 5]);
        });

        it('Doit réduire avec des collections', function (): void {
            $inner = new LazyCollection([1, 2]);
            $collection = new LazyCollection([$inner, [3, 4]]);
            $collapsed = $collection->collapse();
            expect($collapsed->all())->toBe([1, 2, 3, 4]);
        });
    });

    describe('Méthode collapseWithKeys', function (): void {
        it('Doit réduire en préservant les clés', function (): void {
            $collection = new LazyCollection([
                ['a' => 1, 'b' => 2],
                ['c' => 3, 'd' => 4]
            ]);
            $collapsed = $collection->collapseWithKeys();
            expect($collapsed->all())->toBe(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        });
    });

    describe('Méthode keyBy', function (): void {
        it('Doit réindexer par une clé', function (): void {
            $collection = new LazyCollection([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ]);
            $keyed = $collection->keyBy('id');
            expect($keyed->all())->toBe([
                1 => ['id' => 1, 'name' => 'John'],
                2 => ['id' => 2, 'name' => 'Jane']
            ]);
        });

        it('Doit réindexer avec un callback', function (): void {
            $collection = new LazyCollection([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ]);
            $keyed = $collection->keyBy(fn ($item): string => strtoupper($item['name']));
            expect($keyed->all())->toBe([
                'JOHN' => ['id' => 1, 'name' => 'John'],
                'JANE' => ['id' => 2, 'name' => 'Jane']
            ]);
        });
    });

    describe('Méthode select', function (): void {
        it('Doit sélectionner des clés spécifiques', function (): void {
            $collection = new LazyCollection([
                ['id' => 1, 'name' => 'John', 'age' => 30],
                ['id' => 2, 'name' => 'Jane', 'age' => 25]
            ]);
            $selected = $collection->select(['id', 'name']);
            expect($selected->all())->toBe([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ]);
        });
    });

    describe('Méthode reverse', function (): void {
        it('Doit inverser l\'ordre', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $reversed = $collection->reverse();
            expect($reversed->values()->all())->toBe([5, 4, 3, 2, 1]);
        });
    });

    describe('Méthode shuffle', function (): void {
        it('Doit mélanger les éléments', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $shuffled = $collection->shuffle();
            expect($shuffled->count())->toBe(5);
        });

        it('Doit mélanger avec une graine', function (): void {
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $shuffled1 = $collection->shuffle(123);
            $shuffled2 = $collection->shuffle(123);
            expect($shuffled1->all())->toBe($shuffled2->all());
        });
    });

    describe('Méthode dot', function (): void {
        it('Doit aplatir avec notation point', function (): void {
            $collection = new LazyCollection([
                'user' => ['name' => 'John', 'age' => 30],
                'settings' => ['theme' => 'dark']
            ]);
            $dotted = $collection->dot();
            expect($dotted->all())->toContainKey('user.name');
            expect($dotted->all())->toContainKey('user.age');
            expect($dotted->all())->toContainKey('settings.theme');
        });
    });

    describe('Méthode undot', function (): void {
        it('Doit reconstruire depuis la notation point', function (): void {
            $collection = new LazyCollection([
                'user.name' => 'John',
                'user.age' => 30,
                'settings.theme' => 'dark'
            ]);
            $undotted = $collection->undot();
            expect($undotted->all())->toBe([
                'user' => ['name' => 'John', 'age' => 30],
                'settings' => ['theme' => 'dark']
            ]);
        });
    });

    describe('Méthode withHeartbeat', function (): void {
        xit('Doit exécuter un callback à intervalle régulier', function (): void {
            $callCount = 0;
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $withHeartbeat = $collection->withHeartbeat(1, function () use (&$callCount): void {
                $callCount++;
            });
            $withHeartbeat->all();
            expect($callCount )->toBeGreaterThan(0);
        });

        it('Doit accepter un DateInterval', function (): void {
            $interval = new DateInterval('PT1S');
            $collection = new LazyCollection([1, 2, 3]);
            $withHeartbeat = $collection->withHeartbeat($interval, function (): void {});
            expect($withHeartbeat->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode takeUntilTimeout', function (): void {
        it('Doit prendre jusqu\'à un timeout', function (): void {
            $timeout = new DateTimeImmutable('+1 second');
            $collection = new LazyCollection([1, 2, 3, 4, 5]);
            $result = $collection->takeUntilTimeout($timeout);
            expect($result->count())->toBeGreaterThan(0);
        });
    });
});
