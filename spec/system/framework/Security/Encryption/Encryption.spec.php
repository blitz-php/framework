<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Exceptions\EncryptionException;
use BlitzPHP\Security\Encryption\Encryption;

use function Kahlan\expect;

describe('Security / Encryption', function (): void {
    beforeEach(function (): void {
        $this->encryption = new Encryption();
    });

    describe('Encryption', function (): void {
        it(': Constructeur', function (): void {
            // Assume no configuration from set_up()
            expect($this->encryption->key)->toBeEmpty();

            // Try with an empty value
            $config           = (object) config('encryption');
            $this->encryption = new Encryption($config);
            expect($this->encryption->key)->toBeEmpty();

            // try a different key
            $ikm              = 'Secret stuff';
            $config->key      = $ikm;
            $this->encryption = new Encryption($config);
            expect($this->encryption->key)->toBe($ikm);
        });

        it(": L'utilisation d'un mauvais pilote leve une exception", function (): void {
            // ask for a bad driver
            $config         = (object) config('encryption');
            $config->driver = 'Bogus';
            $config->key    = 'anything';

            expect(function () use ($config): void {
                $this->encryption->initialize($config);
            })->toThrow(new EncryptionException());
        });

        it(": L'abscence du pilote leve une exception", function (): void {
            // ask for a bad driver
            $config         = (object) config('encryption');
            $config->driver = '';
            $config->key    = 'anything';

            expect(function () use ($config): void {
                $this->encryption->initialize($config);
            })->toThrow(new EncryptionException());
        });

        it(": Creation d'une cle", function (): void {
            expect($this->encryption->createKey())->not->toBeEmpty();
            expect(strlen($this->encryption->createKey()))->toBe(32);
            expect(strlen($this->encryption->createKey(16)))->toBe(16);
        });
    });

    describe('Service', function (): void {
        it(': Le service encrypter fonctionne', function (): void {
            $config           = config('encryption');
            $config['driver'] = 'OpenSSL';
            $config['key']    = 'anything';

            $encrypter = service('encrypter', $config);

            expect($encrypter)->toBeAnInstanceOf(EncrypterInterface::class);
        });

        it(': Le service encrypter leve une exception si le pilote est mauvais', function (): void {
            // ask for a bad driver
            $config           = config('encryption');
            $config['driver'] = 'Bogus';
            $config['key']    = 'anything';

            expect(function () use ($config): void {
                service('encrypter', $config);
            })->toThrow(new EncryptionException());
        });

        it(": Le service encrypter leve une exception s'il n'y a pas de cle", function (): void {
            expect(function (): void {
                service('encrypter');
            })->toThrow(new EncryptionException());
        });

        it(': Service encrypter partagé', function (): void {
            $config           = config('encryption');
            $config['driver'] = 'OpenSSL';
            $config['key']    = 'anything';

            $encrypter = service('encrypter', $config);

            $config['key'] = 'Abracadabra';
            $encrypter     = service('encrypter', $config, true);

            expect($encrypter->key)->toBe('anything');
        });
    });

    describe('Methodes magiques', function (): void {
        it(': Magic isset', function (): void {
            expect(isset($this->encryption->digest))->toBeTruthy();
            expect(isset($this->encryption->bogus))->toBeFalsy();
        });

        it(': Magic get', function (): void {
            expect($this->encryption->digest)->toBe('SHA512');
            expect($this->encryption->bogus)->toBeNull();
        });
    });

    describe('Decryptage', function (): void {
        it(': Decrypte une chaine codée avec AES-128-CBC', function (): void {
            $config                   = config('encryption');
            $config['driver']         = 'OpenSSL';
            $config['key']            = hex2bin('64c70b0b8d45b80b9eba60b8b3c8a34d0193223d20fea46f8644b848bf7ce67f');
            $config['cipher']         = 'AES-128-CBC';
            $config['rawData']        = false;
            $config['encryptKeyInfo'] = 'encryption';
            $config['authKeyInfo']    = 'authentication';
            $encrypter                = single_service('encrypter', $config);

            $encrypted = 'ZGY3OWMyODBjN2M4MjBlNWEyMjRkY2RlOTQzNDIwNjA1ZGI2MzU2NDVjMDFmNjdhODM4ZDE1YmYyYzczZTYyNmQ1MmNhZmUyMjU1MTMxYWI2ZjRlNTFjYWUyYjY1OWJkNjNhMjhjZTU2ZTkyZWNlNTE1NTE5NDlhYThmMDlhZjVMNWN0SXVrU0hkQjJOVVczT1paK0RkbTM4WFRWTnNUVTJFTlZQWmxHNGJSS1hPaUpCMHNhMXBxcUlsc3RTaXpr';
            $decrypted = $encrypter->decrypt($encrypted);

            $expected = 'This is a plain-text message.';
            expect($decrypted)->toBe($expected);
        });

        it(': Decrypte une chaine codée avec AES-256-CTR', function (): void {
            $config                   = config('encryption');
            $config['driver']         = 'OpenSSL';
            $config['key']            = hex2bin('64c70b0b8d45b80b9eba60b8b3c8a34d0193223d20fea46f8644b848bf7ce67f');
            $config['cipher']         = 'AES-256-CTR';
            $config['rawData']        = false;
            $config['encryptKeyInfo'] = 'encryption';
            $config['authKeyInfo']    = 'authentication';
            $encrypter                = single_service('encrypter', $config);

            $encrypted = 'ZDY2MjM0NGYzNTdmNDE4NjZlOWQ4MzhkYjBmMTdkYjQ5ZGRhZDU3OTQ3YWM2YTFmZGU2YWM3YjhjMDQ5NWM4ZDU1MTUwMDljZjI5YjBmOGM2MTkxNGNiMWI1ZjE5ZjU1ZmRmOTdlMDY2Y2VkMzU5OTQ0ODdlZjgwMDUyMzU2ZGFMVmdpakhLUmY1ZFdsM09Mb0I0RGlIcGwxNUtkcTQxaU94eHVlYTNNK2dlaTlXdU5zYXladXdmcndqOUY=';
            $decrypted = $encrypter->decrypt($encrypted);

            $expected = 'This is a plain-text message.';
            expect($decrypted)->toBe($expected);
        });

        it(': Decrypte une chaine codée avec base64_encode', function (): void {
            $config           = config('encryption');
            $config['driver'] = 'OpenSSL';
            $config['key']    = hex2bin('64c70b0b8d45b80b9eba60b8b3c8a34d0193223d20fea46f8644b848bf7ce67f');
            $encrypter        = single_service('encrypter', $config);

            $encrypted = base64_decode('UB9PC3QfQIoLY5+/GU8BUQnfhEcCml6i4Sve6k0f8r6Id6IzlbkvMhfWf5E2lBH5+OTWuv5MUoTBQWv9Pd46ua07QsqS6/vHaW3rCg6cpLM/8d2IZE/VO+uXeaU6XHO5mJ8ehGKg96JITvKjxA==', true);
            $decrypted = $encrypter->decrypt($encrypted);

            $expected = 'This is a plain-text message.';
            expect($decrypted)->toBe($expected);
        });
    });

	describe('EncrypterInterface', function (): void {
        it('getKey', function (): void {
           $config           = (object) config('encryption');
		   $this->encryption = new Encryption($config);
		   expect(fn() => $this->encryption->getKey())->toThrow(EncryptionException::needsStarterKey());

		   // try a different key
		   $ikm              = 'Secret stuff';
		   $config->key      = $ikm;
		   $this->encryption = new Encryption($config);
		   expect($this->encryption->getKey())->toBe($ikm);
        });

        it('Chiffrement', function (): void {
			$config      = (object) config('encryption');
			$config->key = 'abracadabra';
			$encryption  = new Encryption($config);

            $message1  = 'Ceci est un message en clair.';
            $encoded   = $encryption->encrypt($message1, ['key' => $config->key]);

            expect($message1)->toBe($encryption->decrypt($encoded, ['key' => $config->key]));
        });
    });
});
