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

describe('Security / Encryption / Sodium', function (): void {
    beforeEach(function (): void {
        skipIf(! extension_loaded('sodium'));

        $this->config         = (object) config('encryption');
        $this->config->driver = 'Sodium';
        $this->config->key    = sodium_crypto_secretbox_keygen();
        $this->encryption     = new Encryption($this->config);
    });

    it(': Recuperation des proprietes', function (): void {
        $this->config->key       = sodium_crypto_secretbox_keygen();
        $this->config->blockSize = 256;
        $encrypter               = $this->encryption->initialize($this->config);

        expect($this->config->key)->toBe($encrypter->key);
        expect($this->config->blockSize)->toBe($encrypter->blockSize);
        expect($encrypter->driver)->toBeNull();
    });

    it(": L'abscence de la clé lève une exception lors de l'initialisation", function (): void {
        expect(function (): void {
            $this->config->key = '';
            $this->encryption->initialize($this->config);
        })->toThrow(new EncryptionException());
    });

    it(": L'abscence de la clé lève une exception lors du chiffrement", function (): void {
        expect(function (): void {
            $encrypter = $this->encryption->initialize($this->config);
            $encrypter->encrypt('Un message à chiffrer', '');
        })->toThrow(new EncryptionException());
    });

    it(": L'abscence de la clé lève une exception lors du déchiffrement", function (): void {
        expect(function (): void {
            $encrypter  = $this->encryption->initialize($this->config);
            $ciphertext = $encrypter->encrypt('Un message à chiffrer');
            // Après le chiffrement, le message et la clé sont effacés du tampon
            $encrypter->decrypt($ciphertext);
        })->toThrow(new EncryptionException());
    });

    it(':Un blocksize invalide lève une exception lors du chiffrement', function (): void {
        expect(function (): void {
            $this->config->blockSize = -1;

            $encrypter = $this->encryption->initialize($this->config);
            $encrypter->encrypt('Un message à chiffrer');
        })->toThrow(new EncryptionException());
    });

    xit(':Un blocksize invalide lève une exception lors du déchiffrement', function (): void {
        expect(function (): void {
            $key       = $this->config->key;
            $encrypter = $this->encryption->initialize($this->config);

            $ciphertext = $encrypter->encrypt('Un message.');
            // Après le chiffrement, le message et la clé sont effacés du tampon.
            $encrypter->decrypt($ciphertext, ['key' => $key, 'blockSize' => 0]);
        })->toThrow(new EncryptionException());
    });

    it(':Un texte tronqué lève une exception lors du déchiffrement', function (): void {
        expect(function (): void {
            $encrypter = $this->encryption->initialize($this->config);

            $ciphertext = $encrypter->encrypt('Un message à chiffrer');
            $truncated  = mb_substr($ciphertext, 0, 24, '8bit');
            $encrypter->decrypt($truncated, ['blockSize' => 256, 'key' => sodium_crypto_secretbox_keygen()]);
        })->toThrow(new EncryptionException());
    });

    it(': décryptage', function (): void {
        $key = sodium_crypto_secretbox_keygen();
        $msg = 'Un message en clair pour vous.';

        $this->config->key = $key;
        $encrypter         = $this->encryption->initialize($this->config);
        $ciphertext        = $encrypter->encrypt($msg);

        expect($encrypter->decrypt($ciphertext, $key))->toBe($msg);
        expect($encrypter->decrypt($ciphertext, $key))->not->toBe('Un message en-clair pour vous.');
    });
});
