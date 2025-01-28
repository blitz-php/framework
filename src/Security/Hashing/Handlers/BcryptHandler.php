<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Security\Hashing\Handlers;

use BlitzPHP\Contracts\Security\HasherInterface;
use Error;
use RuntimeException;

/**
 * Gestionnaire de hashage basé sur Bcrypt
 *
 * @credit <a href="http://www.laravel.com">Laravel 11 - \Illuminate\Hashing\BcryptHasher</a>
 */
class BcryptHandler extends BaseHandler implements HasherInterface
{
    /**
     * Le facteur de coût par défaut.
     */
    protected int $rounds = 12;

    /**
     * Indique s'il faut effectuer une vérification de l'algorithme.
     */
    protected bool $verifyAlgorithm = false;

    /**
     * Créer une nouvelle instance de Hacheur.
     */
    public function __construct(array $options = [])
    {
        $this->rounds          = $options['rounds'] ?? $this->rounds;
        $this->verifyAlgorithm = $options['verify'] ?? $this->verifyAlgorithm;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    public function make(string $value, array $options = []): string
    {
        try {
            $hash = password_hash($value, PASSWORD_BCRYPT, [
                'cost' => $this->cost($options),
            ]);
        } catch (Error) {
            throw new RuntimeException("Le hachage Bcrypt n'est pas supporté.");
        }

        return $hash;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        if ($hashedValue === '') {
            return false;
        }

        if ($this->verifyAlgorithm && ! $this->isUsingCorrectAlgorithm($hashedValue)) {
            throw new RuntimeException("Ce mot de passe n'utilise pas l'algorithme Bcrypt.");
        }

        return parent::check($value, $hashedValue, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, [
            'cost' => $this->cost($options),
        ]);
    }

    /**
     * Vérifie que la configuration est inférieure ou égale à ce qui est configuré.
     *
     * @internal
     */
    public function verifyConfiguration(string $value): bool
    {
        return $this->isUsingCorrectAlgorithm($value) && $this->isUsingValidOptions($value);
    }

    /**
     * Vérifie l'algorithme de la valeur hachée.
     */
    protected function isUsingCorrectAlgorithm(string $hashedValue): bool
    {
        return $this->info($hashedValue)['algoName'] === 'bcrypt';
    }

    /**
     * Vérifie les options de la valeur hachée.
     */
    protected function isUsingValidOptions(string $hashedValue): bool
    {
        ['options' => $options] = $this->info($hashedValue);

        if (! is_int($options['cost'] ?? null)) {
            return false;
        }

        return ! ($options['cost'] > $this->rounds);
    }

    /**
     * Défini le facteur de travail du mot de passe par défaut.
     */
    public function setRounds(int $rounds): self
    {
        $this->rounds = (int) $rounds;

        return $this;
    }

    /**
     * Extrait la valeur du coût a partir du tableau d'options.
     */
    protected function cost(array $options = []): int
    {
        return $options['rounds'] ?? $this->rounds;
    }
}
