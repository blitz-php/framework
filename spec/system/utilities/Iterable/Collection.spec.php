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
use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Utilities\Exceptions\ItemNotFoundException;
use BlitzPHP\Utilities\Helpers;

use function Kahlan\expect;

describe('Utilities / Iterable / Collection', function (): void {
    describe('Initialisation', function (): void {
        it('Doit créer une collection vide', function (): void {
            $collection = new Collection();
            expect($collection->all())->toBe([]);
        });

        it('Doit créer une collection à partir d\'un tableau', function (): void {
            $collection = new Collection([1, 2, 3]);
            expect($collection->all())->toBe([1, 2, 3]);
        });

        it('Doit créer une collection à partir d\'un Arrayable', function (): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array { return ['a' => 1]; }
            };
            $collection = new Collection($arrayable);
            expect($collection->all())->toBe(['a' => 1]);
        });

        it('Doit créer une collection à partir d\'un itérable', function (): void {
            $iterator = new ArrayObject(['a' => 1, 'b' => 2]);
            $collection = new Collection($iterator);
            expect($collection->all())->toBe(['a' => 1, 'b' => 2]);
        });
    });

    describe('Méthode range', function (): void {
        it('Doit créer une plage', function (): void {
            $collection = Collection::range(1, 5);
            expect($collection->all())->toBe([1, 2, 3, 4, 5]);
        });

        it('Doit créer une plage avec un pas', function (): void {
            $collection = Collection::range(1, 10, 2);
            expect($collection->all())->toBe([1, 3, 5, 7, 9]);
        });
    });

    describe('Méthode all', function (): void {
        it('Doit retourner tous les éléments', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            expect($collection->all())->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
        });
    });

    describe('Méthode lazy', function (): void {
        it('Doit créer une LazyCollection', function (): void {
            $collection = new Collection([1, 2, 3]);
            $lazy = $collection->lazy();
            expect($lazy->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode median', function (): void {
        it('Doit calculer la médiane', function (): void {
            $collection = new Collection([1, 3, 3, 6, 7, 8, 9]);
            expect($collection->median())->toBe(6);

            $collection = new Collection([1, 2, 3, 4]);
            expect($collection->median())->toBe(2.5);
        });

        it('Doit calculer la médiane d\'une clé', function (): void {
            $collection = new Collection([
                ['age' => 30],
                ['age' => 25],
                ['age' => 35]
            ]);
            expect($collection->median('age'))->toBe(30);
        });
    });

    describe('Méthode mode', function (): void {
        it('Doit calculer le mode', function (): void {
            $collection = new Collection([1, 2, 2, 3, 3, 3, 4]);
            expect($collection->mode())->toBe([3]);

            $collection = new Collection([1, 1, 2, 2]);
            $result = $collection->mode();
            expect(in_array(1, $result))->toBe(true);
            expect(in_array(2, $result))->toBe(true);
        });

        it('Doit retourner null pour une collection vide', function (): void {
            $collection = new Collection([]);
            expect($collection->mode())->toBe(null);
        });
    });

    describe('Méthode collapse', function (): void {
        it('Doit réduire les tableaux imbriqués', function (): void {
            $collection = new Collection([[1, 2], [3, 4], [5]]);
            $collapsed = $collection->collapse();
            expect($collapsed->all())->toBe([1, 2, 3, 4, 5]);
        });
    });

    describe('Méthode collapseWithKeys', function (): void {
        it('Doit réduire en préservant les clés', function (): void {
            $collection = new Collection([
                ['a' => 1, 'b' => 2],
                ['c' => 3, 'd' => 4]
            ]);
            $collapsed = $collection->collapseWithKeys();
            expect($collapsed->all())->toBe(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        });
    });

    describe('Méthode contains', function (): void {
        it('Doit vérifier la présence d\'une valeur', function (): void {
            $collection = new Collection([1, 2, 3]);
            expect($collection->contains(2))->toBe(true);
            expect($collection->contains(4))->toBe(false);
        });

        it('Doit vérifier avec un callback', function (): void {
            $collection = new Collection([1, 2, 3]);
            expect($collection->contains(fn ($value) => $value > 2))->toBe(true);
        });
    });

    describe('Méthode containsStrict', function (): void {
        it('Doit vérifier la présence stricte', function (): void {
            $collection = new Collection(['1', '2', '3']);
            expect($collection->containsStrict('2'))->toBe(true);
            expect($collection->containsStrict(2))->toBe(false);
        });
    });

    describe('Méthode doesntContain', function (): void {
        it('Doit vérifier l\'absence d\'une valeur', function (): void {
            $collection = new Collection([1, 2, 3]);
            expect($collection->doesntContain(4))->toBe(true);
            expect($collection->doesntContain(2))->toBe(false);
        });
    });

    describe('Méthode crossJoin', function (): void {
        it('Doit créer un produit cartésien', function (): void {
            $collection = new Collection([1, 2]);
            $result = $collection->crossJoin(['a', 'b']);
            $expected = [
                [1, 'a'],
                [1, 'b'],
                [2, 'a'],
                [2, 'b']
            ];
            expect($result->all())->toBe($expected);
        });
    });

    describe('Méthode diff', function (): void {
        it('Doit calculer la différence', function (): void {
            $collection1 = new Collection([1, 2, 3, 4, 5]);
            $collection2 = [2, 4, 6];
            $result = $collection1->diff($collection2);
            expect(array_values($result->all()))->toBe([1, 3, 5]);
        });
    });

    describe('Méthode diffUsing', function (): void {
        it('Doit calculer la différence avec callback', function (): void {
            $collection1 = new Collection(['a', 'B', 'c']);
            $collection2 = ['A', 'b'];
            $result = $collection1->diffUsing($collection2, 'strcasecmp');
            expect(array_values($result->all()))->toBe(['c']);
        });
    });

    describe('Méthode diffAssoc', function (): void {
        it('Doit calculer la différence associative', function (): void {
            $collection1 = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            $collection2 = ['a' => 1, 'd' => 4];
            $result = $collection1->diffAssoc($collection2);
            expect($result->all())->toBe(['b' => 2, 'c' => 3]);
        });
    });

    describe('Méthode diffAssocUsing', function (): void {
        it('Doit calculer la différence associative avec callback', function (): void {
            $collection1 = new Collection(['a' => 1, 'b' => 2]);
            $collection2 = ['A' => 1];
            $result = $collection1->diffAssocUsing($collection2, 'strcasecmp');
            expect($result->all())->toBe(['b' => 2]);
        });
    });

    describe('Méthode diffKeys', function (): void {
        it('Doit calculer la différence de clés', function (): void {
            $collection1 = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            $collection2 = ['a' => 10, 'd' => 40];
            $result = $collection1->diffKeys($collection2);
            expect($result->all())->toBe(['b' => 2, 'c' => 3]);
        });
    });

    describe('Méthode diffKeysUsing', function (): void {
        it('Doit calculer la différence de clés avec callback', function (): void {
            $collection1 = new Collection(['a' => 1, 'b' => 2]);
            $collection2 = ['A' => 10];
            $result = $collection1->diffKeysUsing($collection2, 'strcasecmp');
            expect($result->all())->toBe(['b' => 2]);
        });
    });

    describe('Méthode duplicates', function (): void {
        it('Doit trouver les doublons', function (): void {
            $collection = new Collection([1, 2, 1, 3, 2, 4]);
            $duplicates = $collection->duplicates();
            expect($duplicates->all())->toBe([2 => 1, 4 => 2]);
        });
    });

    describe('Méthode duplicatesStrict', function (): void {
        it('Doit trouver les doublons stricts', function (): void {
            $collection = new Collection(['1', 1, '2', 2]);
            $duplicates = $collection->duplicatesStrict();
            expect($duplicates->all())->toBe([]);
        });
    });

    describe('Méthode except', function (): void {
        it('Doit exclure des clés', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            $result = $collection->except('b');
            expect($result->all())->toBe(['a' => 1, 'c' => 3]);
        });
    });

    describe('Méthode filter', function (): void {
        it('Doit filtrer avec un callback', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $filtered = $collection->filter(fn ($value) => $value > 2);
            expect(array_values($filtered->all()))->toBe([3, 4, 5]);
        });

        it('Doit filtrer les valeurs falsy sans callback', function (): void {
            $collection = new Collection([0, 1, false, 2, null, 3, '']);
            $filtered = $collection->filter();
            expect($filtered->values()->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode first', function (): void {
        it('Doit retourner le premier élément', function (): void {
            $collection = new Collection([1, 2, 3]);
            expect($collection->first())->toBe(1);
        });

        it('Doit retourner le premier élément correspondant au callback', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $result = $collection->first(fn ($value) => $value > 3);
            expect($result)->toBe(4);
        });

        it('Doit retourner la valeur par défaut si vide', function (): void {
            $collection = new Collection([]);
            expect($collection->first(null, 'default'))->toBe('default');
        });
    });

    describe('Méthode flatten', function (): void {
        it('Doit aplatir un tableau', function (): void {
            $collection = new Collection([[1, 2], [3, 4], [5]]);
            $flattened = $collection->flatten();
            expect($flattened->all())->toBe([1, 2, 3, 4, 5]);
        });

        it('Doit aplatir avec profondeur limitée', function (): void {
            $collection = new Collection([[1, [2, 3]], [4, [5, 6]]]);
            $flattened = $collection->flatten(1);
            expect($flattened->all())->toBe([1, [2, 3], 4, [5, 6]]);
        });
    });

    describe('Méthode flip', function (): void {
        it('Doit inverser les clés et valeurs', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            $flipped = $collection->flip();
            expect($flipped->all())->toBe([1 => 'a', 2 => 'b', 3 => 'c']);
        });
    });

    describe('Méthode forget', function (): void {
        it('Doit supprimer une clé', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2]);
            $collection->forget('a');
            expect($collection->all())->toBe(['b' => 2]);
        });

        it('Doit supprimer plusieurs clés', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            $collection->forget(['a', 'c']);
            expect($collection->all())->toBe(['b' => 2]);
        });
    });

    describe('Méthode get', function (): void {
        it('Doit récupérer une valeur par clé', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2]);
            expect($collection->get('b'))->toBe(2);
        });

        it('Doit retourner la valeur par défaut si non trouvé', function (): void {
            $collection = new Collection(['a' => 1]);
            expect($collection->get('b', 'default'))->toBe('default');
        });

        it('Doit retourner null pour une clé null', function (): void {
            $collection = new Collection(['a' => 1]);
            expect($collection->get(null))->toBe(null);
        });
    });

    describe('Méthode getOrPut', function (): void {
        it('Doit récupérer une valeur existante', function (): void {
            $collection = new Collection(['a' => 1]);
            expect($collection->getOrPut('a', 2))->toBe(1);
        });

        it('Doit ajouter et retourner une nouvelle valeur', function (): void {
            $collection = new Collection(['a' => 1]);
            $value = $collection->getOrPut('b', 2);
            expect($value)->toBe(2);
            expect($collection->get('b'))->toBe(2);
        });

        it('Doit exécuter un callback pour la valeur', function (): void {
            $collection = new Collection(['a' => 1]);
            $value = $collection->getOrPut('b', fn () => 3);
            expect($value)->toBe(3);
            expect($collection->get('b'))->toBe(3);
        });
    });

    describe('Méthode groupBy', function (): void {
        it('Doit grouper par une clé', function (): void {
            $collection = new Collection([
                ['department' => 'IT', 'name' => 'John'],
                ['department' => 'HR', 'name' => 'Jane'],
                ['department' => 'IT', 'name' => 'Bob']
            ]);
            $grouped = $collection->groupBy('department');
            expect($grouped->has('IT'))->toBe(true);
            expect($grouped->get('IT')->count())->toBe(2);
        });

        it('Doit grouper par un callback', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $grouped = $collection->groupBy(fn ($value) => $value % 2 == 0 ? 'pair' : 'impair');
            expect($grouped->has('pair'))->toBe(true);
            expect($grouped->get('impair')->count())->toBe(3);
        });
    });

    describe('Méthode keyBy', function (): void {
        it('Doit indexer par une clé', function (): void {
            $collection = new Collection([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ]);
            $keyed = $collection->keyBy('id');
            expect($keyed->get(1)['name'])->toBe('John');
            expect($keyed->get(2)['name'])->toBe('Jane');
        });
    });

    describe('Méthode has', function (): void {
        it('Doit vérifier la présence d\'une clé', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2]);
            expect($collection->has('b'))->toBe(true);
            expect($collection->has('c'))->toBe(false);
        });

        it('Doit vérifier la présence de plusieurs clés', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            expect($collection->has(['a', 'b']))->toBe(true);
            expect($collection->has(['a', 'd']))->toBe(false);
        });
    });

    describe('Méthode hasAny', function (): void {
        it('Doit vérifier la présence d\'au moins une clé', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2]);
            expect($collection->hasAny(['a', 'c']))->toBe(true);
            expect($collection->hasAny(['c', 'd']))->toBe(false);
        });
    });

    describe('Méthode implode', function (): void {
        it('Doit imploser les valeurs', function (): void {
            $collection = new Collection(['a', 'b', 'c']);
            expect($collection->implode(','))->toBe('a,b,c');
        });

        it('Doit imploser avec clé/valeur', function (): void {
            $collection = new Collection([
                ['name' => 'John'],
                ['name' => 'Jane']
            ]);
            expect($collection->implode('name', ', '))->toBe('John, Jane');
        });
    });

    describe('Méthode intersect', function (): void {
        it('Doit calculer l\'intersection', function (): void {
            $collection1 = new Collection([1, 2, 3, 4]);
            $collection2 = [2, 4, 6];
            $result = $collection1->intersect($collection2);
            expect($result->values()->all())->toBe([2, 4]);
        });
    });

    describe('Méthode intersectUsing', function (): void {
        it('Doit calculer l\'intersection avec callback', function (): void {
            $collection1 = new Collection(['a', 'B', 'c']);
            $collection2 = ['A', 'b', 'C'];
            $result = $collection1->intersectUsing($collection2, 'strcasecmp');
            expect($result->all())->toBe(['a', 'B', 'c']);
        });
    });

    describe('Méthode intersectAssoc', function (): void {
        it('Doit calculer l\'intersection associative', function (): void {
            $collection1 = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            $collection2 = ['a' => 1, 'd' => 4];
            $result = $collection1->intersectAssoc($collection2);
            expect($result->all())->toBe(['a' => 1]);
        });
    });

    describe('Méthode intersectAssocUsing', function (): void {
        it('Doit calculer l\'intersection associative avec callback', function (): void {
            $collection1 = new Collection(['a' => 1, 'b' => 2]);
            $collection2 = ['A' => 1];
            $result = $collection1->intersectAssocUsing($collection2, 'strcasecmp');
            expect($result->all())->toBe(['a' => 1]);
        });
    });

    describe('Méthode intersectByKeys', function (): void {
        it('Doit calculer l\'intersection par clés', function (): void {
            $collection1 = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            $collection2 = ['a' => 10, 'd' => 40];
            $result = $collection1->intersectByKeys($collection2);
            expect($result->all())->toBe(['a' => 1]);
        });
    });

    describe('Méthode isEmpty', function (): void {
        it('Doit vérifier si la collection est vide', function (): void {
            $collection1 = new Collection([]);
            $collection2 = new Collection([1]);
            expect($collection1->isEmpty())->toBe(true);
            expect($collection2->isEmpty())->toBe(false);
        });
    });

    describe('Méthode containsOneItem', function (): void {
        it('Doit vérifier si la collection contient exactement un élément', function (): void {
            $collection1 = new Collection([1]);
            $collection2 = new Collection([1, 2]);
            $collection3 = new Collection([]);
            expect($collection1->containsOneItem())->toBe(true);
            expect($collection2->containsOneItem())->toBe(false);
            expect($collection3->containsOneItem())->toBe(false);
        });

        it('Doit vérifier avec un callback', function (): void {
            $collection = new Collection([1, 2, 3]);
            expect($collection->containsOneItem(fn ($value) => $value > 2))->toBe(true);
            expect($collection->containsOneItem(fn ($value) => $value > 1))->toBe(false);
        });
    });

    describe('Méthode join', function (): void {
        it('Doit joindre avec une colle finale', function (): void {
            $collection = new Collection(['a', 'b', 'c']);
            expect($collection->join(', ', ' and '))->toBe('a, b and c');
        });
    });

    describe('Méthode keys', function (): void {
        it('Doit retourner les clés', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            expect($collection->keys()->all())->toBe(['a', 'b', 'c']);
        });
    });

    describe('Méthode last', function (): void {
        it('Doit retourner le dernier élément', function (): void {
            $collection = new Collection([1, 2, 3]);
            expect($collection->last())->toBe(3);
        });

        it('Doit retourner le dernier élément correspondant au callback', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $result = $collection->last(fn ($value) => $value < 4);
            expect($result)->toBe(3);
        });
    });

    describe('Méthode pluck', function (): void {
        it('Doit extraire des valeurs', function (): void {
            $collection = new Collection([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ]);
            $plucked = $collection->pluck('name');
            expect($plucked->all())->toBe(['John', 'Jane']);
        });
    });

    describe('Méthode map', function (): void {
        it('Doit transformer chaque élément', function (): void {
            $collection = new Collection([1, 2, 3]);
            $mapped = $collection->map(fn ($value) => $value * 2);
            expect($mapped->all())->toBe([2, 4, 6]);
        });
    });

    describe('Méthode mapToDictionary', function (): void {
        it('Doit mapper vers un dictionnaire', function (): void {
            $collection = new Collection([1, 2, 3, 4]);
            $result = $collection->mapToDictionary(fn ($value) => [
                $value % 2 == 0 ? 'pair' : 'impair' => $value
            ]);
            expect($result->has('pair'))->toBe(true);
            expect($result->has('impair'))->toBe(true);
        });
    });

    describe('Méthode mapWithKeys', function (): void {
        it('Doit mapper avec nouvelles clés', function (): void {
            $collection = new Collection([1, 2, 3]);
            $mapped = $collection->mapWithKeys(fn ($value) => ["key_$value" => $value * 2]);
            expect($mapped->all())->toBe(['key_1' => 2, 'key_2' => 4, 'key_3' => 6]);
        });
    });

    describe('Méthode merge', function (): void {
        it('Doit fusionner des tableaux', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2]);
            $merged = $collection->merge(['b' => 3, 'c' => 4]);
            expect($merged->all())->toBe(['a' => 1, 'b' => 3, 'c' => 4]);
        });
    });

    describe('Méthode mergeRecursive', function (): void {
        it('Doit fusionner récursivement', function (): void {
            $collection = new Collection(['user' => ['name' => 'John']]);
            $merged = $collection->mergeRecursive(['user' => ['age' => 30]]);
            expect($merged->all())->toBe(['user' => ['name' => 'John', 'age' => 30]]);
        });
    });

    describe('Méthode multiply', function (): void {
        it('Doit multiplier les éléments', function (): void {
            $collection = new Collection([1, 2]);
            $multiplied = $collection->multiply(3);
            expect($multiplied->all())->toBe([1, 2, 1, 2, 1, 2]);
        });
    });

    describe('Méthode combine', function (): void {
        it('Doit combiner avec des valeurs', function (): void {
            $collection = new Collection(['a', 'b', 'c']);
            $combined = $collection->combine([1, 2, 3]);
            expect($combined->all())->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
        });
    });

    describe('Méthode union', function (): void {
        it('Doit faire une union', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2]);
            $union = $collection->union(['b' => 3, 'c' => 4]);
            expect($union->all())->toBe(['a' => 1, 'b' => 2, 'c' => 4]);
        });
    });

    describe('Méthode nth', function (): void {
        it('Doit retourner chaque n-ième élément', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5, 6, 7, 8, 9]);
            $nth = $collection->nth(3);
            expect($nth->all())->toBe([1, 4, 7]);
        });
    });

    describe('Méthode only', function (): void {
        it('Doit garder seulement certaines clés', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            $only = $collection->only(['a', 'c']);
            expect($only->all())->toBe(['a' => 1, 'c' => 3]);
        });
    });

    describe('Méthode select', function (): void {
        it('Doit sélectionner des clés spécifiques', function (): void {
            $collection = new Collection([
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

    describe('Méthode pop', function (): void {
        it('Doit dépiler un élément', function (): void {
            $collection = new Collection([1, 2, 3]);
            $popped = $collection->pop();
            expect($popped)->toBe(3);
            expect($collection->all())->toBe([1, 2]);
        });

        it('Doit dépiler plusieurs éléments', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $popped = $collection->pop(3);
            expect($popped->all())->toBe([5, 4, 3]);
            expect($collection->all())->toBe([1, 2]);
        });
    });

    describe('Méthode prepend', function (): void {
        it('Doit ajouter au début', function (): void {
            $collection = new Collection([2, 3]);
            $collection->prepend(1);
            expect($collection->all())->toBe([1, 2, 3]);
        });

        it('Doit ajouter au début avec clé', function (): void {
            $collection = new Collection(['b' => 2]);
            $collection->prepend(1, 'a');
            expect($collection->all())->toBe(['a' => 1, 'b' => 2]);
        });
    });

    describe('Méthode push', function (): void {
        it('Doit ajouter à la fin', function (): void {
            $collection = new Collection([1, 2]);
            $collection->push(3);
            expect($collection->all())->toBe([1, 2, 3]);
        });

        it('Doit ajouter plusieurs éléments', function (): void {
            $collection = new Collection([1]);
            $collection->push(2, 3, 4);
            expect($collection->all())->toBe([1, 2, 3, 4]);
        });
    });

    describe('Méthode unshift', function (): void {
        it('Doit ajouter au début', function (): void {
            $collection = new Collection([3, 4]);
            $collection->unshift(1, 2);
            expect($collection->all())->toBe([1, 2, 3, 4]);
        });
    });

    describe('Méthode concat', function (): void {
        it('Doit concaténer avec un itérable', function (): void {
            $collection = new Collection([1, 2]);
            $concatenated = $collection->concat([3, 4]);
            expect($concatenated->all())->toBe([1, 2, 3, 4]);
        });
    });

    describe('Méthode pull', function (): void {
        it('Doit extraire et supprimer une valeur', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2]);
            $value = $collection->pull('b');
            expect($value)->toBe(2);
            expect($collection->all())->toBe(['a' => 1]);
        });
    });

    describe('Méthode put', function (): void {
        it('Doit ajouter ou mettre à jour une valeur', function (): void {
            $collection = new Collection(['a' => 1]);
            $collection->put('b', 2);
            expect($collection->all())->toBe(['a' => 1, 'b' => 2]);
        });
    });

    describe('Méthode random', function (): void {
        it('Doit retourner un élément aléatoire', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $random = $collection->random();
            expect(in_array($random, [1, 2, 3, 4, 5]))->toBe(true);
        });
    });

    describe('Méthode replace', function (): void {
        it('Doit remplacer des valeurs', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2]);
            $replaced = $collection->replace(['b' => 20, 'c' => 30]);
            expect($replaced->all())->toBe(['a' => 1, 'b' => 20, 'c' => 30]);
        });
    });

    describe('Méthode replaceRecursive', function (): void {
        it('Doit remplacer récursivement', function (): void {
            $collection = new Collection(['user' => ['name' => 'John', 'age' => 30]]);
            $replaced = $collection->replaceRecursive(['user' => ['age' => 31]]);
            expect($replaced->all())->toBe(['user' => ['name' => 'John', 'age' => 31]]);
        });
    });

    describe('Méthode reverse', function (): void {
        it('Doit inverser l\'ordre', function (): void {
            $collection = new Collection([1, 2, 3]);
            $reversed = $collection->reverse();
            expect($reversed->values()->all())->toBe([3, 2, 1]);
        });
    });

    describe('Méthode search', function (): void {
        it('Doit rechercher une valeur', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            expect($collection->search(2))->toBe('b');
        });

        it('Doit rechercher avec un callback', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            $key = $collection->search(fn ($value) => $value > 1);
            expect($key)->toBe('b');
        });
    });

    describe('Méthodes before/after', function (): void {
        it('Doit trouver l\'élément avant', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            expect($collection->before(3))->toBe(2);
        });

        it('Doit trouver l\'élément après', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            expect($collection->after(3))->toBe(4);
        });
    });

    describe('Méthode shift', function (): void {
        it('Doit décaler un élément', function (): void {
            $collection = new Collection([1, 2, 3]);
            $shifted = $collection->shift();
            expect($shifted)->toBe(1);
            expect($collection->all())->toBe([2, 3]);
        });

        it('Doit lever une exception pour un nombre négatif', function (): void {
            expect(function () {
                $collection = new Collection([1, 2, 3]);
                $collection->shift(-1);
            })->toThrow(new InvalidArgumentException());
        });
    });

    describe('Méthode shuffle', function (): void {
        it('Doit mélanger les éléments', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $shuffled = $collection->shuffle();
            expect($shuffled->count())->toBe(5);
        });
    });

    describe('Méthode sliding', function (): void {
        it('Doit créer des fenêtres glissantes', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $windows = $collection->sliding(3);
            expect($windows->count())->toBe(3);
        });
    });

    describe('Méthode skip', function (): void {
        it('Doit sauter des éléments', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $skipped = $collection->skip(2);
            expect($skipped->values()->all())->toBe([3, 4, 5]);
        });
    });

    describe('Méthode skipUntil', function (): void {
        it('Doit sauter jusqu\'à une valeur', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $result = $collection->skipUntil(3);
            expect($result->values()->all())->toBe([3, 4, 5]);
        });
    });

    describe('Méthode skipWhile', function (): void {
        it('Doit sauter tant qu\'une condition est vraie', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $result = $collection->skipWhile(fn ($value) => $value < 3);
            expect($result->values()->all())->toBe([3, 4, 5]);
        });
    });

    describe('Méthode slice', function (): void {
        it('Doit extraire une tranche', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $slice = $collection->slice(1, 3);
            expect($slice->values()->all())->toBe([2, 3, 4]);
        });
    });

    describe('Méthode split', function (): void {
        it('Doit diviser en groupes', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $split = $collection->split(3);
            expect($split->count())->toBe(3);
        });
    });

    describe('Méthode splitIn', function (): void {
        it('Doit diviser en groupes avec distribution égale', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $split = $collection->splitIn(2);
            expect($split->count())->toBe(2);
        });
    });

    describe('Méthode sole', function (): void {
        it('Doit retourner l\'élément unique', function (): void {
            $collection = new Collection([['id' => 1, 'name' => 'John']]);
            $result = $collection->sole('name', '=', 'John');
            expect($result['id'])->toBe(1);
        });

        it('Doit lever une exception si aucun élément', function (): void {
            expect(function () {
                $collection = new Collection([]);
                $collection->sole();
            })->toThrow(new ItemNotFoundException());
        });

        it('Doit lever une exception si plusieurs éléments', function (): void {
            expect(function () {
                $collection = new Collection([1, 2, 3]);
                $collection->sole();
            })->toThrow();
        });
    });

    describe('Méthode firstOrFail', function (): void {
        it('Doit retourner le premier élément', function (): void {
            $collection = new Collection([1, 2, 3]);
            expect($collection->firstOrFail())->toBe(1);
        });

        it('Doit lever une exception si aucun élément', function (): void {
            expect(function () {
                $collection = new Collection([]);
                $collection->firstOrFail();
            })->toThrow(new ItemNotFoundException());
        });
    });

    describe('Méthode chunk', function (): void {
        it('Doit diviser en morceaux', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $chunks = $collection->chunk(2);
            expect($chunks->count())->toBe(3);
        });
    });

    describe('Méthode chunkWhile', function (): void {
        it('Doit créer des morceaux conditionnels', function (): void {
            $collection = new Collection([1, 2, 3, 6, 7, 8, 12, 13, 14]);
            $chunks = $collection->chunkWhile(function ($current, $key, $chunk) {
                return empty($chunk) || $current === Helpers::last($chunk->all()) + 1;
            });
            expect($chunks->count())->toBe(3);
        });
    });

    describe('Méthode sort', function (): void {
        it('Doit trier les éléments', function (): void {
            $collection = new Collection([3, 1, 4, 2, 5]);
            $sorted = $collection->sort();
            expect($sorted->values()->all())->toBe([1, 2, 3, 4, 5]);
        });
    });

    describe('Méthode sortDesc', function (): void {
        it('Doit trier en ordre décroissant', function (): void {
            $collection = new Collection([1, 3, 2, 5, 4]);
            $sorted = $collection->sortDesc();
            expect($sorted->values()->all())->toBe([5, 4, 3, 2, 1]);
        });
    });

    describe('Méthode sortBy', function (): void {
        it('Doit trier par une clé', function (): void {
            $collection = new Collection([
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35]
            ]);
            $sorted = $collection->sortBy('age');
            expect($sorted->first()['name'])->toBe('Jane');
            expect($sorted->last()['name'])->toBe('Bob');
        });
    });

    describe('Méthode sortByDesc', function (): void {
        it('Doit trier en ordre décroissant par une clé', function (): void {
            $collection = new Collection([
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35]
            ]);
            $sorted = $collection->sortByDesc('age');
            expect($sorted->first()['name'])->toBe('Bob');
        });
    });

    describe('Méthode sortKeys', function (): void {
        it('Doit trier par clés', function (): void {
            $collection = new Collection(['c' => 3, 'a' => 1, 'b' => 2]);
            $sorted = $collection->sortKeys();
            expect(array_keys($sorted->all()))->toBe(['a', 'b', 'c']);
        });
    });

    describe('Méthode sortKeysDesc', function (): void {
        it('Doit trier par clés en ordre décroissant', function (): void {
            $collection = new Collection(['a' => 1, 'c' => 3, 'b' => 2]);
            $sorted = $collection->sortKeysDesc();
            expect(array_keys($sorted->all()))->toBe(['c', 'b', 'a']);
        });
    });

    describe('Méthode sortKeysUsing', function (): void {
        it('Doit trier par clés avec callback', function (): void {
            $collection = new Collection(['aaa' => 1, 'bb' => 2, 'c' => 3]);
            $sorted = $collection->sortKeysUsing(fn ($a, $b) => strlen($a) <=> strlen($b));
            expect(array_keys($sorted->all()))->toBe(['c', 'bb', 'aaa']);
        });
    });

    describe('Méthode splice', function (): void {
        it('Doit remplacer une partie du tableau', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $spliced = $collection->splice(2, 2, [30, 40]);
            expect($spliced->all())->toBe([3, 4]);
            expect($collection->all())->toBe([1, 2, 30, 40, 5]);
        });
    });

    describe('Méthode take', function (): void {
        it('Doit prendre les premiers éléments', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $taken = $collection->take(3);
            expect($taken->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode takeUntil', function (): void {
        it('Doit prendre jusqu\'à une valeur', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $result = $collection->takeUntil(4);
            expect($result->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode takeWhile', function (): void {
        it('Doit prendre tant qu\'une condition est vraie', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            $result = $collection->takeWhile(fn ($value) => $value < 4);
            expect($result->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode transform', function (): void {
        it('Doit transformer les éléments en place', function (): void {
            $collection = new Collection([1, 2, 3]);
            $collection->transform(fn ($value) => $value * 2);
            expect($collection->all())->toBe([2, 4, 6]);
        });
    });

    describe('Méthode dot', function (): void {
        it('Doit aplatir avec notation point', function (): void {
            $collection = new Collection([
                'user' => ['name' => 'John', 'age' => 30],
                'settings' => ['theme' => 'dark']
            ]);
            $dotted = $collection->dot();
            expect($dotted->has('user.name'))->toBe(true);
        });
    });

    describe('Méthode undot', function (): void {
        it('Doit reconstruire depuis la notation point', function (): void {
            $collection = new Collection([
                'user.name' => 'John',
                'user.age' => 30,
                'settings.theme' => 'dark'
            ]);
            $undotted = $collection->undot();
            expect($undotted->has('user'))->toBe(true);
        });
    });

    describe('Méthode unique', function (): void {
        it('Doit supprimer les doublons', function (): void {
            $collection = new Collection([1, 2, 2, 3, 4, 4, 5]);
            $unique = $collection->unique();
            expect($unique->values()->all())->toBe([1, 2, 3, 4, 5]);
        });
    });

    describe('Méthode values', function (): void {
        it('Doit réinitialiser les clés', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
            $values = $collection->values();
            expect($values->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode zip', function (): void {
        it('Doit fusionner avec d\'autres itérables', function (): void {
            $collection = new Collection([1, 2, 3]);
            $zipped = $collection->zip(['a', 'b', 'c']);
            expect($zipped->first()->all())->toBe([1, 'a']);
        });
    });

    describe('Méthode pad', function (): void {
        it('Doit compléter la collection', function (): void {
            $collection = new Collection([1, 2, 3]);
            $padded = $collection->pad(5, 0);
            expect($padded->all())->toBe([1, 2, 3, 0, 0]);
        });
    });

    describe('Méthode getIterator', function (): void {
        it('Doit retourner un itérateur', function (): void {
            $collection = new Collection([1, 2, 3]);
            $iterator = $collection->getIterator();
            expect($iterator)->toBeAnInstanceOf(ArrayIterator::class);
        });
    });

    describe('Méthode count', function (): void {
        it('Doit compter les éléments', function (): void {
            $collection = new Collection([1, 2, 3, 4, 5]);
            expect($collection->count())->toBe(5);
        });
    });

    describe('Méthode countBy', function (): void {
        it('Doit compter les occurrences', function (): void {
            $collection = new Collection(['a', 'b', 'a', 'c', 'b', 'b']);
            $counted = $collection->countBy();
            expect($counted->get('b'))->toBe(3);
        });
    });

    describe('Méthode add', function (): void {
        it('Doit ajouter un élément', function (): void {
            $collection = new Collection([1, 2]);
            $collection->add(3);
            expect($collection->all())->toBe([1, 2, 3]);
        });
    });

    describe('Méthode toBase', function (): void {
        it('Doit créer une nouvelle instance de base', function (): void {
            $collection = new Collection([1, 2, 3]);
            $base = $collection->toBase();
            expect($base->all())->toBe([1, 2, 3]);
        });
    });

    describe('ArrayAccess', function (): void {
        it('Doit vérifier l\'existence d\'une clé', function (): void {
            $collection = new Collection(['a' => 1]);
            expect(isset($collection['a']))->toBe(true);
            expect(isset($collection['b']))->toBe(false);
        });

        it('Doit récupérer une valeur', function (): void {
            $collection = new Collection(['a' => 1]);
            expect($collection['a'])->toBe(1);
        });

        it('Doit définir une valeur', function (): void {
            $collection = new Collection();
            $collection['a'] = 1;
            expect($collection['a'])->toBe(1);
        });

        it('Doit supprimer une valeur', function (): void {
            $collection = new Collection(['a' => 1, 'b' => 2]);
            unset($collection['a']);
            expect(isset($collection['a']))->toBe(false);
            expect($collection->all())->toBe(['b' => 2]);
        });

        it('Doit ajouter sans clé', function (): void {
            $collection = new Collection();
            $collection[] = 1;
            $collection[] = 2;
            expect($collection->all())->toBe([1, 2]);
        });
    });
});
