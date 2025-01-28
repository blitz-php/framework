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

use RuntimeException;

/**
 * Gestionnaire de hashage basé sur Argon2Id
 *
 * @credit <a href="http://www.laravel.com">Laravel 11 - \Illuminate\Hashing\Argon2IdHasher</a>
 */
class Argon2IdHandler extends ArgonHandler
{
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
            throw new RuntimeException("Ce mot de passe n'utilise pas l'algorithme Argon2id.");
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * {@inheritDoc}
     */
    protected function isUsingCorrectAlgorithm(string $hashedValue): bool
    {
        return $this->info($hashedValue)['algoName'] === 'argon2id';
    }

    /**
     * {@inheritDoc}
     */
    protected function algorithm(): string
    {
        return PASSWORD_ARGON2ID;
    }
}
