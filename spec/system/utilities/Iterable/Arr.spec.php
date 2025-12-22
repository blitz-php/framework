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
use BlitzPHP\Contracts\Support\Arrayable;

use function Kahlan\expect;

describe('Utilities / Iterable / Arr', function (): void {
    describe('Méthode accessible', function (): void {
        it('Doit vérifier si une valeur est accessible comme tableau', function (): void {
            expect(Arr::accessible([]))->toBe(true);
            expect(Arr::accessible(new ArrayObject()))->toBe(true);
            expect(Arr::accessible('string'))->toBe(false);
            expect(Arr::accessible(123))->toBe(false);
            expect(Arr::accessible(null))->toBe(false);
        });
    });

    describe('Méthode add', function (): void {
        it('Doit ajouter une clé si elle n\'existe pas', function (): void {
            $array = ['a' => 1];
            $result = Arr::add($array, 'b', 2);
            expect($result)->toBe(['a' => 1, 'b' => 2]);
        });

        it('Ne doit pas écraser une clé existante', function (): void {
            $array = ['a' => 1];
            $result = Arr::add($array, 'a', 2);
            expect($result)->toBe(['a' => 1]);
        });

        it('Doit ajouter avec notation point', function (): void {
            $array = ['user' => ['name' => 'John']];
            $result = Arr::add($array, 'user.age', 30);
            expect($result)->toBe(['user' => ['name' => 'John', 'age' => 30]]);
        });
    });

    describe('Méthode array', function (): void {
        it('Doit récupérer un tableau avec notation point', function (): void {
            $array = ['user' => ['profile' => ['name' => 'John']]];
            $result = Arr::array($array, 'user.profile');
            expect($result)->toBe(['name' => 'John']);
        });

        it('Doit lever une exception si la valeur n\'est pas un tableau', function (): void {
            expect(function () {
                $array = ['user' => 'not an array'];
                Arr::array($array, 'user');
            })->toThrow(new InvalidArgumentException());
        });
    });

    describe('Méthode boolean', function (): void {
        it('Doit récupérer un booléen avec notation point', function (): void {
            $array = ['settings' => ['enabled' => true]];
            $result = Arr::boolean($array, 'settings.enabled');
            expect($result)->toBe(true);
        });

        it('Doit lever une exception si la valeur n\'est pas un booléen', function (): void {
            expect(function () {
                $array = ['flag' => 'true'];
                Arr::boolean($array, 'flag');
            })->toThrow(new InvalidArgumentException());
        });
    });

    describe('Méthode check', function (): void {
        it('Doit vérifier l\'existence d\'un chemin', function (): void {
            $array = ['user' => ['profile' => ['name' => 'John']]];
            expect(Arr::check($array, 'user.profile.name'))->toBe(true);
            expect(Arr::check($array, 'user.profile.email'))->toBe(false);
            expect(Arr::check($array, 'admin'))->toBe(false);
        });
    });

    describe('Méthode collapse', function (): void {
        it('Doit réduire les tableaux imbriqués', function (): void {
            $array = [[1, 2], [3, 4], [5]];
            $result = Arr::collapse($array);
            expect($result)->toBe([1, 2, 3, 4, 5]);
        });

        it('Doit ignorer les non-tableaux', function (): void {
            $array = [[1, 2], 'string', [3, 4], 123];
            $result = Arr::collapse($array);
            expect($result)->toBe([1, 2, 3, 4]);
        });
    });

    describe('Méthode combine', function (): void {
        it('Doit combiner selon les chemins', function (): void {
            $data = [
                ['User' => ['id' => 1, 'name' => 'John']],
                ['User' => ['id' => 2, 'name' => 'Jane']]
            ];
            $result = Arr::combine($data, '{n}.User.id', '{n}.User.name');
            expect($result)->toBe([1 => 'John', 2 => 'Jane']);
        });

        it('Doit grouper avec un chemin de groupe', function (): void {
            $data = [
                ['User' => ['id' => 1, 'name' => 'John', 'group' => 'admin']],
                ['User' => ['id' => 2, 'name' => 'Jane', 'group' => 'user']],
                ['User' => ['id' => 3, 'name' => 'Bob', 'group' => 'admin']]
            ];
            $result = Arr::combine($data, '{n}.User.id', '{n}.User.name', '{n}.User.group');
            expect($result)->toContainKey('admin');
            expect($result)->toContainKey('user');
        });

        xit('Doit lever une exception pour des tailles différentes', function (): void {
            expect(function () {
                $data = [['id' => 1], ['id' => 2, 'name' => 'John']];
                Arr::combine($data, '{n}.id');
            })->toThrow(new Exception());
        });
    });

    describe('Méthode contains', function (): void {
        it('Doit vérifier si un tableau contient un autre', function (): void {
            $array = ['a' => 1, 'b' => 2, 'c' => ['d' => 3, 'e' => 4]];
            $needle = ['b' => 2, 'c' => ['d' => 3]];
            expect(Arr::contains($array, $needle))->toBe(true);
            expect(Arr::contains($array, ['b' => 3]))->toBe(false);
        });
    });

    describe('Méthode crossJoin', function (): void {
        it('Doit créer un produit cartésien', function (): void {
            $result = Arr::crossJoin([1, 2], ['a', 'b']);
            $expected = [
                [0 => 1, 1 => 'a'],
                [0 => 1, 1 => 'b'],
                [0 => 2, 1 => 'a'],
                [0 => 2, 1 => 'b']
            ];
            expect($result)->toBe($expected);
        });

        it('Doit gérer plusieurs tableaux', function (): void {
            $result = Arr::crossJoin([1], ['a'], ['x']);
            expect($result)->toBe([[0 => 1, 1 => 'a', 2 => 'x']]);
        });
    });

    describe('Méthode dimensions', function (): void {
        it('Doit compter les dimensions d\'un tableau', function (): void {
            expect(Arr::dimensions([]))->toBe(0);
            expect(Arr::dimensions([1, 2, 3]))->toBe(1);
            expect(Arr::dimensions([[1, 2], [3, 4]]))->toBe(2);
            expect(Arr::dimensions([[[1]], [[2]]]))->toBe(3);
        });
    });

    describe('Méthode divide', function (): void {
        it('Doit diviser en clés et valeurs', function (): void {
            $array = ['a' => 1, 'b' => 2, 'c' => 3];
            $result = Arr::divide($array);
            expect($result)->toBe([['a', 'b', 'c'], [1, 2, 3]]);
        });
    });

    describe('Méthode dot', function (): void {
        it('Doit aplatir avec notation point', function (): void {
            $array = [
                'user' => [
                    'name' => 'John',
                    'age' => 30,
                    'settings' => ['theme' => 'dark']
                ]
            ];
            $result = Arr::dot($array);
            expect($result)->toContainKey('user.name');
            expect($result)->toContainKey('user.age');
            expect($result)->toContainKey('user.settings.theme');
        });

        it('Doit ajouter un préfixe', function (): void {
            $array = ['name' => 'John'];
            $result = Arr::dot($array, 'prefix.');
            expect($result)->toBe(['prefix.name' => 'John']);
        });
    });

    describe('Méthode undot', function (): void {
        it('Doit reconstruire depuis la notation point', function (): void {
            $array = [
                'user.name' => 'John',
                'user.age' => 30,
                'user.settings.theme' => 'dark'
            ];
            $result = Arr::undot($array);
            expect($result)->toBe([
                'user' => [
                    'name' => 'John',
                    'age' => 30,
                    'settings' => ['theme' => 'dark']
                ]
            ]);
        });
    });

    describe('Méthode except', function (): void {
        it('Doit exclure des clés', function (): void {
            $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
            $result = Arr::except($array, ['b', 'd']);
            expect($result)->toBe(['a' => 1, 'c' => 3]);
        });

        it('Doit exclure avec une seule clé', function (): void {
            $array = ['a' => 1, 'b' => 2];
            $result = Arr::except($array, 'b');
            expect($result)->toBe(['a' => 1]);
        });
    });

    describe('Méthode exists', function (): void {
        it('Doit vérifier l\'existence d\'une clé', function (): void {
            $array = ['a' => 1, 'b' => 2];
            expect(Arr::exists($array, 'b'))->toBe(true);
            expect(Arr::exists($array, 'c'))->toBe(false);
        });

        it('Doit gérer ArrayAccess', function (): void {
            $object = new ArrayObject(['a' => 1]);
            expect(Arr::exists($object, 'a'))->toBe(true);
        });

        it('Doit convertir les flottants en chaînes', function (): void {
            $array = ['1.5' => 'value'];
            expect(Arr::exists($array, 1.5))->toBe(true);
        });
    });

    describe('Méthode extract', function (): void {
        it('Doit extraire des valeurs avec notation point', function (): void {
            $data = [
                ['User' => ['id' => 1, 'name' => 'John']],
                ['User' => ['id' => 2, 'name' => 'Jane']]
            ];
            $result = Arr::extract($data, '{n}.User.name');
            expect($result)->toBe(['John', 'Jane']);
        });

        xit('Doit extraire avec conditions', function (): void {
            $data = [
                ['User' => ['id' => 1, 'name' => 'John']],
                ['User' => ['id' => 2, 'name' => 'Jane']]
            ];
            $result = Arr::extract($data, '{n}.User[id=2].name');
            expect($result)->toBe(['Jane']);
        });
    });

    describe('Méthode expand', function (): void {
        it('Doit développer un tableau aplati', function (): void {
            $array = ['user.name' => 'John', 'user.age' => 30];
            $result = Arr::expand($array);
            expect($result)->toBe(['user' => ['name' => 'John', 'age' => 30]]);
        });

        it('Doit développer avec séparateur personnalisé', function (): void {
            $array = ['user-name' => 'John', 'user-age' => 30];
            $result = Arr::expand($array, '-');
            expect($result)->toBe(['user' => ['name' => 'John', 'age' => 30]]);
        });
    });

    describe('Méthode filter', function (): void {
        it('Doit filtrer un tableau', function (): void {
            $array = [0, 1, false, 2, null, 3, ''];
            $result = Arr::filter($array);
            expect(array_values($result))->toBe([0, 1, 2, 3]);
        });

        it('Doit filtrer récursivement', function (): void {
            $array = [
                'a' => null,
                'b' => [0, 1, false],
                'c' => 2
            ];
            $result = Arr::filter($array);
            expect($result)->toBe(['b' => [0, 1], 'c' => 2]);
        });

        it('Doit utiliser un callback personnalisé', function (): void {
            $array = [1, 2, 3, 4, 5];
            $result = Arr::filter($array, fn ($value) => $value > 2);
            expect(array_values($result))->toBe([3, 4, 5]);
        });
    });

    describe('Méthode first', function (): void {
        it('Doit retourner le premier élément', function (): void {
            $array = [1, 2, 3];
            expect(Arr::first($array))->toBe(1);
        });

        it('Doit retourner le premier élément correspondant au callback', function (): void {
            $array = [1, 2, 3, 4, 5];
            $result = Arr::first($array, fn ($value) => $value > 3);
            expect($result)->toBe(4);
        });

        it('Doit retourner la valeur par défaut si vide', function (): void {
            $array = [];
            expect(Arr::first($array, null, 'default'))->toBe('default');
        });
    });

    describe('Méthode flatten', function (): void {
        it('Doit aplatir un tableau', function (): void {
            $array = [[1, 2], [3, 4], [5]];
            $result = Arr::flatten($array);
            expect($result)->toBe([1, 2, 3, 4, 5]);
        });

        it('Doit aplatir avec profondeur limitée', function (): void {
            $array = [[1, [2, 3]], [4, [5, 6]]];
            $result = Arr::flatten($array, 1);
            expect($result)->toBe([1, [2, 3], 4, [5, 6]]);
        });
    });

    describe('Méthode flattenSeparator', function (): void {
        it('Doit aplatir avec séparateur personnalisé', function (): void {
            $array = [
                ['Foo' => ['Bar' => 'Far']]
            ];
            $result = Arr::flattenSeparator($array, '-');
            expect($result)->toBe(['0-Foo-Bar' => 'Far']);
        });
    });

    describe('Méthode forget', function (): void {
        it('Doit supprimer une clé', function (): void {
            $array = ['a' => 1, 'b' => 2, 'c' => 3];
            Arr::forget($array, 'b');
            expect($array)->toBe(['a' => 1, 'c' => 3]);
        });

        it('Doit supprimer avec notation point', function (): void {
            $array = [
                'user' => [
                    'profile' => ['name' => 'John', 'age' => 30],
                    'settings' => ['theme' => 'dark']
                ]
            ];
            Arr::forget($array, 'user.profile.age');
            expect($array)->toBe([
                'user' => [
                    'profile' => ['name' => 'John'],
                    'settings' => ['theme' => 'dark']
                ]
            ]);
        });

        it('Doit supprimer plusieurs clés', function (): void {
            $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
            Arr::forget($array, ['b', 'd']);
            expect($array)->toBe(['a' => 1, 'c' => 3]);
        });
    });

    describe('Méthode format', function (): void {
        it('Doit formater des valeurs extraites', function (): void {
            $data = [
                ['User' => ['id' => 1, 'name' => 'John']],
                ['User' => ['id' => 2, 'name' => 'Jane']]
            ];
            $result = Arr::format($data, ['{n}.User.id', '{n}.User.name'], '%s: %s');
            expect($result)->toBe(['1: John', '2: Jane']);
        });
    });

    describe('Méthode from', function (): void {
        it('Doit convertir Arrayable en tableau', function (): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array { return ['a' => 1]; }
            };
            $result = Arr::from($arrayable);
            expect($result)->toBe(['a' => 1]);
        });

        it('Doit convertir Enumerable en tableau', function (): void {
            $enumerable = new class implements IteratorAggregate {
                public function getIterator(): Traversable {
                    return new ArrayIterator(['a' => 1]);
                }
            };
            $result = Arr::from($enumerable);
            expect($result)->toBe(['a' => 1]);
        });

        it('Doit convertir Jsonable en tableau', function (): void {
            $jsonable = new class implements JsonSerializable {
                public function jsonSerialize(): array { return ['a' => 1]; }
            };
            $result = Arr::from($jsonable);
            expect($result)->toBe(['a' => 1]);
        });

        it('Doit convertir un objet en tableau', function (): void {
            $object = (object) ['a' => 1];
            $result = Arr::from($object);
            expect($result)->toBe(['a' => 1]);
        });

        it('Doit lever une exception pour un scalaire', function (): void {
            expect(function () {
                Arr::from('string');
            })->toThrow(new InvalidArgumentException());
        });
    });

    describe('Méthode get', function (): void {
        it('Doit récupérer une valeur avec notation point', function (): void {
            $array = ['user' => ['profile' => ['name' => 'John']]];
            expect(Arr::get($array, 'user.profile.name'))->toBe('John');
        });

        it('Doit retourner la valeur par défaut si non trouvé', function (): void {
            $array = ['a' => 1];
            expect(Arr::get($array, 'b', 'default'))->toBe('default');
        });

        it('Doit retourner null pour une clé null', function (): void {
            $array = ['a' => 1];
            expect(Arr::get($array, null))->toBe(['a' => 1]);
        });

        it('Doit gérer ArrayAccess', function (): void {
            $object = new ArrayObject(['a' => ['b' => 1]]);
            expect(Arr::get($object, 'a.b'))->toBe(1);
        });
    });

    describe('Méthode getRecursive', function (): void {
        it('Doit récupérer récursivement une valeur', function (): void {
            $array = ['user' => ['profile' => ['name' => 'John']]];
            expect(Arr::getRecursive($array, 'user.profile.name'))->toBe('John');
        });

        it('Doit retourner null si non trouvé', function (): void {
            $array = ['a' => 1];
            expect(Arr::getRecursive($array, 'b.c'))->toBe(null);
        });
    });

    describe('Méthode has', function (): void {
        it('Doit vérifier l\'existence d\'un chemin', function (): void {
            $array = ['user' => ['profile' => ['name' => 'John']]];
            expect(Arr::has($array, 'user.profile.name'))->toBe(true);
            expect(Arr::has($array, 'user.profile.email'))->toBe(false);
        });

        it('Doit vérifier plusieurs chemins', function (): void {
            $array = ['a' => 1, 'b' => 2];
            expect(Arr::has($array, ['a', 'b']))->toBe(true);
            expect(Arr::has($array, ['a', 'c']))->toBe(false);
        });

        it('Doit gérer ArrayAccess', function (): void {
            $object = new ArrayObject(['a' => ['b' => 1]]);
            expect(Arr::has($object, 'a.b'))->toBe(true);
        });
    });

    describe('Méthode hasAll', function (): void {
        it('Doit vérifier que tous les chemins existent', function (): void {
            $array = ['a' => 1, 'b' => 2, 'c' => 3];
            expect(Arr::hasAll($array, ['a', 'b']))->toBe(true);
            expect(Arr::hasAll($array, ['a', 'd']))->toBe(false);
        });
    });

    describe('Méthode hasAny', function (): void {
        it('Doit vérifier qu\'au moins un chemin existe', function (): void {
            $array = ['a' => 1, 'b' => 2];
            expect(Arr::hasAny($array, ['a', 'c']))->toBe(true);
            expect(Arr::hasAny($array, ['c', 'd']))->toBe(false);
        });

        it('Doit retourner false pour un tableau vide', function (): void {
            expect(Arr::hasAny([], ['a']))->toBe(false);
        });
    });

    describe('Méthode every', function (): void {
        it('Doit vérifier que tous les éléments satisfont une condition', function (): void {
            $array = [2, 4, 6, 8];
            expect(Arr::every($array, fn ($value) => $value % 2 === 0))->toBe(true);
            expect(Arr::every($array, fn ($value) => $value > 5))->toBe(false);
        });
    });

    describe('Méthode insert', function (): void {
        it('Doit insérer des valeurs avec notation point', function (): void {
            $data = [
                ['User' => ['id' => 1]],
                ['User' => ['id' => 2]]
            ];
            $result = Arr::insert($data, '{n}.User.name', 'Default');
            expect($result[0]['User']['name'])->toBe('Default');
            expect($result[1]['User']['name'])->toBe('Default');
        });
    });

    describe('Méthode isAssoc', function (): void {
        it('Doit identifier un tableau associatif', function (): void {
            expect(Arr::isAssoc(['a' => 1, 'b' => 2]))->toBe(true);
            expect(Arr::isAssoc([0 => 'a', 1 => 'b']))->toBe(false);
            expect(Arr::isAssoc([]))->toBe(false);
        });
    });

    describe('Méthode isList', function (): void {
        it('Doit identifier une liste', function (): void {
            expect(Arr::isList([0 => 'a', 1 => 'b']))->toBe(true);
            expect(Arr::isList([1 => 'a', 0 => 'b']))->toBe(false);
            expect(Arr::isList(['a' => 1]))->toBe(false);
        });
    });

    describe('Méthode join', function (): void {
        it('Doit joindre les éléments', function (): void {
            $array = ['a', 'b', 'c'];
            expect(Arr::join($array, ', '))->toBe('a, b, c');
        });

        it('Doit joindre avec une colle finale', function (): void {
            $array = ['a', 'b', 'c'];
            expect(Arr::join($array, ', ', ' and '))->toBe('a, b and c');
        });

        it('Doit retourner une chaîne vide pour un tableau vide', function (): void {
            expect(Arr::join([], ', '))->toBe('');
        });
    });

    describe('Méthode keyBy', function (): void {
        it('Doit indexer par une clé', function (): void {
            $array = [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ];
            $result = Arr::keyBy($array, 'id');
            expect($result)->toBe([
                1 => ['id' => 1, 'name' => 'John'],
                2 => ['id' => 2, 'name' => 'Jane']
            ]);
        });

        it('Doit indexer par un callback', function (): void {
            $array = [
                ['name' => 'John'],
                ['name' => 'Jane']
            ];
            $result = Arr::keyBy($array, fn ($item) => strtoupper($item['name']));
            expect($result)->toBe([
                'JOHN' => ['name' => 'John'],
                'JANE' => ['name' => 'Jane']
            ]);
        });
    });

    describe('Méthode prependKeysWith', function (): void {
        it('Doit préfixer les clés', function (): void {
            $array = ['a' => 1, 'b' => 2];
            $result = Arr::prependKeysWith($array, 'prefix_');
            expect($result)->toBe(['prefix_a' => 1, 'prefix_b' => 2]);
        });
    });

    describe('Méthode last', function (): void {
        it('Doit retourner le dernier élément', function (): void {
            $array = [1, 2, 3];
            expect(Arr::last($array))->toBe(3);
        });

        it('Doit retourner le dernier élément correspondant au callback', function (): void {
            $array = [1, 2, 3, 4, 5];
            $result = Arr::last($array, fn ($value) => $value < 4);
            expect($result)->toBe(3);
        });

        it('Doit retourner la valeur par défaut si vide', function (): void {
            $array = [];
            expect(Arr::last($array, null, 'default'))->toBe('default');
        });
    });

    describe('Méthode map', function (): void {
        it('Doit transformer chaque élément', function (): void {
            $array = [1, 2, 3];
            $result = Arr::map($array, fn ($value) => $value * 2);
            expect($result)->toBe([2, 4, 6]);
        });

        it('Doit préserver les clés', function (): void {
            $array = ['a' => 1, 'b' => 2];
            $result = Arr::map($array, fn ($value) => $value * 2);
            expect($result)->toBe(['a' => 2, 'b' => 4]);
        });
    });

    describe('Méthode mapSpread', function (): void {
        it('Doit mapper avec décomposition', function (): void {
            $array = [[1, 2], [3, 4]];
            $result = Arr::mapSpread($array, fn ($a, $b) => $a + $b);
            expect($result)->toBe([3, 7]);
        });
    });

    describe('Méthode mapWithKeys', function (): void {
        it('Doit mapper avec nouvelles clés', function (): void {
            $array = [1, 2, 3];
            $result = Arr::mapWithKeys($array, fn ($value) => ["key_$value" => $value * 2]);
            expect($result)->toBe(['key_1' => 2, 'key_2' => 4, 'key_3' => 6]);
        });
    });

    describe('Méthode maxDimensions', function (): void {
        it('Doit trouver la dimension maximale', function (): void {
            $array = [[1, 2], [3, [4, 5]], 6];
            expect(Arr::maxDimensions($array))->toBe(2);
        });
    });

    describe('Méthode merge', function (): void {
        it('Doit fusionner des tableaux', function (): void {
            $array1 = ['a' => 1, 'b' => 2];
            $array2 = ['b' => 3, 'c' => 4];
            $result = Arr::merge($array1, $array2);
            expect($result)->toBe(['a' => 1, 'b' => 3, 'c' => 4]);
        });

        it('Doit fusionner récursivement les tableaux imbriqués', function (): void {
            $array1 = ['user' => ['name' => 'John', 'age' => 30]];
            $array2 = ['user' => ['age' => 31, 'city' => 'Paris']];
            $result = Arr::merge($array1, $array2);
            expect($result)->toBe(['user' => ['name' => 'John', 'age' => 31, 'city' => 'Paris']]);
        });
    });

    describe('Méthode numeric', function (): void {
        it('Doit vérifier si toutes les valeurs sont numériques', function (): void {
            expect(Arr::numeric([1, 2, 3]))->toBe(true);
            expect(Arr::numeric(['1', '2', '3']))->toBe(true);
            expect(Arr::numeric([1, '2', 3.5]))->toBe(true);
            expect(Arr::numeric([1, 'a', 3]))->toBe(false);
        });
    });

    describe('Méthode only', function (): void {
        it('Doit garder seulement certaines clés', function (): void {
            $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
            $result = Arr::only($array, ['a', 'c']);
            expect($result)->toBe(['a' => 1, 'c' => 3]);
        });

        it('Doit accepter une seule clé comme chaîne', function (): void {
            $array = ['a' => 1, 'b' => 2];
            $result = Arr::only($array, 'a');
            expect($result)->toBe(['a' => 1]);
        });
    });

    describe('Méthode partition', function (): void {
        it('Doit partitionner un tableau', function (): void {
            $array = [1, 2, 3, 4, 5];
            [$passed, $failed] = Arr::partition($array, fn ($value) => $value > 2);
            expect(array_values($passed))->toBe([3, 4, 5]);
            expect(array_values($failed))->toBe([1, 2]);
        });
    });

    describe('Méthode pluck', function (): void {
        it('Doit extraire des valeurs', function (): void {
            $array = [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ];
            $result = Arr::pluck($array, 'name');
            expect($result)->toBe(['John', 'Jane']);
        });

        it('Doit extraire avec clé personnalisée', function (): void {
            $array = [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ];
            $result = Arr::pluck($array, 'name', 'id');
            expect($result)->toBe([1 => 'John', 2 => 'Jane']);
        });

        it('Doit extraire avec notation point', function (): void {
            $array = [
                ['user' => ['profile' => ['name' => 'John']]],
                ['user' => ['profile' => ['name' => 'Jane']]]
            ];
            $result = Arr::pluck($array, 'user.profile.name');
            expect($result)->toBe(['John', 'Jane']);
        });
    });

    describe('Méthode prepend', function (): void {
        it('Doit ajouter au début', function (): void {
            $array = ['b' => 2, 'c' => 3];
            $result = Arr::prepend($array, 1);
            expect($result)->toBe([1, 'b' => 2, 'c' => 3]);
        });

        it('Doit ajouter au début avec clé', function (): void {
            $array = ['b' => 2];
            $result = Arr::prepend($array, 1, 'a');
            expect($result)->toBe(['a' => 1, 'b' => 2]);
        });
    });

    describe('Méthode pull', function (): void {
        it('Doit extraire et supprimer une valeur', function (): void {
            $array = ['a' => 1, 'b' => 2, 'c' => 3];
            $value = Arr::pull($array, 'b');
            expect($value)->toBe(2);
            expect($array)->toBe(['a' => 1, 'c' => 3]);
        });

        it('Doit retourner la valeur par défaut si non trouvé', function (): void {
            $array = ['a' => 1];
            $value = Arr::pull($array, 'b', 'default');
            expect($value)->toBe('default');
            expect($array)->toBe(['a' => 1]);
        });
    });

    describe('Méthode push', function (): void {
        it('Doit ajouter des valeurs à un tableau', function (): void {
            $array = ['items' => [1, 2]];
            $result = Arr::push($array, 'items', 3, 4);
            expect($result['items'])->toBe([1, 2, 3, 4]);
        });

        it('Doit créer le tableau s\'il n\'existe pas', function (): void {
            $array = [];
            $result = Arr::push($array, 'items', 1);
            expect($result['items'])->toBe([1]);
        });
    });

    describe('Méthode query', function (): void {
        it('Doit créer une chaîne de requête', function (): void {
            $array = ['name' => 'John Doe', 'age' => 30];
            $result = Arr::query($array);
            expect($result)->toBe('name=John%20Doe&age=30');
        });
    });

    describe('Méthode random', function (): void {
        it('Doit retourner un élément aléatoire', function (): void {
            $array = [1, 2, 3, 4, 5];
            $random = Arr::random($array);
            expect(in_array($random, $array))->toBe(true);
        });

        it('Doit retourner plusieurs éléments aléatoires', function (): void {
            $array = [1, 2, 3, 4, 5];
            $random = Arr::random($array, 3);
            expect(count($random))->toBe(3);
            expect(array_intersect($random, $array))->toBe($random);
        });

        it('Doit préserver les clés si demandé', function (): void {
            $array = ['a' => 1, 'b' => 2, 'c' => 3];
            $random = Arr::random($array, 2, true);
            expect(count($random))->toBe(2);
            expect(array_keys($random))->toBe(array_intersect(array_keys($random), array_keys($array)));
        });

        it('Doit lever une exception si on demande plus d\'éléments que disponible', function (): void {
            expect(function () {
                $array = [1, 2];
                Arr::random($array, 3);
            })->toThrow(new InvalidArgumentException());
        });
    });

    describe('Méthode reject', function (): void {
        it('Doit rejeter les éléments satisfaisant une condition', function (): void {
            $array = [1, 2, 3, 4, 5];
            $result = Arr::reject($array, fn ($value) => $value > 3);
            expect($result)->toBe([1, 2, 3]);
        });
    });

    describe('Méthode remove', function (): void {
        it('Doit supprimer un chemin', function (): void {
            $data = [
                ['User' => ['id' => 1, 'name' => 'John']],
                ['User' => ['id' => 2, 'name' => 'Jane']]
            ];
            $result = Arr::remove($data, '{n}.User.name');
            expect($result[0]['User'])->toContainKey('id');
            expect($result[0]['User'])->not->toContainKey('name');
        });
    });

    describe('Méthode select', function (): void {
        it('Doit sélectionner des clés spécifiques', function (): void {
            $array = [
                ['id' => 1, 'name' => 'John', 'age' => 30],
                ['id' => 2, 'name' => 'Jane', 'age' => 25]
            ];
            $result = Arr::select($array, ['id', 'name']);
            expect($result)->toBe([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ]);
        });
    });

    describe('Méthode set', function (): void {
        xit('Doit définir une valeur avec notation point', function (): void {
            $array = [];
            $result = Arr::set($array, 'user.profile.name', 'John');
            expect($result)->toBe(['user' => ['profile' => ['name' => 'John']]]);
        });

        it('Doit remplacer un tableau complet avec clé null', function (): void {
            $array = ['a' => 1];
            $result = Arr::set($array, null, ['b' => 2]);
            expect($result)->toBe(['b' => 2]);
        });
    });

    describe('Méthode setRecursive', function (): void {
        it('Doit définir une valeur récursivement', function (): void {
            $array = [];
            Arr::setRecursive($array, 'user.profile.name', 'John');
            expect($array)->toBe(['user' => ['profile' => ['name' => 'John']]]);
        });
    });

    describe('Méthode shuffle', function (): void {
        it('Doit mélanger un tableau', function (): void {
            $array = [1, 2, 3, 4, 5];
            $shuffled = Arr::shuffle($array);
            expect(count($shuffled))->toBe(5);
            expect(array_intersect($shuffled, $array))->toBe($shuffled);
        });

        it('Doit mélanger avec une graine', function (): void {
            $array = [1, 2, 3, 4, 5];
            $shuffled1 = Arr::shuffle($array, 123);
            $shuffled2 = Arr::shuffle($array, 123);
            expect($shuffled1)->toBe($shuffled2);
        });
    });

    describe('Méthode sole', function (): void {
        it('Doit retourner l\'élément unique', function (): void {
            $array = [['id' => 1, 'name' => 'John']];
            $result = Arr::sole($array, fn ($item) => $item['name'] === 'John');
            expect($result['id'])->toBe(1);
        });

        it('Doit lever une exception si aucun élément', function (): void {
            expect(function () {
                $array = [];
                Arr::sole($array);
            })->toThrow(new BlitzPHP\Utilities\Exceptions\ItemNotFoundException());
        });

        it('Doit lever une exception si plusieurs éléments', function (): void {
            expect(function () {
                $array = [1, 2, 3];
                Arr::sole($array);
            })->toThrow();
        });
    });

    describe('Méthode some', function (): void {
        it('Doit vérifier qu\'au moins un élément satisfait une condition', function (): void {
            $array = [1, 2, 3, 4, 5];
            expect(Arr::some($array, fn ($value) => $value > 4))->toBe(true);
            expect(Arr::some($array, fn ($value) => $value > 5))->toBe(false);
        });
    });

    describe('Méthode sort', function (): void {
        it('Doit trier un tableau', function (): void {
            $array = [3, 1, 4, 2, 5];
            $result = Arr::sort($array);
            expect(array_values($result))->toBe([1, 2, 3, 4, 5]);
        });

        it('Doit trier avec un callback', function (): void {
            $array = ['apple', 'banana', 'cherry'];
            $result = Arr::sort($array, fn ($a, $b) => strlen($a) <=> strlen($b));
            expect($result)->toBe(['apple', 'banana', 'cherry']);
        });
    });

    describe('Méthode sortDesc', function (): void {
        it('Doit trier en ordre décroissant', function (): void {
            $array = [1, 3, 2, 5, 4];
            $result = Arr::sortDesc($array);
            expect(array_values($result))->toBe([5, 4, 3, 2, 1]);
        });
    });

    describe('Méthode sortField', function (): void {
        it('Doit trier par champ', function (): void {
            $array = [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35]
            ];
            $result = Arr::sortField($array, 'age');
            expect($result[0]['name'])->toBe('Jane');
            expect($result[2]['name'])->toBe('Bob');
        });

        it('Doit trier en ordre décroissant', function (): void {
            $array = [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35]
            ];
            $result = Arr::sortField($array, 'age', Arr::SORT_DESC);
            expect($result[0]['name'])->toBe('Bob');
            expect($result[2]['name'])->toBe('Jane');
        });
    });

    describe('Méthode sortRecursive', function (): void {
        it('Doit trier récursivement', function (): void {
            $array = [
                'c' => 3,
                'a' => 1,
                'b' => ['d' => 4, 'c' => 3, 'a' => 1]
            ];
            $result = Arr::sortRecursive($array);
            expect(array_keys($result))->toBe(['a', 'b', 'c']);
            expect(array_keys($result['b']))->toBe(['a', 'c', 'd']);
        });

        it('Doit trier en ordre décroissant', function (): void {
            $array = [1, 3, 2];
            $result = Arr::sortRecursiveDesc($array);
            expect($result)->toBe([3, 2, 1]);
        });
    });

    describe('Méthode take', function (): void {
        it('Doit prendre les premiers éléments', function (): void {
            $array = [1, 2, 3, 4, 5];
            $result = Arr::take($array, 3);
            expect($result)->toBe([1, 2, 3]);
        });

        it('Doit prendre les derniers éléments avec limite négative', function (): void {
            $array = [1, 2, 3, 4, 5];
            $result = Arr::take($array, -2);
            expect($result)->toBe([4, 5]);
        });
    });

    describe('Méthode diffRecursive', function (): void {
        xit('Doit comparer récursivement', function (): void {
            $array1 = ['a' => 1, 'b' => ['c' => 2, 'd' => 3]];
            $array2 = ['a' => 1, 'b' => ['c' => 2]];
            $result = Arr::diffRecursive($array1, $array2);
            expect($result)->toBe(['b' => ['d' => 3]]);
        });
    });

    describe('Méthode countRecursive', function (): void {
        it('Doit compter récursivement', function (): void {
            $array = [1, [2, 3], [4, [5, 6]]];
            $count = Arr::countRecursive($array);
            expect($count)->toBe(9);
        });
    });

    describe('Méthode toCssClasses', function (): void {
        it('Doit créer des classes CSS', function (): void {
            $array = [
                'btn' => true,
                'btn-primary' => true,
                'disabled' => false,
                'active'
            ];
            $result = Arr::toCssClasses($array);
            expect($result)->toBe('btn btn-primary active');
        });
    });

    describe('Méthode toCssStyles', function (): void {
        it('Doit créer des styles CSS', function (): void {
            $array = [
                'color: red' => true,
                'font-weight: bold' => false,
                'margin: 10px'
            ];
            $result = Arr::toCssStyles($array);
            expect($result)->toBe('color: red; margin: 10px;');
        });
    });

    describe('Méthode toString', function (): void {
        it('Doit créer une chaîne de paramètres', function (): void {
            $array = ['name' => 'John Doe', 'active' => true, 'count' => 5];
            $result = Arr::toString($array, ';');
            expect($result)->toMatch('/name="John Doe"/');
            expect($result)->toMatch('/active/');
            expect($result)->toMatch('/count=5/');
        });
    });

    describe('Méthode where', function (): void {
        it('Doit filtrer avec un callback', function (): void {
            $array = [1, 2, 3, 4, 5];
            $result = Arr::where($array, fn ($value) => $value > 2);
            expect(array_values($result))->toBe([3, 4, 5]);
        });
    });

    describe('Méthode whereNotNull', function (): void {
        it('Doit filtrer les valeurs non nulles', function (): void {
            $array = [null, 1, '', 0, false, 2];
            $result = Arr::whereNotNull($array);
            expect(array_values($result))->toBe([1, '', 0, false, 2]);
        });
    });

    describe('Méthode wrap', function (): void {
        it('Doit envelopper une valeur dans un tableau', function (): void {
            expect(Arr::wrap('string'))->toBe(['string']);
            expect(Arr::wrap(123))->toBe([123]);
            expect(Arr::wrap(['already']))->toBe(['already']);
            expect(Arr::wrap(null))->toBe([]);
        });
    });
});
