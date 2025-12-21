<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was was distributed with this source code.
 */

use BlitzPHP\Utilities\Jwt;

use function Kahlan\expect;

describe('Utilities / Jwt', function (): void {
    beforeAll(function (): void {
        // Nettoyer l'instance singleton avant chaque test
        $reflection = new ReflectionClass(Jwt::class);
        $property = $reflection->getProperty('_instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    });

    describe('Instance unique', function (): void {
        it('Doit retourner la même instance avec instance()', function (): void {
            $instance1 = Jwt::instance();
            $instance2 = Jwt::instance();

            expect($instance1)->toBe($instance2);
        });

        it('Doit accepter une configuration avec instance()', function (): void {
            $config = ['key' => 'custom-key', 'exp_time' => 10];
            $instance = Jwt::instance($config);

            expect($instance)->toBeAnInstanceOf(Jwt::class);
        });
    });

    describe('Récupération du token', function (): void {
        it('Doit récupérer le token depuis Authorization header', function (): void {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test.token.here';

            $token = Jwt::getToken();

            expect($token)->toBe('test.token.here');

            unset($_SERVER['HTTP_AUTHORIZATION']);
        });

        it('Doit récupérer le token depuis SERVER[Authorization]', function (): void {
            $_SERVER['Authorization'] = 'Bearer server.token.here';

            $token = Jwt::getToken();

            expect($token)->toBe('server.token.here');

            unset($_SERVER['Authorization']);
        });

        it('Doit retourner null si aucun token n\'est trouvé', function (): void {
            $token = Jwt::getToken();

            expect($token)->toBeNull();
        });
    });

    describe('getAuthorization', function (): void {
        it('Doit récupérer l\'Authorization depuis différentes sources', function (): void {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test1';
            expect(Jwt::getAuthorization())->toBe('Bearer test1');
            unset($_SERVER['HTTP_AUTHORIZATION']);

            $_SERVER['Authorization'] = 'Bearer test2';
            expect(Jwt::getAuthorization())->toBe('Bearer test2');
            unset($_SERVER['Authorization']);

            // Test sans Authorization
            expect(Jwt::getAuthorization())->toBeNull();
        });
    });

	if (class_exists('Firebase\JWT\JWT')) {
		describe('Encodage', function (): void {
			it('Doit encoder un token JWT', function (): void {
				$data = ['user_id' => 123, 'role' => 'admin'];
				$token = Jwt::encode($data);

				expect($token)->toBeA('string');
				expect($token)->toMatch('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/');
			});

			it('Doit encoder avec configuration personnalisée', function (): void {
				$data = ['test' => 'value'];
				$config = ['exp_time' => 60, 'key' => 'custom-secret'];
				$token = Jwt::encode($data, $config);

				expect($token)->toBeA('string');
			});

			it('Doit encoder avec merge activé', function (): void {
				$data = ['custom' => 'data', 'iat' => 1234567890];
				$config = ['merge' => true];
				$token = Jwt::encode($data, $config);

				expect($token)->toBeA('string');
			});

			it('Doit lever une exception si l\'encodage échoue', function (): void {
				// Forcer une erreur en utilisant un algorithme invalide
				$config = ['algorithm' => 'INVALID_ALGO'];

				expect(function () use ($config) {
					Jwt::encode([], $config);
				})->toThrow(new Exception('JWT Exception :'));
			});
		});

		describe('Décodage', function (): void {
			it('Doit décoder un token JWT valide', function (): void {
				$data = ['user_id' => 456];
				$token = Jwt::encode($data);
				$decoded = Jwt::decode($token);

				expect($decoded)->toBeAn('object');
				expect($decoded->data->user_id)->toBe(456);
			});

			it('Doit lever une exception pour un token invalide', function (): void {
				expect(function () {
					Jwt::decode('invalid.token.here');
				})->toThrow(new Exception('JWT Exception :'));
			});
		});

		describe('Payload', function (): void {
			it('Doit récupérer le payload du token', function (): void {
				// Simuler un token dans les headers
				$data = ['user' => 'john.doe'];
				$token = Jwt::encode($data);

				$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

				$payload = Jwt::payload();

				expect($payload->user)->toBe('john.doe');

				// Nettoyer
				unset($_SERVER['HTTP_AUTHORIZATION']);
			});

			it('Doit récupérer le payload complet avec full=true', function (): void {
				$data = ['test' => 'value'];
				$token = Jwt::encode($data);

				$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

				$payload = Jwt::payload(true);

				expect($payload)->toContainKey('iat');
				expect($payload)->toContainKey('iss');
				expect($payload)->toContainKey('exp');
				expect($payload->data->test)->toBe('value');

				unset($_SERVER['HTTP_AUTHORIZATION']);
			});

			it('Doit lever une exception si aucun token n\'est trouvé', function (): void {
				expect(function () {
					Jwt::payload();
				})->toThrow(new Exception('Access token not found.'));
			});
		});
	}
});
