<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Security\Encryption\Handlers;

use BlitzPHP\Exceptions\EncryptionException;

/**
 * Gestionnaire de chiffrement basé sur la librairie Sodium
 *
 * @see https://github.com/jedisct1/libsodium/issues/392
 * @credit <a href="http://www.codeigniter.com">CodeIgniter 4.4 - \CodeIgniter\Encryption\Handlers\SodiumHandler</a>
 */
class SodiumHandler extends BaseHandler
{
    /**
     * Taille du bloc pour le message de remplissage.
     */
    protected int $blockSize = 16;

    /**
     * {@inheritDoc}
     */
    public function encrypt(string $data, null|array|string $params = null): string
    {
        $this->parseParams($params);

        if ($this->key === '' || $this->key === '0') {
            throw EncryptionException::needsStarterKey();
        }

        // créer un occasionnel pour cette opération
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES); // 24 bytes

        // ajouter du remplissage avant de chiffrer les données
        if ($this->blockSize <= 0) {
            throw EncryptionException::encryptionFailed();
        }

        $data = sodium_pad($data, $this->blockSize);

        // chiffrer le message et combiner avec occasionnel
        $ciphertext = $nonce . sodium_crypto_secretbox($data, $nonce, $this->key);

        // tampons de nettoyage
        sodium_memzero($data);
        sodium_memzero($this->key);

        return $ciphertext;
    }

    /**
     * {@inheritDoc}
     */
    public function decrypt(string $data, null|array|string $params = null): string
    {
        $this->parseParams($params);

        if (empty($this->key)) {
            throw EncryptionException::needsStarterKey();
        }

        if (mb_strlen($data, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            // le message a été tronqué
            throw EncryptionException::authenticationFailed();
        }

        // Extraire des informations à partir de données cryptées
        $nonce      = self::substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = self::substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        // décrypter les données
        $data = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);

        if ($data === false) {
            // le message a été falsifié pendant le transit
            throw EncryptionException::authenticationFailed(); // @codeCoverageIgnore
        }

        // supprimer le remplissage supplémentaire pendant le cryptage
        if ($this->blockSize <= 0) {
            throw EncryptionException::authenticationFailed();
        }

        $data = sodium_unpad($data, $this->blockSize);

        // tampons de nettoyage
        sodium_memzero($ciphertext);
        sodium_memzero($this->key);

        return $data;
    }

    /**
     * Analysez les $params avant de faire l'affectation.
     *
     * @throws EncryptionException si la cle est vide
     */
    protected function parseParams(null|array|string $params): void
    {
        if ($params === null) {
            return;
        }

        if (is_array($params)) {
            if (isset($params['key'])) {
                $this->key = $params['key'];
            }

            if (isset($params['block_size']) || isset($params['blockSize'])) {
                $this->blockSize = $params['block_size'] ?? $params['blockSize'];
            }

            return;
        }

        $this->key = $params;
    }
}
