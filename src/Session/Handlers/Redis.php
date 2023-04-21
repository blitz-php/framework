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
use Redis as BaseRedis;
use RedisException;

/**
 * Gestionnaire de session utilisant Redis pour la persistance
 */
class Redis extends BaseHandler
{
    private const DEFAULT_PORT     = 6379;
    private const DEFAULT_PROTOCOL = 'tcp';

    /**
     * phpRedis instance
     */
    protected ?BaseRedis $redis = null;

    /**
     * cle de verouillage
     */
    protected ?string $lockKey = null;

    /**
     * Drapeau pour les cles existantes
     */
    protected bool $keyExists = false;

    /**
     * {@inheritDoc}s
     *
     * @throws SessionException
     */
    public function init(array $config, string $ipAddress): bool
    {
        parent::init($config, $ipAddress);

        if (empty($config['expiration'])) {
            $this->_config['expiration'] = ini_get('session.gc_maxlifetime');
        }

        // Ajouter un nom de cookie de session pour plusieurs cookies de session.
        $this->_config['keyPrefix'] .= $this->_config['cookie_name'] . ':';

        $this->setSavePath();

        if ($this->_config['matchIP'] === true) {
            $this->_config['keyPrefix'] .= $this->ipAddress . ':';
        }

        return true;
    }

    protected function setSavePath(): void
    {
        if (empty($this->_config['savePath'])) {
            throw SessionException::emptySavepath();
        }

        if (preg_match('#(?:(tcp|tls)://)?([^:?]+)(?:\:(\d+))?(\?.+)?#', $this->_config['savePath'], $matches)) {
            if (! isset($matches[4])) {
                $matches[4] = ''; // Juste pour éviter les avis d'index indéfinis ci-dessous
            }

            $this->_config['savePath'] = [
                'protocol' => ! empty($matches[1]) ? $matches[1] : self::DEFAULT_PROTOCOL,
                'host'     => $matches[2],
                'port'     => empty($matches[3]) ? self::DEFAULT_PORT : $matches[3],
                'password' => preg_match('#auth=([^\s&]+)#', $matches[4], $match) ? $match[1] : null,
                'database' => preg_match('#database=(\d+)#', $matches[4], $match) ? (int) $match[1] : 0,
                'timeout'  => preg_match('#timeout=(\d+\.\d+|\d+)#', $matches[4], $match) ? (float) $match[1] : 0.0,
            ];

            preg_match('#prefix=([^\s&]+)#', $matches[4], $match) && $this->_config['keyPrefix'] = $match[1];
        } else {
            throw SessionException::invalidSavePathFormat($this->_config['savePath']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function open(string $path, string $name): bool
    {
        if (empty($this->_config['savePath'])) {
            return false;
        }

        $redis = new BaseRedis();

        if (! $redis->connect($this->_config['savePath']['protocol'] . '://' . $this->_config['savePath']['host'], ($this->_config['savePath']['host'][0] === '/' ? 0 : $this->_config['savePath']['port']), $this->_config['savePath']['timeout'])) {
            $this->logMessage("Session\u{a0}: Impossible de se connecter à Redis avec les paramètres configurés.");
        } elseif (isset($this->_config['savePath']['password']) && ! $redis->auth($this->_config['savePath']['password'])) {
            $this->logMessage("Session\u{a0}: impossible de s'authentifier auprès de l'instance Redis.");
        } elseif (isset($this->_config['savePath']['database']) && ! $redis->select($this->_config['savePath']['database'])) {
            $this->logMessage("Session\u{a0}: impossible de sélectionner la base de données Redis avec index " . $this->_config['savePath']['database']);
        } else {
            $this->redis = $redis;

            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $id): false|string
    {
        if (isset($this->redis) && $this->lockSession($id)) {
            if (! isset($this->sessionID)) {
                $this->sessionID = $id;
            }

            $data = $this->redis->get($this->_config['keyPrefix'] . $id);

            if (is_string($data)) {
                $this->keyExists = true;
            } else {
                $data = '';
            }

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
        if (! isset($this->redis)) {
            return false;
        }

        if ($this->sessionID !== $id) {
            if (! $this->releaseLock() || ! $this->lockSession($id)) {
                return false;
            }

            $this->keyExists = false;
            $this->sessionID = $id;
        }

        if (isset($this->lockKey)) {
            $this->redis->expire($this->lockKey, 300);

            if ($this->fingerprint !== ($fingerprint = md5($data)) || $this->keyExists === false) {
                if ($this->redis->set($this->_config['keyPrefix'] . $id, $data, $this->_config['expiration'])) {
                    $this->fingerprint = $fingerprint;
                    $this->keyExists   = true;

                    return true;
                }

                return false;
            }

            return $this->redis->expire($this->_config['keyPrefix'] . $id, $this->_config['expiration']);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): bool
    {
        if (isset($this->redis)) {
            try {
                $pingReply = $this->redis->ping();

                if (($pingReply === true) || ($pingReply === '+PONG')) {
                    if (isset($this->lockKey)) {
                        $this->redis->del($this->lockKey);
                    }

                    if (! $this->redis->close()) {
                        return false;
                    }
                }
            } catch (RedisException $e) {
                $this->logMessage("Session\u{a0}: RedisException obtenu sur close()\u{a0}: " . $e->getMessage());
            }

            $this->redis = null;

            return true;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(string $id): bool
    {
        if (isset($this->redis, $this->lockKey)) {
            if (($result = $this->redis->del($this->_config['keyPrefix'] . $id)) !== 1) {
                $this->logMessage("Session\u{a0}: Redis :: del() devrait renvoyer 1, a obtenu " . var_export($result, true) . ' instead.', 'debug');
            }

            return $this->destroyCookie();
        }

        return false;
    }

    /**
     * Acquires an emulated lock.
     */
    protected function lockSession(string $sessionID): bool
    {
        $lockKey = $this->_config['keyPrefix'] . $sessionID . ':lock';

        // PHP 7 réutilise l'objet SessionHandler lors de la régénération,
        // nous devons donc vérifier ici si la clé de verrouillage correspond à l'ID de session correct.
        if ($this->lockKey === $lockKey) {
            return $this->redis->expire($this->lockKey, 300);
        }

        $attempt = 0;

        do {
            if (($ttl = $this->redis->ttl($lockKey)) > 0) {
                sleep(1);

                continue;
            }

            if (! $this->redis->setex($lockKey, 300, (string) Date::now()->getTimestamp())) {
                $this->logMessage("Session\u{a0}: erreur lors de la tentative d'obtention du verrou pour " . $this->_config['keyPrefix'] . $sessionID);

                return false;
            }

            $this->lockKey = $lockKey;
            break;
        } while (++$attempt < 30);

        if ($attempt === 30) {
            $this->logMessage('Session: Impossible d\'obtenir le verrou pour ' . $this->_config['keyPrefix'] . $sessionID . ' after 30 attempts, aborting.');

            return false;
        }

        if ($ttl === -1) {
            $this->logMessage('Session: Serrure pour ' . $this->_config['keyPrefix'] . $sessionID . ' n\'avait pas de TTL, prioritaire.', 'debug');
        }

        $this->lock = true;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function releaseLock(): bool
    {
        if (isset($this->redis, $this->lockKey) && $this->lock) {
            if (! $this->redis->del($this->lockKey)) {
                $this->logMessage("Session\u{a0}: erreur lors de la tentative de libération du verrou pour " . $this->lockKey);

                return false;
            }

            $this->lockKey = null;
            $this->lock    = false;
        }

        return true;
    }
}
