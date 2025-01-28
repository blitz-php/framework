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
 * Gestionnaire de hashage basé sur Argon
 *
 * @credit <a href="http://www.laravel.com">Laravel 11 - \Illuminate\Hashing\ArgonHasher</a>
 */
class ArgonHandler extends BaseHandler implements HasherInterface
{
    /**
     * Le facteur de coût de la mémoire par défaut.
     */
    protected int $memory = 1024;

    /**
     * Le facteur de coût du temps par défaut.
     */
    protected int $time = 2;

    /**
     * Le facteur de filetage par défaut.
     */
    protected int $threads = 2;

    /**
     * Indique s'il faut effectuer une vérification de l'algorithme.
     */
    protected bool $verifyAlgorithm = false;

    /**
     * Créer une nouvelle instance de Hacheur.
     */
    public function __construct(array $options = [])
    {
        $this->time            = $options['time'] ?? $this->time;
        $this->memory          = $options['memory'] ?? $this->memory;
        $this->threads         = $this->threads($options);
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
            $hash = password_hash($value, $this->algorithm(), [
                'memory_cost' => $this->memory($options),
                'time_cost'   => $this->time($options),
                'threads'     => $this->threads($options),
            ]);
        } catch (Error) {
            throw new RuntimeException("Le hachage Argon2 n'est pas supporté.");
        }

        return $hash;
    }

    /**
     * Obtient l'algorithme à utiliser pour le hachage.
     */
    protected function algorithm(): string
    {
        return PASSWORD_ARGON2I;
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
            throw new RuntimeException("Ce mot de passe n'utilise pas l'algorithme Argon2i.");
        }

        return parent::check($value, $hashedValue, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return password_needs_rehash($hashedValue, $this->algorithm(), [
            'memory_cost' => $this->memory($options),
            'time_cost'   => $this->time($options),
            'threads'     => $this->threads($options),
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
        return $this->info($hashedValue)['algoName'] === 'argon2i';
    }

    /**
     * Vérifie les options de la valeur hachée.
     */
    protected function isUsingValidOptions(string $hashedValue): bool
    {
        ['options' => $options] = $this->info($hashedValue);

        if (
            ! is_int($options['memory_cost'] ?? null)
            || ! is_int($options['time_cost'] ?? null)
            || ! is_int($options['threads'] ?? null)
        ) {
            return false;
        }

        return ! (
            $options['memory_cost'] > $this->memory
            || $options['time_cost'] > $this->time
            || $options['threads'] > $this->threads
        );
    }

    /**
     * Définit le facteur de mémoire du mot de passe par défaut.
     */
    public function setMemory(int $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * Définit le facteur de synchronisation du mot de passe par défaut.
     */
    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Définit le facteur de filtrage du mot de passe par défaut.
     */
    public function setThreads(int $threads): self
    {
        $this->threads = $threads;

        return $this;
    }

    /**
     * Extrait la valeur du coût de la mémoire du tableau d'options.
     */
    protected function memory(array $options): int
    {
        return $options['memory'] ?? $this->memory;
    }

    /**
     * Extrait la valeur du coût du temps du tableau des options.
     */
    protected function time(array $options): int
    {
        return $options['time'] ?? $this->time;
    }

    /**
     * Extrait la valeur du facteur de filtrage du tableau d'options.
     */
    protected function threads(array $options): int
    {
        if (defined('PASSWORD_ARGON2_PROVIDER') && PASSWORD_ARGON2_PROVIDER === 'sodium') {
            return 1;
        }

        return $options['threads'] ?? $this->threads;
    }
}
