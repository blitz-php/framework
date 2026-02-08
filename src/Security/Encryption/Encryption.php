<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Security\Encryption;

use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Exceptions\EncryptionException;

/**
 * Gestionnaire de chiffrement BlitzPHP
 *
 * Fournit un cryptage à clé bidirectionnel via les extensions PHP Sodium et/ou OpenSSL.
 * Cette classe détermine le pilote, le chiffrement et le mode à utiliser, puis initialise le gestionnaire de chiffrement approprié.
 *
 * @credit <a href="http://www.codeigniter.com">CodeIgniter 4.4 - \CodeIgniter\Encryption\Encryption</a>
 */
class Encryption implements EncrypterInterface
{
    /**
     * Le chiffreur que nous créons
     */
    protected ?EncrypterInterface $encrypter = null;

    /**
     * Le pilote utilisé
     */
    protected string $driver = '';

    /**
     * La clé/graine utilisée
     */
    protected string $key = '';

    /**
     * La clé HMAC dérivée
     */
    protected string $hmacKey;

    /**
     * Digest HMAC à utiliser
     */
    protected string $digest = 'SHA512';

    /**
     * Pilotes vers les classes de gestionnaires, par ordre de préférence
     */
    protected array $drivers = [
        'OpenSSL',
        'Sodium',
    ];

    /**
     * Gestionnaires à installer
     *
     * @var array<string, bool>
     */
    protected array $handlers = [];

    /**
     * Constructeur
     *
     * @param object|null $config Configuration de chiffrement
     *
     * @throws EncryptionException
     */
    public function __construct(protected ?object $config = null)
    {
        $config ??= (object) config('encryption');

        $this->config = $config;
        $this->key    = $this->parseEncryptionKey($config->key);
        $this->driver = $config->driver;
        $this->digest = $config->digest ?? 'SHA512';

        $this->handlers = [
            'OpenSSL' => extension_loaded('openssl'),
            // le SodiumHandler utilise une API (comme sodium_pad) qui n'est disponible que sur la version 1.0.14+
            'Sodium' => extension_loaded('sodium') && version_compare(SODIUM_LIBRARY_VERSION, '1.0.14', '>='),
        ];

        if (! in_array($this->driver, $this->drivers, true) || (array_key_exists($this->driver, $this->handlers) && ! $this->handlers[$this->driver])) {
            throw EncryptionException::noHandlerAvailable($this->driver);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function encrypt(string $data, array|string|null $params = null): string
    {
        return base64_encode($this->encrypter()->encrypt($data, $params));
    }

    /**
     * {@inheritDoc}
     */
    public function decrypt(string $data, array|string|null $params = null): string
    {
        if (function_exists('mb_check_encoding')) {
            $data = ! mb_check_encoding($data, 'UTF-8') ? $data : base64_decode($data, true);
        }

        return $this->encrypter()->decrypt($data, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function getKey(): string
    {
        return $this->encrypter()->getKey();
    }

    /**
     * Crée un nouveau chiffreur avec rotation de clés activée
     *
     * @param string $currentKey   Clé actuelle de chiffrement
     * @param array  $previousKeys Clés précédentes pour le fallback
     * @param array  $config       Configuration supplémentaire
     *
     * @return self Instance de chiffrement avec rotation de clés
     */
    public static function withKeyRotation(string $currentKey, array $previousKeys, array $config = []): self
    {
        $config                = (object) array_merge(config('encryption'), $config);
        $config->key           = $currentKey;
        $config->previous_keys = $previousKeys;

        return new self($config);
    }

    /**
     * Initialiser ou réinitialiser un chiffreur
     *
     * @param object|null $config Configuration de chiffrement
     *
     * @return EncrypterInterface Le chiffreur initialisé
     *
     * @throws EncryptionException
     */
    public function initialize(?object $config = null): EncrypterInterface
    {
        if ($config) {
            $this->key    = $this->parseEncryptionKey($config->key);
            $this->driver = $config->driver;
            $this->digest = $config->digest ?? 'SHA512';
        }

        if ($this->driver === '') {
            throw EncryptionException::noDriverRequested();
        }

        if (! in_array($this->driver, $this->drivers, true)) {
            throw EncryptionException::unKnownHandler($this->driver);
        }

        if ($this->key === '' || $this->key === '0') {
            throw EncryptionException::needsStarterKey();
        }

        $this->hmacKey = bin2hex(hash_hkdf($this->digest, $this->key));

        $handlerName     = 'BlitzPHP\\Security\\Encryption\\Handlers\\' . $this->driver . 'Handler';
        $this->encrypter = new $handlerName($config);

        if (property_exists($config, 'previous_keys')) {
            if ([] !== $parsedKeys = $this->parsePreviousKeys($config->previous_keys)) {
                $this->encrypter = new KeyRotationDecorator($this->encrypter, $parsedKeys);
            }
        }

        return $this->encrypter;
    }

    /**
     * Créer une clé aléatoire
     *
     * @param int $length Longueur de la clé en octets
     *
     * @return string Clé générée aléatoirement
     */
    public static function createKey(int $length = 32): string
    {
        return random_bytes($length);
    }

    /**
     * Fourni un accès en lecture seule à certaines de nos propriétés
     *
     * @param string $key Nom de la propriété
     *
     * @return array|bool|int|string|null Valeur de la propriété ou null
     */
    public function __get($key)
    {
        if ($this->__isset($key)) {
            return $this->{$key};
        }

        return null;
    }

    /**
     * Assure la vérification de certaines de nos propriétés
     *
     * @param string $key Nom de la propriété
     */
    public function __isset($key): bool
    {
        return in_array($key, ['key', 'digest', 'driver', 'drivers'], true);
    }

    /**
     * Récupère ou initialise le chiffreur interne
     *
     * @return EncrypterInterface Le chiffreur
     *
     * @throws EncryptionException
     */
    private function encrypter(): EncrypterInterface
    {
        if (null === $this->encrypter) {
            $this->encrypter = $this->initialize($this->config);
        }

        return $this->encrypter;
    }

    /**
     * Parse les clés précédentes en un tableau formaté
     *
     * @param array|string $previous_keys Clés précédentes
     *
     * @return array Tableau des clés parsées
     */
    private function parsePreviousKeys(array|string $previous_keys): array
    {
        $keysArray = is_string($previous_keys)
            ? array_map('trim', explode(',', $previous_keys))
            : (array) $previous_keys;

        $parsedKeys = [];

        foreach ($keysArray as $key) {
            if (! empty($key)) {
                $parsedKeys[] = $this->parseEncryptionKey($key);
            }
        }

        return $parsedKeys;
    }

    /**
     * Parse une clé de chiffrement avec préfixe hex2bin: ou base64:
     *
     * @param string $key Clé à parser
     *
     * @return string Clé décodée
     */
    private function parseEncryptionKey(string $key): string
    {
        if (str_starts_with($key, 'hex2bin:')) {
            return hex2bin(substr($key, 8));
        }

        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7), true);
        }

        return $key;
    }
}
