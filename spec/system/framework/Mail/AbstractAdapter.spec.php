<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use Psr\Log\LoggerInterface;
use BlitzPHP\Spec\Mock\MockMailAdapter;
use BlitzPHP\Utilities\Reflection\ReflectionClass;
use Kahlan\Plugin\Double;

use function Kahlan\expect;
use function Kahlan\allow;

describe('Mail / Adapters / AbstractAdapter', function (): void {
    describe('Méthodes protégées statiques', function (): void {
        // Classe concrète pour tester les méthodes protégées
        $concreteAdapter = new class(false) extends MockMailAdapter {
            // Exposer les méthodes protégées pour les tests
            public function testMethodName($name, $prefix = 'set') {
                return $this->methodName($name, $prefix);
            }

            public function testMakeAddress($email, $name) {
                return $this->makeAddress($email, $name);
            }

            public function testParseMultipleAddresses($address, $name = '', $set = false) {
                return $this->parseMultipleAddresses($address, $name, $set);
            }

            public function testIsValidMimeType($mimeType, $allowedTypes = []) {
                return $this->isValidMimeType($mimeType, $allowedTypes);
            }

            public function testIsValidFileSize($filePath, $maxSize = null) {
                return $this->isValidFileSize($filePath, $maxSize);
            }
        };

        describe('methodName()', function () use ($concreteAdapter): void {
            it('Doit convertir les noms en camelCase', function () use ($concreteAdapter): void {
                expect($concreteAdapter->testMethodName('host_name'))->toBe('setHostName');
                expect($concreteAdapter->testMethodName('user-name', 'get'))->toBe('getUserName');
                expect($concreteAdapter->testMethodName('test', 'has'))->toBe('hasTest');
            });
        });

        describe('makeAddress()', function () use ($concreteAdapter): void {
            it('Doit créer un tableau [email, nom]', function () use ($concreteAdapter): void {
                $result = $concreteAdapter->testMakeAddress('test@example.com', 'Test Name');
                expect($result)->toBe(['test@example.com', 'Test Name']);
            });

            it('Doit inverser email et nom si l\'email est invalide', function () use ($concreteAdapter): void {
                $result = $concreteAdapter->testMakeAddress('Invalid Name', 'valid@example.com');
                expect($result)->toBe(['valid@example.com', 'Invalid Name']);
            });

            it('Doit lever une exception pour un email invalide', function () use ($concreteAdapter): void {
                expect(fn() => $concreteAdapter->testMakeAddress('invalid-email', 'Not an email'))
                    ->toThrow(new InvalidArgumentException('Adresse email invalide: invalid-email'));
            });
        });

        describe('parseMultipleAddresses()', function () use ($concreteAdapter): void {
            it('Doit parser une adresse simple', function () use ($concreteAdapter): void {
                [$addresses, $set] = $concreteAdapter->testParseMultipleAddresses(
                    'test@example.com',
                    'Test Name'
                );

                expect($addresses)->toBeAn('array');
                expect($addresses)->toHaveLength(1);
                expect($addresses[0])->toBe(['test@example.com', 'Test Name']);
                expect($set)->toBe(false);
            });

            it('Doit parser un tableau d\'adresses', function () use ($concreteAdapter): void {
                [$addresses, $set] = $concreteAdapter->testParseMultipleAddresses([
                    'test1@example.com' => 'Test 1',
                    'test2@example.com' => 'Test 2'
                ]);

                expect($addresses)->toBeAn('array');
                expect($addresses)->toHaveLength(2);
                expect($set)->toBe(false);
            });

            it('Doit accepter set = true via le deuxième argument', function () use ($concreteAdapter): void {
                [$addresses, $set] = $concreteAdapter->testParseMultipleAddresses(
                    'test@example.com',
					'test',
                    true
                );

                expect($set)->toBe(true);
            });

            it('Doit lever une exception pour des arguments invalides', function () use ($concreteAdapter): void {
                expect(fn() => $concreteAdapter->testParseMultipleAddresses(
                    'test@example.com',
                    true // bool au lieu de string pour name
                ))->toThrow(new InvalidArgumentException(
                    'L\'argument 2 ($name) doit être une chaîne de caractères quand $address est une chaîne'
                ));
            });

            it('Doit ignorer les adresses invalides', function () use ($concreteAdapter): void {
                allow('service')->toBeCalled()->with('logger')->andReturn(
                    Double::instance(['implements' => [LoggerInterface::class]])
                );

                [$addresses, $set] = $concreteAdapter->testParseMultipleAddresses([
                    'invalid-email' => 'Invalid',
                    'valid@example.com' => 'Valid'
                ]);

                // Doit contenir seulement l'adresse valide
                expect($addresses)->toHaveLength(1);
                expect($addresses[0][0])->toBe('valid@example.com');
            });
        });

        describe('isValidMimeType()', function () use ($concreteAdapter): void {
            it('Doit valider les types MIME de base', function () use ($concreteAdapter): void {
                expect($concreteAdapter->testIsValidMimeType('image/jpeg'))->toBe(true);
                expect($concreteAdapter->testIsValidMimeType('application/pdf'))->toBe(true);
                expect($concreteAdapter->testIsValidMimeType('text/plain'))->toBe(true);
            });

            it('Doit rejeter les types MIME non autorisés', function () use ($concreteAdapter): void {
                expect($concreteAdapter->testIsValidMimeType('application/x-msdownload'))->toBe(false);
                expect($concreteAdapter->testIsValidMimeType('application/x-dosexec'))->toBe(false);
            });

            it('Doit accepter les wildcards', function () use ($concreteAdapter): void {
                expect($concreteAdapter->testIsValidMimeType('image/png', ['image/*']))->toBe(true);
                expect($concreteAdapter->testIsValidMimeType('image/jpeg', ['image/*']))->toBe(true);
                expect($concreteAdapter->testIsValidMimeType('application/pdf', ['image/*']))->toBe(false);
            });

            it('Doit être insensible à la casse', function () use ($concreteAdapter): void {
                expect($concreteAdapter->testIsValidMimeType('IMAGE/JPEG'))->toBe(true);
                expect($concreteAdapter->testIsValidMimeType('Application/PDF'))->toBe(true);
            });
        });

        describe('isValidFileSize()', function () use ($concreteAdapter): void {
            it('Doit valider la taille d\'un fichier existant', function () use ($concreteAdapter): void {
                $tempFile = tempnam(sys_get_temp_dir(), 'test');
                file_put_contents($tempFile, 'small content');

                expect($concreteAdapter->testIsValidFileSize($tempFile, 1024))->toBe(true);

                unlink($tempFile);
            });

            it('Doit rejeter un fichier trop gros', function () use ($concreteAdapter): void {
                $tempFile = tempnam(sys_get_temp_dir(), 'test');
                file_put_contents($tempFile, str_repeat('x', 2048));

                expect($concreteAdapter->testIsValidFileSize($tempFile, 1024))->toBe(false);

                unlink($tempFile);
            });

            it('Doit rejeter un fichier inexistant', function () use ($concreteAdapter): void {
                expect($concreteAdapter->testIsValidFileSize('/nonexistent/file.txt'))->toBe(false);
            });

            it('Doit utiliser la taille par défaut de la config', function () use ($concreteAdapter): void {
                $tempFile = tempnam(sys_get_temp_dir(), 'test');
                file_put_contents($tempFile, str_repeat('x', 5 * 1024 * 1024)); // 5MB

                // Mock config pour retourner 10MB
                allow('config')->toBeCalled()->with('mail.max_attachment_size', 10 * 1024 * 1024)
                    ->andReturn(10 * 1024 * 1024);

                expect($concreteAdapter->testIsValidFileSize($tempFile))->toBe(true);

                unlink($tempFile);
            });
        });
    });

    describe('Méthodes publiques', function (): void {
        // Classe concrète pour tester
        $concreteAdapter = new class(false) extends MockMailAdapter {
            public ?string $lastError = null;

            // Pour tester __call
            public $mailer;
            private $custom;

            public function setCustomMethod($value) {
                $this->custom = $value;

                return $this;
            }

            public function getCustomMethod() {
                return $this->custom ?? 'default';
            }
        };

        describe('init()', function () use ($concreteAdapter): void {
            it('Doit appeler les méthodes setter correspondantes', function () use ($concreteAdapter): void {
                $config = [
                    'port' => 587,
                    'host' => 'smtp.example.com',
                    'username' => 'user',
                    'custom_method' => 'value'
                ];

                $result = $concreteAdapter->init($config);
                expect($result)->toBe($concreteAdapter);
            });

            it('Doit ignorer les clés sans setter correspondant', function () use ($concreteAdapter): void {
                $config = [
                    'nonexistent_key' => 'value',
                    'another_invalid' => 'test'
                ];

                $result = $concreteAdapter->init($config);
                expect($result)->toBe($concreteAdapter);
            });
        });

        describe('getLastError() et clearError()', function () use ($concreteAdapter): void {
            it('Doit retourner la dernière erreur', function () use ($concreteAdapter): void {
                $concreteAdapter->lastError = 'Test error';
                expect($concreteAdapter->getLastError())->toBe('Test error');
            });

            it('Doit retourner null si pas d\'erreur', function () use ($concreteAdapter): void {
                $concreteAdapter->lastError = null;
                expect($concreteAdapter->getLastError())->toBeNull();
            });

            it('Doit nettoyer l\'erreur', function () use ($concreteAdapter): void {
                $concreteAdapter->lastError = 'Test error';
                $concreteAdapter->clearError();
                expect($concreteAdapter->getLastError())->toBeNull();
            });
        });

        describe('__call()', function () use ($concreteAdapter): void {
            it('Doit appeler les méthodes setter via __call', function () use ($concreteAdapter): void {
                $result = $concreteAdapter->port(587);
                expect($result)->toBe($concreteAdapter);
            });

            it('Doit appeler les méthodes getter via __call', function () use ($concreteAdapter): void {
                $concreteAdapter->setCustomMethod('test');
                $result = $concreteAdapter->customMethod();
                expect($result)->toBe('test');
            });

            it('Doit appeler les méthodes sur le mailer sous-jacent', function () use ($concreteAdapter): void {
                $concreteAdapter->mailer = new class {
					public function someMethod() { return 'result'; }
				};

                $result = $concreteAdapter->someMethod();
                expect($result)->toBe('result');
            });

            it('Doit lever une exception pour une méthode inexistante', function () use ($concreteAdapter): void {
                expect(fn() => $concreteAdapter->nonexistentMethod())
                    ->toThrow(new BadMethodCallException(
                        'La méthode nonexistentMethod n\'existe pas dans ' . $concreteAdapter::class
                    ));
            });
        });
    });

    describe('Validation des dépendances', function (): void {
        it('Doit valider les dépendances au constructeur', function (): void {
			$adapter = new class(false) extends MockMailAdapter { };
			$reflection = ReflectionClass::make($adapter);
			$reflection->setValue('dependancies', [
				['class' => 'NonexistentClass', 'package' => 'vendor/package']
			]);

            expect(fn() => $adapter->__construct(false))
				->toThrow(new RuntimeException(
					lang('Mail.dependancyNotFound', ['NonexistentClass', $adapter::class, 'vendor/package'])
				));
        });

        it('Doit lever une exception pour des dépendances mal formées', function (): void {
            expect(fn(): MockMailAdapter => new class(false) extends MockMailAdapter {
                protected array $dependancies = [
                    ['invalid' => 'dependency']
                ];
            })
			->toThrow(new InvalidArgumentException('Propriété de dépendance invalide'));
        });
    });
});
