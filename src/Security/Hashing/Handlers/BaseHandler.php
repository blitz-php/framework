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

/**
 * Gestionnaire de hashage de base
 *
 * @credit <a href="http://www.laravel.com">Laravel 11 - \Illuminate\Hashing\AbstractHasher</a>
 */
abstract class BaseHandler implements HasherInterface
{
    /**
     * {@inheritDoc}
     */
    public function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * {@inheritDoc}
     */
    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        if ($hashedValue === '') {
            return false;
        }

        return password_verify($value, $hashedValue);
    }
}
