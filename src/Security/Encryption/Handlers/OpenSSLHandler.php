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
 * Gestionnaire de chiffrement basé sur la librairie OpenSSL
 *
 * @credit <a href="http://www.codeigniter.com">CodeIgniter 4.4 - \CodeIgniter\Encryption\Handlers\OpenSSLHandler</a>
 */
class OpenSSLHandler extends BaseHandler
{
    /**
     * HMAC digest à utiliser
     */
    protected string $digest = 'SHA512';

    /**
     * Liste des algorithmes HMAC pris en charge
     *
     * @var array [name => digest size]
     */
    protected array $digestSize = [
        'SHA224' => 28,
        'SHA256' => 32,
        'SHA384' => 48,
        'SHA512' => 64,
    ];

    /**
     * Chiffrement à utiliser
     */
    protected string $cipher = 'AES-256-CTR';

    /**
     * Indique si le texte chiffré doit être brut. S'il est défini sur false, il sera codé en base64.
     */
    protected bool $rawData = true;

    /**
     * Informations sur la clé de cryptage.
     * Ce paramètre est uniquement utilisé par OpenSSLHandler.
     */
    public string $encryptKeyInfo = '';

    /**
     * Informations sur la clé d'authentification.
     * Ce paramètre est uniquement utilisé par OpenSSLHandler.
     */
    public string $authKeyInfo = '';

    /**
     * {@inheritDoc}
     */
    public function encrypt(string $data, array|string|null $params = null): string
    {
        // Autoriser le remplacement de clé
        if ($params) {
            $this->key = is_array($params) && isset($params['key']) ? $params['key'] : $params;
        }

        if ($this->key === '' || $this->key === '0') {
            throw EncryptionException::needsStarterKey();
        }

        // derive a secret key
        $encryptKey = \hash_hkdf($this->digest, $this->key, 0, $this->encryptKeyInfo);

        // cryptage de base
        $iv = ($ivSize = \openssl_cipher_iv_length($this->cipher)) ? \openssl_random_pseudo_bytes($ivSize) : null;

        $data = \openssl_encrypt($data, $this->cipher, $encryptKey, OPENSSL_RAW_DATA, $iv);

        if ($data === false) {
            throw EncryptionException::encryptionFailed();
        }

        $result = $this->rawData ? $iv . $data : base64_encode($iv . $data);

        // dériver une clé secrète
        $authKey = \hash_hkdf($this->digest, $this->key, 0, $this->authKeyInfo);

        $hmacKey = \hash_hmac($this->digest, $result, $authKey, $this->rawData);

        return $hmacKey . $result;
    }

    /**
     * {@inheritDoc}
     */
    public function decrypt(string $data, array|string|null $params = null): string
    {
        // Autoriser le remplacement de clé
        if ($params) {
            $this->key = is_array($params) && isset($params['key']) ? $params['key'] : $params;
        }

        if ($this->key === '' || $this->key === '0') {
            throw EncryptionException::needsStarterKey();
        }

        // dériver une clé secrète
        $authKey = \hash_hkdf($this->digest, $this->key, 0, $this->authKeyInfo);

        $hmacLength = $this->rawData
            ? $this->digestSize[$this->digest]
            : $this->digestSize[$this->digest] * 2;

        $hmacKey  = self::substr($data, 0, $hmacLength);
        $data     = self::substr($data, $hmacLength);
        $hmacCalc = \hash_hmac($this->digest, $data, $authKey, $this->rawData);

        if (! hash_equals($hmacKey, $hmacCalc)) {
            throw EncryptionException::authenticationFailed();
        }

        $data = $this->rawData ? $data : base64_decode($data, true);

        if ($ivSize = \openssl_cipher_iv_length($this->cipher)) {
            $iv   = self::substr($data, 0, $ivSize);
            $data = self::substr($data, $ivSize);
        } else {
            $iv = null;
        }

        // dériver une clé secrète
        $encryptKey = \hash_hkdf($this->digest, $this->key, 0, $this->encryptKeyInfo);

        return \openssl_decrypt($data, $this->cipher, $encryptKey, OPENSSL_RAW_DATA, $iv);
    }
}
