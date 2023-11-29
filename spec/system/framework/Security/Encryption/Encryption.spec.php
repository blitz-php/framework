<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Exceptions\EncryptionException;
use BlitzPHP\Security\Encryption\Encryption;

describe('Security / Encryption', function () {
    beforeEach(function () {
        $this->encryption = new Encryption();
    });

    describe('Encryption', function () {
        it(': Constructeur', function () {
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

        it(": L'utilisation d'un mauvais pilote leve une exception", function () {
            // ask for a bad driver
            $config         = (object) config('encryption');
            $config->driver = 'Bogus';
            $config->key    = 'anything';

            expect(function () use ($config) {
                $this->encryption->initialize($config);
            })->toThrow(new EncryptionException());
        });

        it(": L'abscence du pilote leve une exception", function () {
            // ask for a bad driver
            $config         = (object) config('encryption');
            $config->driver = '';
            $config->key    = 'anything';

            expect(function () use ($config) {
                $this->encryption->initialize($config);
            })->toThrow(new EncryptionException());
        });

        it(": Creation d'une cle", function () {
            expect($this->encryption->createKey())->not->toBeEmpty();
            expect(strlen($this->encryption->createKey()))->toBe(32);
            expect(strlen($this->encryption->createKey(16)))->toBe(16);
        });
    });

    describe('Service', static function () {
        it(': Le service encrypter fonctionne', static function () {
            $config           = config('encryption');
            $config['driver'] = 'OpenSSL';
            $config['key']    = 'anything';

            $encrypter = Services::encrypter($config);

            expect($encrypter)->toBeAnInstanceOf(EncrypterInterface::class);
        });

        it(': Le service encrypter leve une exception si le pilote est mauvais', static function () {
            // ask for a bad driver
            $config           = config('encryption');
            $config['driver'] = 'Bogus';
            $config['key']    = 'anything';

            expect(static function () use ($config) {
                Services::encrypter($config);
            })->toThrow(new EncryptionException());
        });

        it(": Le service encrypter leve une exception s'il n'y a pas de cle", static function () {
            expect(static function () {
                Services::encrypter();
            })->toThrow(new EncryptionException());
        });

        it(': Service encrypter partagé', static function () {
            $config           = config('encryption');
            $config['driver'] = 'OpenSSL';
            $config['key']    = 'anything';

            $encrypter = Services::encrypter($config, true);

            $config['key'] = 'Abracadabra';
            $encrypter     = Services::encrypter($config, true);

            expect($encrypter->key)->toBe('anything');
        });
    });

    describe('Methodes magiques', function () {
        it(': Magic isset', function () {
            expect(isset($this->encryption->digest))->toBeTruthy();
            expect(isset($this->encryption->bogus))->toBeFalsy();
        });

        it(': Magic get', function () {
            expect($this->encryption->digest)->toBe('SHA512');
            expect($this->encryption->bogus)->toBeNull();
        });
    });

    describe('Decryptage', static function () {
        it(': Decrypte une chaine codée avec AES-128-CBC', static function () {
            $config                   = config('encryption');
            $config['driver']         = 'OpenSSL';
            $config['key']            = hex2bin('64c70b0b8d45b80b9eba60b8b3c8a34d0193223d20fea46f8644b848bf7ce67f');
            $config['cipher']         = 'AES-128-CBC';
            $config['rawData']        = false;
            $config['encryptKeyInfo'] = 'encryption';
            $config['authKeyInfo']    = 'authentication';
            $encrypter                = Services::encrypter($config, false);

            $encrypted = '211c55b9d1948187557bff88c1e77e0f6b965e3711d477d97fb0b60907a7336028714dbb8dfe90598039e9bc7147b54e552d739b378cd864fb91dde9ad6d4ffalIvVxFDDLTPBYGaHLNDzUSJExBKbQJ0NW27KDaR83bYqz8MDz/mXXpE+HHdaWjEE';
            $decrypted = $encrypter->decrypt($encrypted);

            $expected = 'This is a plain-text message.';
            expect($decrypted)->toBe($expected);
        });

        it(': Decrypte une chaine codée avec AES-256-CTR', static function () {
            $config                   = config('encryption');
            $config['driver']         = 'OpenSSL';
            $config['key']            = hex2bin('64c70b0b8d45b80b9eba60b8b3c8a34d0193223d20fea46f8644b848bf7ce67f');
            $config['cipher']         = 'AES-256-CTR';
            $config['rawData']        = false;
            $config['encryptKeyInfo'] = 'encryption';
            $config['authKeyInfo']    = 'authentication';
            $encrypter                = Services::encrypter($config, false);

            $encrypted = 'f5eeb3f056b2dc5e8119b4a5f5ba793d724b9ca2d1ca23ab89bc72e51863f8da233a83ccb48d5daf3d6905d61f357877aaad32c8bc7a7c5e48f3268d2ba362b9UTw2A7U4CB9vb+6izrDzJHAdz1hAutIt2Ex2C2FqamJAXc8Z8RQor9UvaWy2';
            $decrypted = $encrypter->decrypt($encrypted);

            $expected = 'This is a plain-text message.';
            expect($decrypted)->toBe($expected);
        });

        it(': Decrypte une chaine codée avec base64_encode', static function () {
            $config           = config('encryption');
            $config['driver'] = 'OpenSSL';
            $config['key']    = hex2bin('64c70b0b8d45b80b9eba60b8b3c8a34d0193223d20fea46f8644b848bf7ce67f');
            $encrypter        = Services::encrypter($config, false);

            $encrypted = base64_decode('UB9PC3QfQIoLY5+/GU8BUQnfhEcCml6i4Sve6k0f8r6Id6IzlbkvMhfWf5E2lBH5+OTWuv5MUoTBQWv9Pd46ua07QsqS6/vHaW3rCg6cpLM/8d2IZE/VO+uXeaU6XHO5mJ8ehGKg96JITvKjxA==', true);
            $decrypted = $encrypter->decrypt($encrypted);

            $expected = 'This is a plain-text message.';
            expect($decrypted)->toBe($expected);
        });
    });
});
