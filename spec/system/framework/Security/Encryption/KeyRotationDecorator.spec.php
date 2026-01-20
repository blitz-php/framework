<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Security\Encryption\Encryption;
use BlitzPHP\Exceptions\EncryptionException;

use function Kahlan\expect;

describe('Security / Encryption / KeyRotationDecorator', function (): void {
    beforeEach(function (): void {
        $this->encryption = new Encryption();
    });

    describe('KeyRotationDecorator', function (): void {
		skipIf(! extension_loaded('openssl'));

        it('utilise la clé actuelle pour le chiffrement', function (): void {
            $currentKey  = 'current-encryption-key';
            $previousKey = 'previous-encryption-key';

            $config                 = config('encryption');
            $config['driver']       = 'OpenSSL';
            $config['key']          = $currentKey;
            $config['previous_keys'] = [$previousKey];

            $encrypter = $this->encryption->initialize((object) $config);
            $message   = 'This is a plain-text message.';
            $encrypted = $encrypter->encrypt($message);

            expect($encrypter->decrypt($encrypted))->toBe($message);
            expect(function () use ($encrypter, $encrypted, $previousKey): void {
                $encrypter->decrypt($encrypted, ['key' => $previousKey]);
            })->toThrow(new EncryptionException());
        });

        it('déchiffre les anciennes données avec rotation de clé', function (): void {
            $oldKey = 'old-encryption-key';
            $newKey = 'new-encryption-key';

            $configOld       = config('encryption');
            $configOld['key'] = $oldKey;
            $oldEncrypter = $this->encryption->initialize((object) $configOld);
            $message      = 'Sensitive data encrypted with old key';
            $encrypted    = $oldEncrypter->encrypt($message);

            $configNew               = config('encryption');
            $configNew['key']        = $newKey;
            $configNew['previous_keys'] = [$oldKey];
            $newEncrypter = $this->encryption->initialize((object) $configNew);

            expect($newEncrypter->decrypt($encrypted))->toBe($message);
        });

        it('gère plusieurs clés précédentes avec fallback', function (): void {
            $key1 = 'first-key-very-long';
            $key2 = 'second-key-very-long';
            $key3 = 'third-key-very-long';

            $config1       = config('encryption');
            $config1['key'] = $key1;
            $encrypter1    = $this->encryption->initialize((object) $config1);
            $message1      = 'Message encrypted with key1';
            $encrypted1    = $encrypter1->encrypt($message1);

            $config2       = config('encryption');
            $config2['key'] = $key2;
            $encrypter2    = $this->encryption->initialize((object) $config2);
            $message2      = 'Message encrypted with key2';
            $encrypted2    = $encrypter2->encrypt($message2);

            $config3               = config('encryption');
            $config3['key']        = $key3;
            $config3['previous_keys'] = [$key2, $key1];
            $encrypter3 = $this->encryption->initialize((object) $config3);

            expect($encrypter3->decrypt($encrypted1))->toBe($message1);
            expect($encrypter3->decrypt($encrypted2))->toBe($message2);
        });

        it('empêche la rotation lorsque une clé explicite est fournie', function (): void {
            $currentKey  = 'current-key-very-long';
            $previousKey = 'previous-key-very-long';
            $explicitKey = 'explicit-key-very-long';

            $configOld       = config('encryption');
            $configOld['key'] = $previousKey;
            $oldEncrypter   = $this->encryption->initialize((object) $configOld);
            $message        = 'Test message';
            $encrypted      = $oldEncrypter->encrypt($message);

            $config               = config('encryption');
            $config['key']        = $currentKey;
            $config['previous_keys'] = [$previousKey];
            $encrypter = $this->encryption->initialize((object) $config);

            expect(function () use ($encrypter, $encrypted, $explicitKey): void {
                $encrypter->decrypt($encrypted, ['key' => $explicitKey]);
            })->toThrow(new EncryptionException());
        });

        it('ne fait pas de fallback avec des clés précédentes vides', function (): void {
            $key1 = 'first-key-very-long';
            $key2 = 'second-key-very-long';

            $config1       = config('encryption');
            $config1['key'] = $key1;
            $encrypter1    = $this->encryption->initialize((object) $config1);
            $message       = 'Test message';
            $encrypted     = $encrypter1->encrypt($message);

            $config2               = config('encryption');
            $config2['key']        = $key2;
            $config2['previous_keys'] = [];
            $encrypter2 = $this->encryption->initialize((object) $config2);

            expect(function () use ($encrypter2, $encrypted): void {
                $encrypter2->decrypt($encrypted);
            })->toThrow(new EncryptionException());
        });

        it('lance l\'exception originale lorsque toutes les clés échouent', function (): void {
            $correctKey = 'correct-key-very-long';
            $wrongKey1  = 'wrong-key-1-very-long';
            $wrongKey2  = 'wrong-key-2-very-long';
            $wrongKey3  = 'wrong-key-3-very-long';

            $configCorrect       = config('encryption');
            $configCorrect['key'] = $correctKey;
            $encrypter          = $this->encryption->initialize((object) $configCorrect);
            $message            = 'Test message';
            $encrypted          = $encrypter->encrypt($message);

            $configWrong               = config('encryption');
            $configWrong['key']        = $wrongKey1;
            $configWrong['previous_keys'] = [$wrongKey2, $wrongKey3];
            $encrypterWrong = $this->encryption->initialize((object) $configWrong);

            expect(function () use ($encrypterWrong, $encrypted): void {
                $encrypterWrong->decrypt($encrypted);
            })->toThrow(EncryptionException::authenticationFailed());
        });

        it('délègue l\'accès aux propriétés', function (): void {
            $config               = config('encryption');
            $config['key']        = 'test-key-very-long';
            $config['cipher']     = 'AES-128-CBC';
            $config['previous_keys'] = ['old-key'];

            $encrypter = $this->encryption->initialize((object) $config);

            expect($encrypter->cipher)->toBe('AES-128-CBC');
            expect($encrypter->key)->toBe('test-key-very-long');
        });

        it('fonctionne avec le gestionnaire Sodium', function (): void {
            skipIf(! extension_loaded('sodium'));

            $oldKey = sodium_crypto_secretbox_keygen();
            $newKey = sodium_crypto_secretbox_keygen();

            $configOld           = config('encryption');
            $configOld['driver'] = 'Sodium';
            $configOld['key']    = $oldKey;
            $oldEncrypter        = $this->encryption->initialize((object) $configOld);
            $message             = 'Sensitive data encrypted with old Sodium key';
            $encrypted           = $oldEncrypter->encrypt($message);

            $configNew               = config('encryption');
            $configNew['driver']     = 'Sodium';
            $configNew['key']        = $newKey;
            $configNew['previous_keys'] = [$oldKey];
            $newEncrypter = $this->encryption->initialize((object) $configNew);



            expect($newEncrypter->decrypt($encrypted))->toBe($message);

            $newMessage   = 'New message with new key';
            $newEncrypted = $newEncrypter->encrypt($newMessage);
            expect($newEncrypter->decrypt($newEncrypted))->toBe($newMessage);
        });

        it('simule un scénario réaliste de rotation de clés', function (): void {
            $q1Key = 'q1-2026-key-very-long';
            $q2Key = 'q2-2026-key-very-long';
            $q3Key = 'q3-2026-key-very-long';
            $q4Key = 'q4-2026-key-very-long';

            // Q1 : Chiffrer les données utilisateur
            $configQ1       = config('encryption');
            $configQ1['key'] = $q1Key;
            $encrypterQ1    = $this->encryption->initialize((object) $configQ1);
            $userData       = 'user-sensitive-data-from-q1';
            $encryptedQ1    = $encrypterQ1->encrypt($userData);

            // Q2 : Rotation vers nouvelle clé, garder Q1 pour compatibilité
            $configQ2               = config('encryption');
            $configQ2['key']        = $q2Key;
            $configQ2['previous_keys'] = [$q1Key];
            $encrypterQ2 = $this->encryption->initialize((object) $configQ2);

            // Peut encore lire les données Q1
            expect($encrypterQ2->decrypt($encryptedQ1))->toBe($userData);

            // Nouvelles données chiffrées avec la clé Q2
            $newData     = 'user-sensitive-data-from-q2';
            $encryptedQ2 = $encrypterQ2->encrypt($newData);
            expect($encrypterQ2->decrypt($encryptedQ2))->toBe($newData);

            // Q3 : Rotation vers nouvelle clé, garder Q2 et Q1 pour compatibilité
            $configQ3               = config('encryption');
            $configQ3['key']        = $q3Key;
            $configQ3['previous_keys'] = [$q2Key, $q1Key];
            $encrypterQ3 = $this->encryption->initialize((object) $configQ3);

            // Peut encore lire les données Q1 et Q2
            expect($encrypterQ3->decrypt($encryptedQ1))->toBe($userData);
            expect($encrypterQ3->decrypt($encryptedQ2))->toBe($newData);

            // Q4 : Rotation vers nouvelle clé, garder seulement Q3 et Q2 (abandonner Q1)
            $configQ4               = config('encryption');
            $configQ4['key']        = $q4Key;
            $configQ4['previous_keys'] = [$q3Key, $q2Key];
            $encrypterQ4 = $this->encryption->initialize((object) $configQ4);

            // Peut encore lire les données Q2 et Q3
            expect($encrypterQ4->decrypt($encryptedQ2))->toBe($newData);

            // Mais les données Q1 ne sont plus accessibles (comme prévu)
            expect(function () use ($encrypterQ4, $encryptedQ1): void {
                $encrypterQ4->decrypt($encryptedQ1);
            })->toThrow(new EncryptionException());
        });
    });
});
