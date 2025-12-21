<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was was distributed with this source code.
 */

use BlitzPHP\Utilities\Support\Fluent;

use function Kahlan\expect;

describe('Utilities / Support / Fluent', function (): void {
    describe('Initialisation', function (): void {
        it('Doit créer une instance fluide avec des attributs', function (): void {
            $fluent = new Fluent(['name' => 'John', 'age' => 30]);

            expect($fluent)->toBeAnInstanceOf(Fluent::class);
            expect($fluent->toArray())->toBe(['name' => 'John', 'age' => 30]);
        });

        it('Doit créer une instance avec make()', function (): void {
            $fluent = Fluent::make(['test' => 'value']);

            expect($fluent)->toBeAnInstanceOf(Fluent::class);
            expect($fluent->test)->toBe('value');
        });

        it('Doit créer une instance vide', function (): void {
            $fluent = new Fluent();

            expect($fluent->toArray())->toBe([]);
            expect($fluent->isEmpty())->toBe(true);
        });
    });

    describe('Accès aux données', function (): void {
        it('Doit récupérer des attributs avec get()', function (): void {
            $fluent = new Fluent(['name' => 'John', 'details' => ['age' => 30]]);

            expect($fluent->get('name'))->toBe('John');
            expect($fluent->get('details.age'))->toBe(30);
            expect($fluent->get('nonexistent', 'default'))->toBe('default');
        });

        it('Doit définir des attributs avec set()', function (): void {
            $fluent = new Fluent();
            $fluent->set('name', 'John');
            $fluent->set('details.age', 30);

            expect($fluent->name)->toBe('John');
            expect($fluent->get('details.age'))->toBe(30);
        });

        it('Doit récupérer des attributs avec value()', function (): void {
            $fluent = new Fluent(['name' => 'John', 'age' => null]);

            expect($fluent->value('name'))->toBe('John');
            expect($fluent->value('age'))->toBeNull();
            expect($fluent->value('nonexistent', 'default'))->toBe('default');
        });

        it('Doit remplir des attributs avec fill()', function (): void {
            $fluent = new Fluent(['name' => 'John']);
            $fluent->fill(['age' => 30, 'city' => 'Paris']);

            expect($fluent->toArray())->toBe(['name' => 'John', 'age' => 30, 'city' => 'Paris']);
        });
    });

    describe('Méthodes magiques', function (): void {
        it('Doit accéder aux attributs via __get', function (): void {
            $fluent = new Fluent(['name' => 'John', 'active' => true]);

            expect($fluent->name)->toBe('John');
            expect($fluent->active)->toBe(true);
        });

        it('Doit définir des attributs via __set', function (): void {
            $fluent = new Fluent();
            $fluent->name = 'John';
            $fluent->active = true;

            expect($fluent->toArray())->toBe(['name' => 'John', 'active' => true]);
        });

        it('Doit vérifier l\'existence via __isset', function (): void {
            $fluent = new Fluent(['name' => 'John']);

            expect(isset($fluent->name))->toBe(true);
            expect(isset($fluent->nonexistent))->toBe(false);
        });

        it('Doit supprimer des attributs via __unset', function (): void {
            $fluent = new Fluent(['name' => 'John', 'age' => 30]);
            unset($fluent->name);

            expect($fluent->toArray())->toBe(['age' => 30]);
        });

        it('Doit créer des attributs via __call', function (): void {
            $fluent = new Fluent();
            $fluent->name('John')->active(true);

            expect($fluent->toArray())->toBe(['name' => 'John', 'active' => true]);
        });
    });

    describe('Conversion', function (): void {
        it('Doit convertir en tableau avec toArray()', function (): void {
            $fluent = new Fluent(['name' => 'John', 'age' => 30]);
            $array = $fluent->toArray();

            expect($array)->toBe(['name' => 'John', 'age' => 30]);
        });

        it('Doit convertir en JSON avec toJson()', function (): void {
            $fluent = new Fluent(['name' => 'John', 'age' => 30]);
            $json = $fluent->toJson();

            expect($json)->toBe('{"name":"John","age":30}');
            expect(json_decode($json, true))->toBe(['name' => 'John', 'age' => 30]);
        });

        it('Doit convertir en JSON formaté avec toPrettyJson()', function (): void {
            $fluent = new Fluent(['name' => 'John']);
            $json = $fluent->toPrettyJson();

            expect($json)->toMatch('/^{\s+"name":/');
        });

        it('Doit sérialiser pour JSON avec jsonSerialize()', function (): void {
            $fluent = new Fluent(['name' => 'John']);
            $serialized = $fluent->jsonSerialize();

            expect($serialized)->toBe(['name' => 'John']);
        });
    });

    describe('Méthodes utilitaires', function (): void {
        it('Doit vérifier si vide avec isEmpty() et isNotEmpty()', function (): void {
            $empty = new Fluent();
            $notEmpty = new Fluent(['name' => 'John']);

            expect($empty->isEmpty())->toBe(true);
            expect($empty->isNotEmpty())->toBe(false);
            expect($notEmpty->isEmpty())->toBe(false);
            expect($notEmpty->isNotEmpty())->toBe(true);
        });

        it('Doit créer un scope avec scope()', function (): void {
            $fluent = new Fluent(['user' => ['name' => 'John', 'age' => 30]]);
            $scope = $fluent->scope('user');

            expect($scope)->toBeAnInstanceOf(Fluent::class);
            expect($scope->toArray())->toBe(['name' => 'John', 'age' => 30]);
        });

        it('Doit récupérer tous les attributs avec all()', function (): void {
            $fluent = new Fluent(['name' => 'John', 'age' => 30, 'city' => 'Paris']);

            expect($fluent->all())->toBe(['name' => 'John', 'age' => 30, 'city' => 'Paris']);
            expect($fluent->all('name', 'city'))->toBe(['name' => 'John', 'city' => 'Paris']);
        });

        it('Doit récupérer les attributs avec getAttributes()', function (): void {
            $fluent = new Fluent(['name' => 'John']);

            expect($fluent->getAttributes())->toBe(['name' => 'John']);
        });
    });

    describe('ArrayAccess', function (): void {
        it('Doit implémenter ArrayAccess', function (): void {
            $fluent = new Fluent(['name' => 'John']);

            expect(isset($fluent['name']))->toBe(true);
            expect($fluent['name'])->toBe('John');

            $fluent['age'] = 30;
            expect($fluent['age'])->toBe(30);

            unset($fluent['name']);
            expect(isset($fluent['name']))->toBe(false);
        });
    });

    describe('Iterator', function (): void {
        xit('Doit être itérable', function (): void {
            $fluent = new Fluent(['a' => 1, 'b' => 2, 'c' => 3]);
            $result = [];

            foreach ($fluent as $key => $value) {
                $result[$key] = $value;
            }

            expect($result)->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
        });
    });
});
