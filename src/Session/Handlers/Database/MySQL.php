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

use BlitzPHP\Session\Handlers\Database;

/**
 * Gestionnaire de session pour MySQL
 */
class MySQL extends Database
{
    /**
     * Verouille la session
     */
    protected function lockSession(string $sessionID): bool
    {
        $arg = md5($sessionID . ($this->_config['matchIP'] ? '_' . $this->ipAddress : ''));
        if ($this->db->query("SELECT GET_LOCK('{$arg}', 300) AS blitz_session_lock")->first()->blitz_session_lock) {
            $this->lock = $arg;

            return true;
        }

        return $this->fail();
    }

    /**
     *{@inheritDoc}
     */
    protected function releaseLock(): bool
    {
        if (! $this->lock) {
            return true;
        }

        if ($this->db->query("SELECT RELEASE_LOCK('{$this->lock}') AS blitz_session_lock")->first()->blitz_session_lock) {
            $this->lock = false;

            return true;
        }

        return $this->fail();
    }
}
