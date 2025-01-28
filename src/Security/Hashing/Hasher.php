<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Security\Hashing;

use BlitzPHP\Contracts\Security\HasherInterface;
use BlitzPHP\Exceptions\HashingException;

/**
 * Gestionnaire de hashage de BlitzPHP
 */
class Hasher implements HasherInterface
{
    /**
     * Le hasheur que nous utilisons
     */
    protected ?HasherInterface $hasher = null;

    /**
     * Le pilote utilisé
     */
    protected string $driver = '';

	/**
     * Pilotes aux classes de gestionnaires, par ordre de préférence
     */
    protected array $drivers = [
        'bcrypt',
        'argon',
        'argon2id',
    ];
    /**
     * Constructeur
     */
    public function __construct(protected ?object $config = null)
    {
        $config ??= (object) config('hashing');

        $this->config = $config;
		$this->driver = $config->driver;
    }

    /**
     * {@inheritDoc}
     */
    public function info(string $hashedValue): array
    {
        return $this->driver()->info($hashedValue);
    }

    /**
     * {@inheritDoc}
     */
    public function make(string $value, array $options = []): string
    {
        return $this->driver()->make($value, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        return $this->driver()->check($value, $hashedValue, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return $this->driver()->needsRehash($hashedValue, $options);
    }

    /**
     * Détermine si une chaîne donnée est déjà hachée.
     */
    public function isHashed(string $value): bool
    {
        return $this->driver()->info($value)['algo'] !== null;
    }

    /**
     * Vérifie que la configuration est inférieure ou égale à ce qui est configuré.
     *
     * @internal
     */
    public function verifyConfiguration(array $value): bool
    {
        return $this->driver()->verifyConfiguration($value);
    }

    /**
     * Initialiser ou réinitialiser le hasheur
     *
     * @throws EncryptionException
     */
    public function initialize(?object $config = null): HasherInterface
    {
        if ($config) {
            $this->driver = $config->driver;
        }

        if ($this->driver === '') {
            throw HashingException::noDriverRequested();
        }

        if (! in_array($this->driver, $this->drivers, true)) {
            throw HashingException::unKnownHandler($this->driver);
        }

        $handlerName  = 'BlitzPHP\\Security\\Hashing\\Handlers\\' . ucfirst($this->driver) . 'Handler';
		$params       = (array) $config;
		$this->hasher = new $handlerName($params[$this->driver]);

        return $this->hasher;
    }

    private function driver(): HasherInterface
    {
        if (null === $this->hasher) {
            $this->hasher = $this->initialize($this->config);
        }

        return $this->hasher;
    }
}
