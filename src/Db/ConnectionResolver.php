<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Db;

use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Contracts\Database\ConnectionResolverInterface;

class ConnectionResolver implements ConnectionResolverInterface
{
    protected string $defaultConnection = 'default';

    /**
     * {@inheritDoc}
     */
    public function connection(?string $name = null): ConnectionInterface
    {
        return $this->connect($name ?: $this->defaultConnection);
    }
    
    /**
     * {@inheritDoc}
     */
    public function connect($group = null, bool $shared = true): ConnectionInterface
    {
        return  Database::connect($group, $shared);
    }

    /**
     * {@inheritDoc}
     */
    public function connectionInfo(array|string|null $group = null): array
    {
        return Database::connectionInfo($group);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultConnection(): string
    {
        return $this->defaultConnection;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultConnection(string $name): void
    {
        $this->defaultConnection = $name;
    }
}