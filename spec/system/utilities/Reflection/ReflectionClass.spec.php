<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\Reflection\ReflectionClass;

use function Kahlan\expect;

describe('Utilities / Reflection / ReflectionClass', function (): void {


    // Traits de test
    trait TestTrait1 {
        public function traitMethod1(): string {
            return 'trait1_method';
        }
    }

    trait TestTrait2 {
        public function traitMethod2(): string {
            return 'trait2_method';
        }
    }

    // Classe abstraite de test
    abstract class AbstractTestClass {
        abstract public function abstractMethod(): string;

        public function concreteMethod(): string {
            return 'concrete';
        }
    }

    // Attribut de test
    #[Attribute]
    class TestAttribute {
        public function __construct(public ?string $value = null) {}
    }

    // Classes de test pour les différents scénarios
    $testClasses = [
        'simple' => new class {
            public string $publicProp = 'public_value';
            protected string $protectedProp = 'protected_value';
            private string $privateProp = 'private_value';
            public static string $staticProp = 'static_value';
            private static string $privateStaticProp = 'private_static_value';

            public function publicMethod(): string {
                return 'public_method';
            }

            protected function protectedMethod(): string {
                return 'protected_method';
            }

            private function privateMethod(): string {
                return 'private_method';
            }

            public static function staticMethod(): string {
                return 'static_method';
            }

            private static function privateStaticMethod(): string {
                return 'private_static_method';
            }

            public function methodWithArgs(string $name, int $age = 30): string {
                return "{$name}_{$age}";
            }
        },

        'withTraits' => new class {
            use TestTrait1, TestTrait2;

            public function ownMethod(): string {
                return 'own_method';
            }
        },

        'singleton' => new class {
            private static ?self $instance = null;

            public static function getInstance(): self {
                if (self::$instance === null) {
                    self::$instance = new self();
                }
                return self::$instance;
            }
        },

        'abstractClass' => new class extends AbstractTestClass {
            public function abstractMethod(): string {
                return 'implemented';
            }

            public final function finalMethod(): string {
                return 'final';
            }
        },

        'withAttributes' => new class {
            #[TestAttribute]
            public string $attributedProp = 'test';

            #[TestAttribute('method')]
            public function attributedMethod(): string {
                return 'attributed';
            }
        }
    ];

    describe('Initialisation et Factory', function () use ($testClasses): void {
        it('Doit créer une instance avec le constructeur', function () use ($testClasses): void {
            $reflection = new ReflectionClass($testClasses['simple']);
            expect($reflection)->toBeAnInstanceOf(ReflectionClass::class);
        });

        it('Doit créer une instance avec la méthode factory make()', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            expect($reflection)->toBeAnInstanceOf(ReflectionClass::class);
        });

        it('Doit créer une instance à partir d\'un nom de classe', function () use($testClasses): void {
            $className = $testClasses['simple']::class;
            $reflection = ReflectionClass::make($className);
            expect($reflection->getName())->toBe($className);
        });
    });

    describe('Instanciation de classes', function (): void {
        it('Doit instancier une classe avec un constructeur public', function (): void {
            $class = new class {
                public function __construct(public string $name = 'default') {}
            };

            $reflection = ReflectionClass::make($class::class);
            $instance = $reflection->newInstance('John');

            expect($instance)->toBeAnInstanceOf($class::class);
            expect($instance->name)->toBe('John');
        });
        it('Doit instancier une classe sans constructeur', function (): void {
            $class = new class {
                public string $name = 'default';
            };

            $reflection = ReflectionClass::make($class::class);
            $instance = $reflection->newInstance();

            expect($instance->name)->toBe('default');
        });
    });

    describe('Vérifications d\'existence', function () use ($testClasses): void {
        it('Doit vérifier l\'existence d\'une méthode', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);

            expect($reflection->hasMethod('publicMethod'))->toBe(true);
            expect($reflection->hasMethod('protectedMethod'))->toBe(true);
            expect($reflection->hasMethod('privateMethod'))->toBe(true);
            expect($reflection->hasMethod('nonExistentMethod'))->toBe(false);
        });

        it('Doit vérifier l\'existence d\'une propriété', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);

            expect($reflection->hasProperty('publicProp'))->toBe(true);
            expect($reflection->hasProperty('protectedProp'))->toBe(true);
            expect($reflection->hasProperty('privateProp'))->toBe(true);
            expect($reflection->hasProperty('nonExistentProp'))->toBe(false);
        });

        it('Doit vérifier si une propriété a une valeur spécifique', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);

            expect($reflection->hasValue('publicProp', 'public_value'))->toBe(true);
            expect($reflection->hasValue('publicProp', 'wrong_value'))->toBe(false);
            expect($reflection->hasValue('nonExistentProp', 'any'))->toBe(false);
        });
    });

    describe('Accès aux méthodes', function () use ($testClasses): void {
        it('Doit récupérer une méthode rendue accessible', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $method = $reflection->getMethod('privateMethod');

            expect($method)->toBeAnInstanceOf(ReflectionMethod::class);
            expect($method->isPublic())->toBe(false);
        });

        it('Doit invoquer une méthode privée', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $result = $reflection->invoke('privateMethod');

            expect($result)->toBe('private_method');
        });

        it('Doit invoquer une méthode avec des arguments', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $result = $reflection->invoke('methodWithArgs', 'John', 25);

            expect($result)->toBe('John_25');
        });

        it('Doit invoquer une méthode statique', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $result = $reflection->invoke('staticMethod');

            expect($result)->toBe('static_method');
        });

        it('Doit filtrer les méthodes avec AND bitwise', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $methods = $reflection->getMethods(
                ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC,
                true
            );

            expect(count($methods))->toBe(1);
            expect($methods[0]->getName())->toBe('staticMethod');
        });

        it('Doit vérifier si une méthode est abstraite', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['abstractClass']);

            expect($reflection->isAbstractMethod('abstractMethod'))->toBe(false);
            expect($reflection->isAbstractMethod('concreteMethod'))->toBe(false);
        });

        it('Doit vérifier si une méthode est finale', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['abstractClass']);

            expect($reflection->isFinalMethod('finalMethod'))->toBe(true);
            expect($reflection->isFinalMethod('concreteMethod'))->toBe(false);
        });

        it('Doit extraire les types de paramètres d\'une méthode', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $types = $reflection->getMethodParameterTypes('methodWithArgs');

            expect($types)->toBe([
                'name' => 'string',
                'age' => 'int'
            ]);
        });
    });

    describe('Accès aux propriétés', function () use ($testClasses): void {
        it('Doit récupérer une propriété rendue accessible', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $property = $reflection->getProperty('privateProp');

            expect($property)->toBeAnInstanceOf(ReflectionProperty::class);
            expect($property->isPublic())->toBe(false);
        });

        it('Doit lire la valeur d\'une propriété privée', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $value = $reflection->getValue('privateProp');

            expect($value)->toBe('private_value');
        });

        it('Doit lire la valeur d\'une propriété statique', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']::class);
            $value = $reflection->getValue('staticProp');

            expect($value)->toBe('static_value');
        });

        it('Doit modifier la valeur d\'une propriété privée', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $oldValue  = $reflection->getValue('privateProp');

			$reflection->setValue('privateProp', 'new_private_value');

            expect($reflection->getValue('privateProp'))->toBe('new_private_value');
			$reflection->setValue('privateProp', $oldValue);
        });

        it('Doit modifier la valeur d\'une propriété statique', function (): void {
            $class = new class {
                private static string $staticProp = 'old_value';
            };

            $reflection = ReflectionClass::make($class::class);
            $reflection->setValue('staticProp', 'new_static_value');

            expect($reflection->getValue('staticProp'))->toBe('new_static_value');
        });

        it('Doit lire toutes les valeurs des propriétés', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $values = $reflection->getValues();

            expect($values)->toContain('public_value');
            expect($values)->toContain('protected_value');
            expect($values)->toContain('private_value');
        });

        it('Doit modifier plusieurs propriétés à la fois', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
			$values = $reflection->getValues();
            $reflection->setValues([
                'publicProp' => 'new_public',
                'privateProp' => 'new_private'
            ]);

            expect($reflection->getValue('publicProp'))->toBe('new_public');
            expect($reflection->getValue('privateProp'))->toBe('new_private');
			$reflection->setValues($values);
        });

        it('Doit filtrer les propriétés avec AND bitwise', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);
            $properties = $reflection->getProperties(
                ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_STATIC,
                true
            );

            expect(count($properties))->toBe(1);
            expect($properties[0]->getName())->toBe('privateStaticProp');
        });
    });

    describe('Types et vérifications de propriétés', function (): void {
        it('Doit vérifier si une propriété a un type déclaré', function (): void {
            $class = new class {
                public string $typedProp;
                public $untypedProp;
            };

            $reflection = ReflectionClass::make($class);

            expect($reflection->hasPropertyType('typedProp'))->toBe(true);
            expect($reflection->hasPropertyType('untypedProp'))->toBe(false);
        });
        it('Doit récupérer le type d\'une propriété', function (): void {
            $class = new class {
                public string $stringProp;
                public ?int $nullableProp = null;
            };

            $reflection = ReflectionClass::make($class);

            expect($reflection->getPropertyTypeName('stringProp'))->toBe('string');
            expect($reflection->getPropertyTypeName('nullableProp'))->toBe('int');
        });
        it('Doit vérifier si une propriété est readonly (PHP 8.1+)', function (): void {
            $class = new class {
                public readonly string $readonlyProp;
                public string $regularProp;
            };

            $reflection = ReflectionClass::make($class);

            expect($reflection->isReadonlyProperty('readonlyProp'))->toBe(true);
            expect($reflection->isReadonlyProperty('regularProp'))->toBe(false);
        });
        it('Doit vérifier si une propriété est modifiable', function (): void {
            $class = new class {
                public string $publicProp;
                protected string $protectedProp;
                private string $privateProp;
            };

            $reflection = ReflectionClass::make($class);

            expect($reflection->isWritableProperty('publicProp'))->toBe(true);
            expect($reflection->isWritableProperty('protectedProp'))->toBe(true);
            expect($reflection->isWritableProperty('privateProp'))->toBe(false);
        });
    });

    describe('Annotations et DocBlocks', function (): void {
        it('Doit récupérer les annotations d\'une propriété', function (): void {
            $class = new class {
                /**
				 * @var string Le nom de l'utilisateur
				 */
                public string $name;

                /** @var int @min(18) @max(100) */
                public int $age;
            };

            $reflection = ReflectionClass::make($class);
            $annotations = $reflection->getPropertyAnnotations('age');

            expect($annotations)->toContainKey('var');
            expect($annotations['var'])->toBe('int');
        });
        it('Doit récupérer les annotations d\'une méthode', function (): void {
            $class = new class {
                /**
                 * @param string $name Le nom à traiter
                 * @return string Le nom formaté
                 * @throws InvalidArgumentException
                 */
                public function process(string $name): string {
                    return strtoupper($name);
                }
            };

            $reflection = ReflectionClass::make($class);
            $annotations = $reflection->getMethodAnnotations('process');

            expect($annotations)->toContainKeys(['param', 'return', 'throws']);
        });
    });
	describe('Annotations multi-lignes', function (): void {
		it('Doit parser les annotations sur plusieurs lignes', function (): void {
			$class = new class {
				/**
				 * @var string
				 *   Le nom complet de lutilisateur
				 *   avec plusieurs lignes de description
				 */
				public string $name;

				/**
				 * @param string $first
				 *   Le prénom de la personne
				 * @param string $last
				 *   Le nom de famille
				 * @return string Le nom complet formaté
				 */
				public function formatName(string $first, string $last): string {
					return "$first $last";
				}
			};

			$reflection = ReflectionClass::make($class);
			$propAnnotations = $reflection->getPropertyAnnotations('name');
			$methodAnnotations = $reflection->getMethodAnnotations('formatName');

			expect($propAnnotations['var'])->toBe('string');
			expect($methodAnnotations['param'])->toBeAn('array');
			expect(count($methodAnnotations['param']))->toBe(2);
			expect($methodAnnotations['return'])->toBe('string');
		});

		it('Doit gérer les annotations complexes multi-lignes', function (): void {
			$class = new class {
				/**
				 * @OA\Get(
				 *     path="/api/users/{id}",
				 *     summary="Get user by ID",
				 *     @OA\Parameter(
				 *         name="id",
				 *         in="path",
				 *         required=true,
				 *         @OA\Schema(type="integer")
				 *     ),
				 *     @OA\Response(
				 *         response=200,
				 *         description="User found"
				 *     )
				 * )
				 */
				public function getUser(int $id): array {
					return [];
				}
			};

			$reflection = ReflectionClass::make($class);
			$annotations = $reflection->getMethodAnnotations('getUser');

			expect($annotations)->toContainKey('OA\Get');
			// Le contenu devrait inclure toutes les lignes
			expect($annotations['OA\Get'])->toContain('path="/api/users/{id}"');
			expect($annotations['OA\Get'])->toContain('summary="Get user by ID"');
		});
	});

    describe('Attributs PHP 8+', function () use ($testClasses): void {
        it('Doit récupérer les attributs d\'une classe', function (): void {
            $class = new #[TestAttribute('class')] class {
                #[TestAttribute('method')]
                public function testMethod(): void {}
            };
            $reflection = ReflectionClass::make($class);
            $attributes = $reflection->getClassAttributes();
            expect(count($attributes))->toBe(1);
            expect($attributes[0]->getName())->toBe('TestAttribute');
        });

        it('Doit récupérer les attributs d\'une propriété', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['withAttributes']);
            $attributes = $reflection->getPropertyAttributes('attributedProp');

            expect(count($attributes))->toBe(1);
            expect($attributes[0]->getName())->toBe('TestAttribute');
        });

        it('Doit récupérer les attributs d\'une méthode', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['withAttributes']);
            $attributes = $reflection->getMethodAttributes('attributedMethod');

            expect(count($attributes))->toBe(1);
            expect($attributes[0]->getName())->toBe('TestAttribute');
        });
    });

    describe('Hiérarchie et relations de classes', function () use ($testClasses): void {
        it('Doit récupérer les traits utilisés', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['withTraits']);
            $traits = $reflection->getUsedTraits();

            expect($traits)->toContain('TestTrait1');
            expect($traits)->toContain('TestTrait2');
        });

        it('Doit récupérer les noms des interfaces implémentées', function (): void {
            $class = new class implements Countable, IteratorAggregate {
                public function count(): int { return 0; }
                public function getIterator(): Traversable { yield; }
            };

            $reflection = ReflectionClass::make($class);
            $interfaces = $reflection->getInterfaceNames();

            expect($interfaces)->toContain('Countable');
            expect($interfaces)->toContain('IteratorAggregate');
        });

        it('Doit récupérer le nom de la classe parente', function (): void {
            $childClass = new class extends Exception {};

            $reflection = ReflectionClass::make($childClass);
            $parentName = $reflection->getParentClassName();

            expect($parentName)->toBe(Exception::class);
        });

        it('Doit détecter un Singleton', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['singleton']);
            expect($reflection->isSingleton())->toBe(true);
        });

        it('Doit ne pas détecter un Singleton si la méthode n\'est pas statique', function (): void {
            $class = new class {
                public function getInstance(): self {
                    return new self();
                }
            };

            $reflection = ReflectionClass::make($class);
            expect($reflection->isSingleton())->toBe(false);
        });
    });

    describe('Utilitaires et opérations avancées', function () use ($testClasses): void {
        it('Doit cloner un objet avec ses propriétés privées', function () use ($testClasses): void {
            $original = $testClasses['simple'];
            $clone = ReflectionClass::cloneWithPrivateProperties($original);

            expect($clone)->not->toBe($original);

            $reflection = ReflectionClass::make($clone);
            expect($reflection->getValue('privateProp'))->toBe('private_value');
        });

        it('Doit réinitialiser les propriétés statiques', function (): void {
            $class = new class {
                public static string $prop1 = 'default1';
                public static ?string $prop2 = 'default2';
                public static ?string $prop3 = null;
            };

            // Modifier les valeurs
            $class::$prop1 = 'modified1';
            $class::$prop2 = 'modified2';
            $class::$prop3 = 'modified3';

            $reflection = ReflectionClass::make($class::class);
            $reflection->resetStaticProperties();

            expect($class::$prop1)->toBe('default1');
            expect($class::$prop2)->toBe('default2');
            expect($class::$prop3)->toBe(null);
        });

        it('Doit gérer le cache des méthodes et propriétés', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);

            // Premier appel - doit être mis en cache
            $method1 = $reflection->getMethod('publicMethod');
            $property1 = $reflection->getProperty('publicProp');

            // Deuxième appel - doit utiliser le cache
            $method2 = $reflection->getMethod('publicMethod');
            $property2 = $reflection->getProperty('publicProp');

            expect($method2)->toBe($method1); // Même instance (cache)
            expect($property2)->toBe($property1); // Même instance (cache)
        });
    });

    describe('Gestion des erreurs', function () use ($testClasses): void {
        it('Doit lever une exception pour une méthode inexistante', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);

            expect(function() use ($reflection): void {
                $reflection->invoke('nonExistentMethod');
            })->toThrow(new ReflectionException());
        });

        it('Doit lever une exception pour une propriété inexistante', function () use ($testClasses): void {
            $reflection = ReflectionClass::make($testClasses['simple']);

            expect(function() use ($reflection): void {
                $reflection->getValue('nonExistentProp');
            })->toThrow(new ReflectionException());
        });

        it('Doit gérer les classes inexistantes', function (): void {
            expect(function(): void {
                ReflectionClass::make('NonExistentClass');
            })->toThrow(new ReflectionException(code: -1));
        });
    });
});
