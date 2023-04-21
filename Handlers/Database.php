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

use BlitzPHP\Contracts\Database\BuilderInterface;
use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Session\SessionException;

/**
 * Gestionnaire de session de base de données de base
 *
 * N'utilisez pas cette classe. Utilisez une classe de gestionnaire spécifique à la base de données.
 */
class Database extends BaseHandler
{
    /**
     * Groupe de bases de données à utiliser pour le stockage.
     */
    protected string $group = 'default';

    /**
     * Le nom de la table pour stocker les informations de session.
     */
    protected string $table = 'blitz_sessions';

    /**
     * L'instance de connexion à la base de données.
     */
    protected ConnectionInterface $db;

    /**
     * The database type
     *
     * @var string
     */
    protected $platform;

    /**
     * Drapeau specifiant que la ligne existe
     */
    protected bool $rowExists = false;

    /**
     * Préfixe d'identification pour les cookies à sessions multiples
     */
    protected string $idPrefix = '';

    /**
     * @throws SessionException
     */
    public function init(array $config, string $ipAddress): bool
    {
        parent::init($config, $ipAddress);

        if (! empty($config['group'])) {
            $this->group = $config['group'];
        }

        $this->idPrefix = $this->_config['cookie_name'] . ':';
        $this->table    = $this->_config['savePath'];

        if (empty($this->table)) {
            throw SessionException::missingDatabaseTable();
        }

        // $this->db       = Database::connect($this->group);
        $this->platform = $this->db->getPlatform();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function open(string $path, string $name): bool
    {
        if (empty($this->db->conn)) {
            $this->db->initialize();
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $id): false|string
    {
        if ($this->lockSession($id) === false) {
            $this->fingerprint = md5('');

            return '';
        }

        if (! isset($this->sessionID)) {
            $this->sessionID = $id;
        }

        $builder = $this->db->table($this->table)->where('id', $this->idPrefix . $id);

        if ($this->_config['matchIP']) {
            $builder = $builder->where('ip_address', $this->ipAddress);
        }

        $this->setSelect($builder);

        $result = $builder->first();

        if ($result === null) {
            // PHP7 réutilisera le même objet SessionHandler après la régénération de l'ID,
            // nous devons donc le définir explicitement sur FALSE au lieu de nous fier à la valeur par défaut ...
            $this->rowExists   = false;
            $this->fingerprint = md5('');

            return '';
        }

        $result = is_bool($result) ? '' : $this->decodeData($result->data);

        $this->fingerprint = md5($result);
        $this->rowExists   = true;

        return $result;
    }

    /**
     * Définit la clause SELECT
     */
    protected function setSelect(BuilderInterface $builder)
    {
        $builder->select('data');
    }

    /**
     * Décode les données de colonne
     */
    protected function decodeData(string $data): string|false
    {
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $id, string $data): bool
    {
        if ($this->lock === false) {
            return $this->fail();
        }

        if ($this->sessionID !== $id) {
            $this->rowExists = false;
            $this->sessionID = $id;
        }

        if ($this->rowExists === false) {
            $insertData = [
                'id'         => $this->idPrefix . $id,
                'ip_address' => $this->ipAddress,
                'data'       => $this->prepareData($data),
            ];

            if (! $this->db->table($this->table)->set('timestamp', 'now()', false)->insert($insertData)) {
                return $this->fail();
            }

            $this->fingerprint = md5($data);
            $this->rowExists   = true;

            return true;
        }

        $builder = $this->db->table($this->table)->where('id', $this->idPrefix . $id);

        if ($this->_config['matchIP']) {
            $builder = $builder->where('ip_address', $this->ipAddress);
        }

        $updateData = [];

        if ($this->fingerprint !== md5($data)) {
            $updateData['data'] = $this->prepareData($data);
        }

        if (! $builder->set('timestamp', 'now()', false)->update($updateData)) {
            return $this->fail();
        }

        $this->fingerprint = md5($data);

        return true;
    }

    /**
     * Defini l'instance de la database a utiliser
     */
    public function setDatabase(ConnectionInterface $db): self
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Préparer les données à insérer/mettre à jour
     */
    protected function prepareData(string $data): string
    {
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): bool
    {
        return ($this->lock && ! $this->releaseLock()) ? $this->fail() : true;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(string $id): bool
    {
        if ($this->lock) {
            $builder = $this->db->table($this->table)->where('id', $this->idPrefix . $id);

            if ($this->_config['matchIP']) {
                $builder = $builder->where('ip_address', $this->ipAddress);
            }

            if (! $builder->delete()) {
                return $this->fail();
            }
        }

        if ($this->close()) {
            $this->destroyCookie();

            return true;
        }

        return $this->fail();
    }

    /**
     * {@inheritDoc}
     */
    public function gc(int $max_lifetime): false|int
    {
        $separator = ' ';
        $interval  = implode($separator, ['', "{$max_lifetime} second", '']);

        return $this->db->table($this->table)->where(
            'timestamp <',
            "now() - INTERVAL {$interval}",
            false
        )->delete() ? 1 : $this->fail();
    }

    /**
     * {@inheritDoc}
     */
    protected function releaseLock(): bool
    {
        if (! $this->lock) {
            return true;
        }

        // BD non supportée ? Laissez le parent gérer la version simple.
        return parent::releaseLock();
    }
}
