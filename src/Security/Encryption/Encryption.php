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
    protected EncrypterInterface $encrypter;

    /**
     * Le pilote utilisé
     */
    protected string $driver;

    /**
     * The key/seed being used
     */
    protected string $key;

    /**
     * La clé HMAC dérivée
     */
    protected string $hmacKey;

    /**
     * HMAC digest à utiliser
     */
    protected string $digest = 'SHA512';

    /**
     * Pilotes aux classes de gestionnaires, par ordre de préférence
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
     * @throws EncryptionException
     */
    public function __construct(protected ?object $config = null)
    {
        $config ??= (object) config('encryption');
        
        $this->config = $config;
        $this->key    = $config->key;
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
    public function encrypt(string $data, null|array|string $params = null): string
    {
        return $this->encrypter()->encrypt($data, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function decrypt(string $data, null|array|string $params = null): string
    {
        return $this->encrypter()->decrypt($data, $params);
    }

    /**
     * Initialiser ou réinitialiser un chiffreur
     *
     * @throws EncryptionException
     */
    public function initialize(object $config = null): EncrypterInterface
    {
        if ($config) {
            $this->key    = $config->key;
            $this->driver = $config->driver;
            $this->digest = $config->digest ?? 'SHA512';
        }

        if (empty($this->driver)) {
            throw EncryptionException::noDriverRequested();
        }

        if (! in_array($this->driver, $this->drivers, true)) {
            throw EncryptionException::unKnownHandler($this->driver);
        }

        if (empty($this->key)) {
            throw EncryptionException::needsStarterKey();
        }

        $this->hmacKey = bin2hex(\hash_hkdf($this->digest, $this->key));

        $handlerName     = 'BlitzPHP\\Security\\Encryption\\Handlers\\' . $this->driver . 'Handler';
        $this->encrypter = new $handlerName($config);

        return $this->encrypter;
    }

    /**
     * Créer une clé aléatoire
     */
    public static function createKey(int $length = 32): string
    {
        return random_bytes($length);
    }

    /**
     * Fourni un accès en lecture seule à certaines de nos propriétés
     *
     * @param string $key Nom de la propriete
     *
     * @return array|bool|int|string|null
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
     * @param string $key Nom de la propriete
     */
    public function __isset($key): bool
    {
        return in_array($key, ['key', 'digest', 'driver', 'drivers'], true);
    }

    private function encrypter(): EncrypterInterface
    {
        if (null === $this->encrypter) {
            $this->encrypter = $this->initialize($this->config);
        }

        return $this->encrypter;
    }
}
