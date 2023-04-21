<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Session\Handlers;

/**
 * Gestionnaire de session utilisant un tableau statique pour le stockage.
 * Destiné uniquement à être utilisé pendant les tests.
 */
class ArrayHandler extends BaseHandler
{
    protected static $cache = [];

    /**
     * {@inheritDoc}
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $id): string|false
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $id, string $data): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(string $id): bool
    {
        return true;
    }
}
