<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Exceptions\EncryptionException;
use BlitzPHP\Security\Encryption\Encryption;
use BlitzPHP\Security\Encryption\Handlers\OpenSSLHandler;

describe('Security / Encryption / OpenSSL', function (): void {
    beforeEach(function (): void {
        skipIf(! extension_loaded('openssl'));

        $this->encryption = new Encryption();
    });

    it(': Test de santé de base', function (): void {
        $params         = (object) config('encryption');
        $params->driver = 'OpenSSL';
        $params->key    = "Quelque chose d'autre qu'une chaîne vide";

        $encrypter = $this->encryption->initialize($params);

        expect($encrypter->key)->toBe("Quelque chose d'autre qu'une chaîne vide");
        expect($encrypter->cipher)->toBe('AES-256-CTR');
    });

    it(': Test simple', function (): void {
        $params         = (object) config('encryption');
        $params->driver = 'OpenSSL';
        $params->key    = '\xd0\xc9\x08\xc4\xde\x52\x12\x6e\xf8\xcc\xdb\x03\xea\xa0\x3a\x5c';

        // Etat par defaut (AES-256/Rijndael-256 en mode CTR)
        $encrypter = $this->encryption->initialize($params);

        // La clé était-elle correctement réglée ?
        expect($encrypter->key)->toBe($params->key);

        // Cryptage/décryptage simple, paramètres par défaut
        $message1 = 'Ceci est un message en clair.';
        expect($encrypter->decrypt($encrypter->encrypt($message1)))->toBe($message1);

        $message2 = 'Ceci est un message en clair different.';
        expect($encrypter->decrypt($encrypter->encrypt($message2)))->toBe($message2);
        expect($encrypter->decrypt($encrypter->encrypt($message1)))->not->toBe($message2);
    });

    it(": L'abscence de la cle leve une exception", function (): void {
        $encrypter = new OpenSSLHandler();
        $message1  = 'Ceci est un message en clair.';

        expect(static function () use ($message1, $encrypter): void {
            $encrypter->encrypt($message1, ['key' => '']);
        })->toThrow(new EncryptionException());
    });

    it(': Chiffrement avec une cle sous forme de chaine', function (): void {
        $key       = 'abracadabra';
        $encrypter = new OpenSSLHandler();
        $message1  = 'Ceci est un message en clair.';
        $encoded   = $encrypter->encrypt($message1, $key);

        expect($encrypter->decrypt($encoded, $key))->toBe($message1);
    });

    it(': dechiffrement avec une cle erronée', function (): void {
        expect(static function (): void {
            $key1      = 'abracadabra';
            $encrypter = new OpenSSLHandler();
            $message1  = 'Ceci est un message en clair.';
            $encoded   = $encrypter->encrypt($message1, $key1);

            expect($message1)->not->toBe($encoded);
            $key2 = 'Sainte vache, Batman !';
            expect($message1)->not->toBe($encrypter->decrypt($encoded, $key2));
        })->toThrow(new EncryptionException());
    });

    it(': Chiffrement avec une cle sous forme de tableau', function (): void {
        $key       = 'abracadabra';
        $encrypter = new OpenSSLHandler();
        $message1  = 'Ceci est un message en clair.';
        $encoded   = $encrypter->encrypt($message1, ['key' => $key]);

        expect($message1)->toBe($encrypter->decrypt($encoded, ['key' => $key]));
    });

    it(": L'authentification échouera lors du décryptage avec la mauvaise clé", function (): void {
        expect(static function (): void {
            $key1      = 'abracadabra';
            $encrypter = new OpenSSLHandler();
            $message1  = 'Ceci est un message en clair.';
            $encoded   = $encrypter->encrypt($message1, ['key' => $key1]);

            expect($message1)->not->toBe($encoded);
            $key2 = 'Sainte vache, Batman !';
            expect($message1)->not->toBe($encrypter->decrypt($encoded, ['key' => $key2]));
        })->toThrow(new EncryptionException());
    });
});
