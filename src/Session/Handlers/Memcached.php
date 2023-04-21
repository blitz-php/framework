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

use BlitzPHP\Session\SessionException;
use BlitzPHP\Utilities\Date;
use Memcached as BaseMemcached;

/**
 * Gestionnaire de session utilisant Memcache pour la persistance
 */
class Memcached extends BaseHandler
{
    /**
     * Memcached instance
     */
    protected ?BaseMemcached $memcached = null;

    /**
     * cle de verouillage
     */
    protected ?string $lockKey = null;

    /**
     * {@inheritDoc}
     *
     * @throws SessionException
     */
    public function init(array $config, string $ipAddress): bool
    {
        parent::init($config, $ipAddress);

        if (empty($this->_config['savePath'])) {
            throw SessionException::emptySavepath();
        }

        // Ajouter un nom de cookie de session pour plusieurs cookies de session.
        $this->_config['keyPrefix'] .= $this->_config['cookie_name'];

        if ($this->_config['matchIP'] === true) {
            $this->_config['keyPrefix'] .= $this->ipAddress . ':';
        }

        if (! empty($this->_config['keyPrefix'])) {
            ini_set('memcached.sess_prefix', $this->_config['keyPrefix']);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function open(string $path, string $name): bool
    {
        $this->memcached = new BaseMemcached();
        $this->memcached->setOption(BaseMemcached::OPT_BINARY_PROTOCOL, true); // requis pour l'utilisation de touch()

        $serverList = [];

        foreach ($this->memcached->getServerList() as $server) {
            $serverList[] = $server['host'] . ':' . $server['port'];
        }

        if (
            ! preg_match_all(
                '#,?([^,:]+)\:(\d{1,5})(?:\:(\d+))?#',
                $this->_config['savePath'],
                $matches,
                PREG_SET_ORDER
            )
        ) {
            $this->memcached = null;
            $this->logMessage("Session\u{a0}: format de chemin d'enregistrement Memcached non valide\u{a0}:" . $this->_config['savePath']);

            return false;
        }

        foreach ($matches as $match) {
            // Si Memcached a déjà ce serveur (ou si le port est invalide), ignorez-le
            if (in_array($match[1] . ':' . $match[2], $serverList, true)) {
                $this->logMessage(
                    "Session\u{a0}: le pool de serveurs Memcached a déjà" . $match[1] . ':' . $match[2],
                    'debug'
                );

                continue;
            }

            if (! $this->memcached->addServer($match[1], (int) $match[2], $match[3] ?? 0)) {
                $this->logMessage(
                    'Impossible d\'ajouter ' . $match[1] . ':' . $match[2] . ' au pool de serveurs Memcached.'
                );
            } else {
                $serverList[] = $match[1] . ':' . $match[2];
            }
        }

        if (empty($serverList)) {
            $this->logMessage("Session\u{a0}: le pool de serveurs Memcached est vide.");

            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $id): false|string
    {
        if (isset($this->memcached) && $this->lockSession($id)) {
            if (! isset($this->sessionID)) {
                $this->sessionID = $id;
            }

            $data = (string) $this->memcached->get($this->_config['keyPrefix'] . $id);

            $this->fingerprint = md5($data);

            return $data;
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $id, string $data): bool
    {
        if (! isset($this->memcached)) {
            return false;
        }

        if ($this->sessionID !== $id) {
            if (! $this->releaseLock() || ! $this->lockSession($id)) {
                return false;
            }

            $this->fingerprint = md5('');
            $this->sessionID   = $id;
        }

        if (isset($this->lockKey)) {
            $this->memcached->replace($this->lockKey, Date::now()->getTimestamp(), 300);

            if ($this->fingerprint !== ($fingerprint = md5($data))) {
                if ($this->memcached->set($this->_config['keyPrefix'] . $id, $data, $this->_config['expiration'])) {
                    $this->fingerprint = $fingerprint;

                    return true;
                }

                return false;
            }

            return $this->memcached->touch($this->_config['keyPrefix'] . $id, $this->_config['expiration']);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): bool
    {
        if (isset($this->memcached)) {
            if (isset($this->lockKey)) {
                $this->memcached->delete($this->lockKey);
            }

            if (! $this->memcached->quit()) {
                return false;
            }

            $this->memcached = null;

            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(string $id): bool
    {
        if (isset($this->memcached, $this->lockKey)) {
            $this->memcached->delete($this->_config['keyPrefix'] . $id);

            return $this->destroyCookie();
        }

        return false;
    }

    /**
     * Acquiert un verrou émulé.
     */
    protected function lockSession(string $sessionID): bool
    {
        if (isset($this->lockKey)) {
            return $this->memcached->replace($this->lockKey, Date::now()->getTimestamp(), 300);
        }

        $lockKey = $this->_config['keyPrefix'] . $sessionID . ':lock';
        $attempt = 0;

        do {
            if ($this->memcached->get($lockKey)) {
                sleep(1);

                continue;
            }

            if (! $this->memcached->set($lockKey, Date::now()->getTimestamp(), 300)) {
                $this->logMessage(
                    "Session\u{a0}: erreur lors de la tentative d'obtention du verrou pour" . $this->_config['keyPrefix'] . $sessionID
                );

                return false;
            }

            $this->lockKey = $lockKey;
            break;
        } while (++$attempt < 30);

        if ($attempt === 30) {
            $this->logMessage(
                'Session : Impossible d\'obtenir le verrou pour ' . $this->_config['keyPrefix'] . $sessionID . ' après 30 tentatives, abandon.'
            );

            return false;
        }

        $this->lock = true;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function releaseLock(): bool
    {
        if (isset($this->memcached, $this->lockKey) && $this->lock) {
            if (
                ! $this->memcached->delete($this->lockKey)
                && $this->memcached->getResultCode() !== BaseMemcached::RES_NOTFOUND
            ) {
                $this->logMessage(
                    "Session\u{a0}: erreur lors de la tentative de libération du verrou pour" . $this->lockKey
                );

                return false;
            }

            $this->lockKey = null;
            $this->lock    = false;
        }

        return true;
    }
}
