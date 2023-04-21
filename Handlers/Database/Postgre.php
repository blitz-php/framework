<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Session\Handlers\Database;

use BlitzPHP\Contracts\Database\BuilderInterface;
use BlitzPHP\Session\Handlers\Database;

/**
 * Gestionnaire de session pour Postgre
 */
class Postgre extends Database
{
    /**
     * {@inheritDoc}
     */
    protected function setSelect(BuilderInterface $builder)
    {
        $builder->select("encode(data, 'base64') AS data");
    }

    /**
     * {@inheritDoc}
     */
    protected function decodeData(string $data): string|false
    {
        return base64_decode(rtrim($data), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareData(string $data): string
    {
        return '\x' . bin2hex($data);
    }

    /**
     * {@inheritDoc}
     */
    public function gc(int $max_lifetime): false|int
    {
        $separator = '\'';
        $interval  = implode($separator, ['', "{$max_lifetime} second", '']);

        return $this->db->table($this->table)->where('timestamp <', "now() - INTERVAL {$interval}", false)->delete() ? 1 : $this->fail();
    }

    /**
     * Verouille la session
     */
    protected function lockSession(string $sessionID): bool
    {
        $arg = "hashtext('{$sessionID}')" . ($this->_config['matchIP'] ? ", hashtext('{$this->ipAddress}')" : '');
        if ($this->db->simpleQuery("SELECT pg_advisory_lock({$arg})")) {
            $this->lock = $arg;

            return true;
        }

        return $this->fail();
    }

    /**
     * {@inheritDoc}
     */
    protected function releaseLock(): bool
    {
        if (! $this->lock) {
            return true;
        }

        if ($this->db->simpleQuery("SELECT pg_advisory_unlock({$this->lock})")) {
            $this->lock = false;

            return true;
        }

        return $this->fail();
    }
}
