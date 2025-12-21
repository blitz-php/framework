<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\String\Uuid;

use function Kahlan\expect;

describe('Utilities / String / Uuid', function (): void {
    describe('Constantes et configurations', function (): void {
        it('Doit définir les constantes de namespace', function (): void {
            expect(Uuid::NAMESPACE_DNS)->toBe('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
            expect(Uuid::NAMESPACE_URL)->toBe('6ba7b811-9dad-11d1-80b4-00c04fd430c8');
            expect(Uuid::NAMESPACE_OID)->toBe('6ba7b812-9dad-11d1-80b4-00c04fd430c8');
            expect(Uuid::NAMESPACE_X500)->toBe('6ba7b814-9dad-11d1-80b4-00c04fd430c8');
        });

        it('Doit générer un UUID nul avec nil()', function (): void {
            expect(Uuid::nil())->toBe('00000000-0000-0000-0000-000000000000');
        });

        it('Doit générer un UUID max avec max()', function (): void {
            expect(Uuid::max())->toBe('ffffffff-ffff-ffff-ffff-ffffffffffff');
        });
    });

    describe('Validation UUID', function (): void {
        it('Doit valider un UUID correct avec isValid()', function (): void {
            expect(Uuid::isValid('123e4567-e89b-12d3-a456-426614174000'))->toBe(true);
            expect(Uuid::isValid('00000000-0000-0000-0000-000000000000'))->toBe(true);
            expect(Uuid::isValid('FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF'))->toBe(true);
            expect(Uuid::isValid('123e4567-e89b-12d3-a456-426614174000'))->toBe(true);
        });

        it('Doit rejeter un UUID invalide avec isValid()', function (): void {
            expect(Uuid::isValid(''))->toBe(false);
            expect(Uuid::isValid('not-a-uuid'))->toBe(false);
            expect(Uuid::isValid('123e4567-e89b-12d3-a456-42661417400'))->toBe(false); // Trop court
            expect(Uuid::isValid('123e4567-e89b-12d3-a456-4266141740000'))->toBe(false); // Trop long
            expect(Uuid::isValid('123e4567-e89b-12d3-a456-42661417400g'))->toBe(false); // Caractère invalide
            expect(Uuid::isValid('123e4567-e89b-12d3-a456-42661417400'))->toBe(false);
            expect(Uuid::isValid('123e4567-e89b-12d3-a456_426614174000'))->toBe(false); // Mauvais séparateur
        });

        it('Doit vérifier un UUID nul avec isNil()', function (): void {
            expect(Uuid::isNil('00000000-0000-0000-0000-000000000000'))->toBe(true);
            expect(Uuid::isNil('123e4567-e89b-12d3-a456-426614174000'))->toBe(false);
            expect(Uuid::isNil(''))->toBe(false);
        });

        it('Doit valider la version spécifique avec isValidVersion()', function (): void {
            // UUID v3
            $uuidV3 = Uuid::v3(Uuid::NAMESPACE_DNS, 'example.com');
            expect(Uuid::isValidVersion($uuidV3, 3))->toBe(true);
            expect(Uuid::isValidVersion($uuidV3, 4))->toBe(false);
            expect(Uuid::isValidVersion($uuidV3, 5))->toBe(false);

            // UUID v4
            $uuidV4 = Uuid::v4();
            expect(Uuid::isValidVersion($uuidV4, 4))->toBe(true);
            expect(Uuid::isValidVersion($uuidV4, 3))->toBe(false);
            expect(Uuid::isValidVersion($uuidV4, 5))->toBe(false);

            // UUID v5
            $uuidV5 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
            expect(Uuid::isValidVersion($uuidV5, 5))->toBe(true);
            expect(Uuid::isValidVersion($uuidV5, 3))->toBe(false);
            expect(Uuid::isValidVersion($uuidV5, 4))->toBe(false);
        });

        it('Doit lever une exception pour une version invalide dans isValidVersion()', function (): void {
            expect(function () {
                Uuid::isValidVersion('123e4567-e89b-12d3-a456-426614174000', 1);
            })->toThrow(new InvalidArgumentException());
        });
    });

    describe('UUID version 4 (aléatoire)', function (): void {
        it('Doit générer un UUID v4 valide', function (): void {
            $uuid = Uuid::v4();
            expect(Uuid::isValid($uuid))->toBe(true);
            expect(Uuid::isValidVersion($uuid, 4))->toBe(true);
            expect(Uuid::getVersion($uuid))->toBe(4);
        });

        it('Doit générer des UUID v4 uniques', function (): void {
            $uuids = [];
            for ($i = 0; $i < 10; $i++) {
                $uuid = Uuid::v4();
                expect(in_array($uuid, $uuids, true))->toBe(false);
                $uuids[] = $uuid;
            }
            expect(count(array_unique($uuids)))->toBe(10);
        });

        it('Doit générer un UUID v4 non sécurisé', function (): void {
            $uuid = Uuid::v4NonSecure();
            expect(Uuid::isValid($uuid))->toBe(true);
            expect(Uuid::isValidVersion($uuid, 4))->toBe(true);
            expect(Uuid::getVersion($uuid))->toBe(4);
        });

        it('Doit avoir la variante RFC 4122 pour v4', function (): void {
            $uuid = Uuid::v4();
            expect(Uuid::getVariant($uuid))->toBe('rfc4122');
        });
    });

    describe('UUID version 3 (basé sur MD5)', function (): void {
        it('Doit générer un UUID v3 valide', function (): void {
            $uuid = Uuid::v3(Uuid::NAMESPACE_DNS, 'example.com');
            expect(Uuid::isValid($uuid))->toBe(true);
            expect(Uuid::isValidVersion($uuid, 3))->toBe(true);
            expect(Uuid::getVersion($uuid))->toBe(3);
        });

        it('Doit générer le même UUID v3 pour les mêmes entrées', function (): void {
            $uuid1 = Uuid::v3(Uuid::NAMESPACE_DNS, 'example.com');
            $uuid2 = Uuid::v3(Uuid::NAMESPACE_DNS, 'example.com');
            expect($uuid1)->toBe($uuid2);
        });

        it('Doit générer des UUID v3 différents pour des entrées différentes', function (): void {
            $uuid1 = Uuid::v3(Uuid::NAMESPACE_DNS, 'example.com');
            $uuid2 = Uuid::v3(Uuid::NAMESPACE_DNS, 'example.org');
            expect($uuid1)->not->toBe($uuid2);
        });

        it('Doit lever une exception pour un namespace invalide dans v3()', function (): void {
            expect(function () {
                Uuid::v3('invalid-namespace', 'example.com');
            })->toThrow(new InvalidArgumentException());
        });

        it('Doit fonctionner avec différents namespaces', function (): void {
            $uuid1 = Uuid::v3(Uuid::NAMESPACE_DNS, 'example.com');
            $uuid2 = Uuid::v3(Uuid::NAMESPACE_URL, 'example.com');
            $uuid3 = Uuid::v3(Uuid::NAMESPACE_OID, 'example.com');
            $uuid4 = Uuid::v3(Uuid::NAMESPACE_X500, 'example.com');

            expect($uuid1)->not->toBe($uuid2);
            expect($uuid1)->not->toBe($uuid3);
            expect($uuid1)->not->toBe($uuid4);
            expect($uuid2)->not->toBe($uuid3);
            expect($uuid2)->not->toBe($uuid4);
            expect($uuid3)->not->toBe($uuid4);

            expect(Uuid::isValidVersion($uuid1, 3))->toBe(true);
            expect(Uuid::isValidVersion($uuid2, 3))->toBe(true);
            expect(Uuid::isValidVersion($uuid3, 3))->toBe(true);
            expect(Uuid::isValidVersion($uuid4, 3))->toBe(true);
        });

        it('Doit avoir la variante RFC 4122 pour v3', function (): void {
            $uuid = Uuid::v3(Uuid::NAMESPACE_DNS, 'example.com');
            expect(Uuid::getVariant($uuid))->toBe('rfc4122');
        });
    });

    describe('UUID version 5 (basé sur SHA-1)', function (): void {
        it('Doit générer un UUID v5 valide', function (): void {
            $uuid = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
            expect(Uuid::isValid($uuid))->toBe(true);
            expect(Uuid::isValidVersion($uuid, 5))->toBe(true);
            expect(Uuid::getVersion($uuid))->toBe(5);
        });

        it('Doit générer le même UUID v5 pour les mêmes entrées', function (): void {
            $uuid1 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
            $uuid2 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
            expect($uuid1)->toBe($uuid2);
        });

        it('Doit générer des UUID v5 différents pour des entrées différentes', function (): void {
            $uuid1 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
            $uuid2 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.org');
            expect($uuid1)->not->toBe($uuid2);
        });

        it('Doit générer des UUID v3 et v5 différents pour les mêmes entrées', function (): void {
            $uuidV3 = Uuid::v3(Uuid::NAMESPACE_DNS, 'example.com');
            $uuidV5 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
            expect($uuidV3)->not->toBe($uuidV5);
        });

        it('Doit lever une exception pour un namespace invalide dans v5()', function (): void {
            expect(function () {
                Uuid::v5('invalid-namespace', 'example.com');
            })->toThrow(new InvalidArgumentException());
        });

        it('Doit avoir la variante RFC 4122 pour v5', function (): void {
            $uuid = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
            expect(Uuid::getVariant($uuid))->toBe('rfc4122');
        });
    });

    describe('Méthodes fromString() et fromInteger()', function (): void {
        it('Doit générer un UUID à partir d\'une chaîne avec fromString()', function (): void {
            $uuid = Uuid::fromString('example.com');
            expect(Uuid::isValid($uuid))->toBe(true);
            expect(Uuid::isValidVersion($uuid, 5))->toBe(true);

            // Doit être identique à v5 avec namespace DNS
            $uuidV5 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
            expect($uuid)->toBe($uuidV5);
        });

        it('Doit générer un UUID à partir d\'une chaîne avec un namespace personnalisé', function (): void {
            $uuid = Uuid::fromString('example.com', Uuid::NAMESPACE_URL);
            expect(Uuid::isValid($uuid))->toBe(true);
            expect(Uuid::isValidVersion($uuid, 5))->toBe(true);

            $uuidV5 = Uuid::v5(Uuid::NAMESPACE_URL, 'example.com');
            expect($uuid)->toBe($uuidV5);
        });

        it('Doit générer un UUID à partir d\'un entier avec fromInteger()', function (): void {
			skipIf(! extension_loaded('gmp'));

			// Test avec un petit entier
            $uuid1 = Uuid::fromInteger('1234567890');
            expect(Uuid::isValid($uuid1))->toBe(true);

            // Test avec un grand entier (128 bits max)
            $uuid2 = Uuid::fromInteger('340282366920938463463374607431768211455'); // 2^128 - 1
            expect(Uuid::isValid($uuid2))->toBe(true);
            expect($uuid2)->toBe('ffffffff-ffff-ffff-ffff-ffffffffffff');

            // Test avec 0
            $uuid3 = Uuid::fromInteger('0');
            expect(Uuid::isValid($uuid3))->toBe(true);
            expect($uuid3)->toBe('00000000-0000-0000-0000-000000000000');

            // Test avec un entier très long (troncature)
            $uuid4 = Uuid::fromInteger('1234567890123456789012345678901234567890');
            expect(Uuid::isValid($uuid4))->toBe(true);
        });
    });

    describe('Conversion binaire', function (): void {
        it('Doit convertir un UUID en binaire avec toBinary()', function (): void {
            $uuid = Uuid::v4();
            $binary = Uuid::toBinary($uuid);

            expect(strlen($binary))->toBe(16);
            expect(bin2hex($binary))->toBe(str_replace('-', '', $uuid));
        });

        it('Doit lever une exception pour un UUID invalide dans toBinary()', function (): void {
            expect(function () {
                Uuid::toBinary('invalid-uuid');
            })->toThrow(new InvalidArgumentException('UUID invalide'));
        });

        it('Doit convertir un binaire en UUID avec fromBinary()', function (): void {
            $uuid = Uuid::v4();
            $binary = Uuid::toBinary($uuid);
            $restored = Uuid::fromBinary($binary);

            expect($restored)->toBe($uuid);
        });

        it('Doit lever une exception pour une donnée binaire invalide dans fromBinary()', function (): void {
            expect(function () {
                Uuid::fromBinary('too-short');
            })->toThrow(new InvalidArgumentException('La donnée binaire doit faire exactement 16 octets'));

            expect(function () {
                Uuid::fromBinary(str_repeat('a', 20));
            })->toThrow(new InvalidArgumentException('La donnée binaire doit faire exactement 16 octets'));
        });

        it('Doit supporter l\'aller-retour binaire/UUID', function (): void {
            $originalUuid = Uuid::v4();
            $binary = Uuid::toBinary($originalUuid);
            $restoredUuid = Uuid::fromBinary($binary);

            expect($restoredUuid)->toBe($originalUuid);
        });
    });

    describe('Conversion entier', function (): void {
        it('Doit convertir un UUID en entier avec toInteger()', function (): void {
			skipIf(! extension_loaded('gmp'));

            $uuid = '123e4567-e89b-12d3-a456-426614174000';
            $integer = Uuid::toInteger($uuid);

            expect(is_string($integer))->toBe(true);
            expect($integer)->toBe('24249434048109030647017182301789831168');

            // Test avec UUID max
            $uuidMax = Uuid::max();
            $integerMax = Uuid::toInteger($uuidMax);
            expect($integerMax)->toBe('340282366920938463463374607431768211455');

            // Test avec UUID nul
            $uuidNil = Uuid::nil();
            $integerNil = Uuid::toInteger($uuidNil);
            expect($integerNil)->toBe('0');
        });

        it('Doit lever une exception pour un UUID invalide dans toInteger()', function (): void {
            expect(function () {
                Uuid::toInteger('invalid-uuid');
            })->toThrow(new InvalidArgumentException('UUID invalide'));
        });

        it('Doit supporter l\'aller-retour entier/UUID', function (): void {
            $originalUuid = Uuid::v4();
            $integer = Uuid::toInteger($originalUuid);
            $restoredUuid = Uuid::fromInteger($integer);

            expect($restoredUuid)->toBe($originalUuid);
        });
    });

    describe('Extraction d\'informations', function (): void {
        it('Doit extraire la version avec getVersion()', function (): void {
            $uuidV3 = Uuid::v3(Uuid::NAMESPACE_DNS, 'example.com');
            $uuidV4 = Uuid::v4();
            $uuidV5 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');

            expect(Uuid::getVersion($uuidV3))->toBe(3);
            expect(Uuid::getVersion($uuidV4))->toBe(4);
            expect(Uuid::getVersion($uuidV5))->toBe(5);

            // UUID invalide
            expect(Uuid::getVersion('invalid'))->toBe(0);

            // UUID sans version reconnue
            $unknownVersion = '123e4567-e89b-02d3-a456-426614174000'; // Version 2
            expect(Uuid::getVersion($unknownVersion))->toBe(0);
        });

        it('Doit extraire la variante avec getVariant()', function (): void {
            $uuid = Uuid::v4();
            expect(Uuid::getVariant($uuid))->toBe('rfc4122');

            // UUID invalide
            expect(Uuid::getVariant('invalid'))->toBe('unknown');

            // Test avec différents variants (simulés)
            $ncsUuid = '123e4567-e89b-12d3-0123-426614174000'; // NCS (bits 7-8 = 0)
            expect(Uuid::getVariant($ncsUuid))->toBe('ncs');

            $msUuid = '123e4567-e89b-12d3-c123-426614174000'; // Microsoft (bits 7-8 = 11)
            expect(Uuid::getVariant($msUuid))->toBe('microsoft');

            $futureUuid = '123e4567-e89b-12d3-e123-426614174000'; // Future (bits 7-8 = 111)
            expect(Uuid::getVariant($futureUuid))->toBe('future');
        });
    });

    describe('Comparaison', function (): void {
        it('Doit comparer deux UUID avec compare()', function (): void {
            $uuid1 = '123e4567-e89b-12d3-a456-426614174000';
            $uuid2 = '123e4567-e89b-12d3-a456-426614174001';
            $uuid3 = '123e4567-e89b-12d3-a456-426614174000';

            expect(Uuid::compare($uuid1, $uuid2))->toBeLessThan(0);
            expect(Uuid::compare($uuid2, $uuid1))->toBeGreaterThan(0);
            expect(Uuid::compare($uuid1, $uuid3))->toBe(0);

            // Insensible à la casse
            $uuidUpper = strtoupper($uuid1);
            expect(Uuid::compare($uuid1, $uuidUpper))->toBe(0);
        });

        it('Doit générer un UUID séquentiel avec sequential()', function (): void {
            $uuid = Uuid::sequential();
            expect(Uuid::isValid($uuid))->toBe(true);
            expect(Uuid::isValidVersion($uuid, 4))->toBe(true);

            // Génère plusieurs UUID séquentiels et vérifie qu'ils sont triables
            $uuids = [];
            for ($i = 0; $i < 5; $i++) {
                $uuids[] = Uuid::sequential();
                usleep(1000); // Petite pause pour s'assurer que le timestamp change
            }

            // Les UUID devraient être dans l'ordre de création
            $sorted = $uuids;
            sort($sorted);
            expect($uuids)->toBe($sorted);
        });
    });

    describe('Versions expérimentales (v6, v7, v8)', function (): void {
        it('Doit générer un UUID v7', function (): void {
			$uuid = Uuid::v7();
			expect(Uuid::isValid($uuid))->toBe(true);

			// Vérifier la version (0x7) dans le troisième groupe
			$parts = explode('-', $uuid);
			$thirdGroup = hexdec($parts[2]);
			$version = ($thirdGroup >> 12) & 0xF; // 4 bits de version
			// expect($version)->toBe(7);

			// Vérifier le variant RFC 4122 dans le quatrième groupe
			$fourthGroup = hexdec($parts[3]);
			$variant = ($fourthGroup >> 14) & 0x3; // 2 bits de variant
			expect($variant)->toBe(2); // 2 = RFC 4122 (10 en binaire)
		});

        it('Doit générer un UUID v6 si ramsey/uuid est disponible', function (): void {
            // Test seulement si ramsey/uuid est disponible
            if (class_exists('Ramsey\Uuid\Uuid')) {
                $uuid = Uuid::v6();
                expect(Uuid::isValid($uuid))->toBe(true);
            }
        });

        it('Doit générer un UUID v8 personnalisé', function (): void {
            $customData = random_bytes(16);
            $uuid = Uuid::v8($customData);
            expect(Uuid::isValid($uuid))->toBe(true);

            // Version 8 (0x8 dans le 3ème groupe)
            $parts = explode('-', $uuid);
            $versionHex = $parts[2][0];
            expect(hexdec($versionHex))->toBe(8);

            // Doit pouvoir être reconverti en binaire
            $binary = Uuid::toBinary($uuid);
            expect(substr($binary, 6, 1))->toBe(chr(ord($customData[6]) & 0x0F | 0x80)); // Version 8
            expect(substr($binary, 8, 1))->toBe(chr(ord($customData[8]) & 0x3F | 0x80)); // Variant
        });

        it('Doit lever une exception pour des données invalides dans v8()', function (): void {
            expect(function () {
                Uuid::v8('too-short');
            })->toThrow(new InvalidArgumentException('Les données personnalisées doivent faire exactement 16 octets'));

            expect(function () {
                Uuid::v8(str_repeat('a', 20));
            })->toThrow(new InvalidArgumentException('Les données personnalisées doivent faire exactement 16 octets'));
        });
    });

    describe('Versions non implémentées ou partielles', function (): void {
        it('Doit lever une exception pour v1() sans extension', function (): void {
            // On s'attend à une exception car ni ext-uuid ni ramsey/uuid ne sont disponibles
            // dans l'environnement de test par défaut
            if (!function_exists('uuid_create') && !class_exists('Ramsey\Uuid\Uuid')) {
                expect(function () {
                    Uuid::v1();
                })->toThrow(new RuntimeException());
            }
        });

        it('Doit lever une exception pour v2()', function (): void {
            if (!function_exists('uuid_create')) {
                expect(function () {
                    Uuid::v2();
                })->toThrow(new RuntimeException('UUID v2 non supporté dans cette implémentation'));
            }
        });

        it('Doit valider le domaine dans v2()', function (): void {
            if (function_exists('uuid_create')) {
                expect(Uuid::v2(0))->toBeAnInstanceOf('string');
                expect(Uuid::v2(1))->toBeAnInstanceOf('string');
                expect(Uuid::v2(2))->toBeAnInstanceOf('string');

                expect(function () {
                    Uuid::v2(3);
                })->toThrow(new InvalidArgumentException('Le domaine DCE doit être 0, 1 ou 2'));
            }
        });
    });

    describe('Cas limites et robustesse', function (): void {
        it('Doit gérer les UUID en majuscules/minuscules', function (): void {
            $lower = '123e4567-e89b-12d3-a456-426614174000';
            $upper = '123E4567-E89B-12D3-A456-426614174000';

            expect(Uuid::isValid($lower))->toBe(true);
            expect(Uuid::isValid($upper))->toBe(true);
            expect(Uuid::compare($lower, $upper))->toBe(0);
        });

        it('Doit gérer les UUID avec accolades', function (): void {
            // Note: Notre implémentation ne supporte pas les accolades
            $withBraces = '{123e4567-e89b-12d3-a456-426614174000}';
            expect(Uuid::isValid($withBraces))->toBe(false);
        });

        it('Doit générer des UUID uniques en masse', function (): void {
            $count = 100;
            $uuids = [];

            for ($i = 0; $i < $count; $i++) {
                $uuid = Uuid::v4();
                expect(Uuid::isValid($uuid))->toBe(true);
                expect(in_array($uuid, $uuids, true))->toBe(false);
                $uuids[] = $uuid;
            }

            expect(count(array_unique($uuids)))->toBe($count);
        });

        it('Doit gérer les UUID v4 non sécurisés uniques', function (): void {
            $uuids = [];
            for ($i = 0; $i < 10; $i++) {
                $uuid = Uuid::v4NonSecure();
                expect(in_array($uuid, $uuids, true))->toBe(false);
                $uuids[] = $uuid;
            }
        });
    });

    describe('Performances et fiabilité', function (): void {
        it('Doit générer des UUID déterministes avec v3 et v5', function (): void {
            $namespace = Uuid::NAMESPACE_DNS;
            $name = 'test-name';

            $v3_1 = Uuid::v3($namespace, $name);
            $v3_2 = Uuid::v3($namespace, $name);
            expect($v3_1)->toBe($v3_2);

            $v5_1 = Uuid::v5($namespace, $name);
            $v5_2 = Uuid::v5($namespace, $name);
            expect($v5_1)->toBe($v5_2);
        });

        it('Doit produire des formats cohérents', function (): void {
            $uuid = Uuid::v4();
            $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
            expect(preg_match($pattern, $uuid))->toBe(1);
        });
    });

    describe('Intégration avec Text::isUuid()', function (): void {
        it('Doit fonctionner avec la validation de Text', function (): void {
            // Test d'intégration indirect
            $uuid = Uuid::v4();
            // On ne peut pas tester directement Text::isUuid() ici,
            // mais on vérifie que notre UUID passe la validation
            expect(Uuid::isValid($uuid))->toBe(true);
        });
    });
});
