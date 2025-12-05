<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\Data\DataTransfertObject;
use BlitzPHP\Utilities\Iterable\Collection;

use function Kahlan\expect;

describe('Utilities / Data / DataTransfertObject', function (): void {
    describe('Initialisation', function (): void {
        it('Doit créer un DTO avec des attributs', function (): void {
			$class = new class extends DataTransfertObject {
				public $name;
				public $age;
			};
            $dto = new $class(['name' => 'John', 'age' => 30]);
            expect($dto->toArray())->toBe(['name' => 'John', 'age' => 30]);
        });

        it('Doit gérer les propriétés publiques de la classe', function (): void {
            $testDTO = new class(['external' => 'value']) extends DataTransfertObject {
                public string $internal = 'default';
            };

            expect($testDTO->toArray())->toContain('default');
        });
    });

    describe('Sérialisation', function (): void {
        it('Doit filtrer avec only()', function (): void {
            $dto = new class(['name' => 'John', 'age' => 30, 'email' => 'john@test.com']) extends DataTransfertObject {
				public $name;
				public $age;
				public $email;
			};
            $filtered = $dto->only('name', 'email');

            expect($filtered->toArray())->toBe(['name' => 'John', 'email' => 'john@test.com']);
        });

        it('Doit exclure avec except()', function (): void {
            $dto = new class(['name' => 'John', 'age' => 30, 'email' => 'john@test.com']) extends DataTransfertObject {
				public $name;
				public $age;
				public $email;
			};
            $filtered = $dto->except('email');

            expect($filtered->toArray())->toBe(['name' => 'John', 'age' => 30]);
        });

        it('Doit formater les valeurs pour JSON', function (): void {
            $dto = new class(['date' => new DateTime('2023-01-01')]) extends DataTransfertObject {
				public $date;
			};
            $json = $dto->toJson();

            expect($json)->toMatch('/2023-01-01/');
        });
    });

    describe('Accesseurs magiques', function (): void {
        it('Doit accéder aux attributs via __get', function (): void {
            $dto = new DataTransfertObject(['name' => 'John']);
            expect($dto->name)->toBe('John');
        });

        it('Doit définir des attributs via __set', function (): void {
            $dto = new DataTransfertObject();
            $dto->name = 'John';

            expect($dto->name)->toBe('John');
        });
    });

    describe('Collections', function (): void {
        it('Doit créer un tableau de DTOs', function (): void {
            $data = [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25]
            ];

            $dtos = DataTransfertObject::arrayOf($data);

            expect($dtos)->toHaveLength(2);
            expect($dtos[0])->toBeAnInstanceOf(DataTransfertObject::class);
        });

        it('Doit créer une collection de DTOs', function (): void {
            $data = [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25]
            ];

            $collection = DataTransfertObject::collection($data);

            expect($collection)->toBeAnInstanceOf(Collection::class);
            expect($collection->count())->toBe(2);
        });
    });
});
