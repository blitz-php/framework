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

use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Database\Database as Db;
use InvalidArgumentException;

/**
 * Configuration pour la base de données
 */
class Database
{
    /**
     * Cache pour les instances de toutes les connections
     * qui ont été requetées en tant que instance partagées
     *
     * @var array<string, ConnectionInterface>
     */
    protected static $instances = [];

    /**
     * L'instance principale utilisée pour gérer toutes les ouvertures à la base de données.
     *
     * @var Db|null
     */
    protected static $factory;

    /**
     * Recupere les informations a utiliser pour la connexion a la base de données
     *
     * @return array [group, configuration]
     */
    public static function connectionInfo(array|string|null $group = null): array
    {
        if (is_array($group)) {
            $config = $group;
            $group  = 'custom-' . md5(json_encode($config));
        }

        $config ??= config('database');

        if (empty($group)) {
            $group = $config['connection'] ?? 'auto';
        }
        if ($group === 'auto') {
            $group = on_test() ? 'test' : (on_prod() ? 'production' : 'development');
        }

        if (! isset($config[$group]) && strpos($group, 'custom-') === false) {
            $group = 'default';
        }

        if (is_string($group) && ! isset($config[$group]) && strpos($group, 'custom-') !== 0) {
            throw new InvalidArgumentException($group . ' is not a valid database connection group.');
        }

        if (strpos($group, 'custom-') !== false) {
            $config = [$group => $config];
        }

        $config = $config[$group];

        if (str_contains($config['driver'], 'sqlite') && $config['database'] !== ':memory:' && ! str_contains($config['database'], DS)) {
            $config['database'] = APP_STORAGE_PATH . $config['database'];
        }

        return [$group, $config];
    }

    /**
     * Ouvre une connexion
     *
     * @param array|ConnectionInterface|string|null $group  Nom du groupe de connexion à utiliser, ou un tableau de paramètres de configuration.
     * @param bool                                  $shared Doit-on retourner une instance partagée
     */
    public static function connect($group = null, bool $shared = true): ConnectionInterface
    {
        // Si on a deja passer une connection, pas la peine de continuer
        if ($group instanceof ConnectionInterface) {
            return $group;
        }

        // Si le package "blitz-php/database" n'existe pas on renvoi une fake connection
        // Ceci est utile juste pour eviter le bug avec le service provider
        if (! class_exists(Db::class)) {
            return static::createMockConnection();
        }

        [$group, $config] = static::connectionInfo($group);

        if ($shared && isset(static::$instances[$group])) {
            return static::$instances[$group];
        }

        static::ensureFactory();

        $connection = static::$factory->load(
            $config,
            $group,
            Services::logger(),
            Services::event()
        );

        static::$instances[$group] = &$connection;

        return $connection;
    }

    /**
     * Renvoie un tableau contenant toute les connxions deja etablies.
     */
    public static function getConnections(): array
    {
        return static::$instances;
    }

    /**
     * Charge et retourne une instance du Creator specifique au groupe de la base de donnees
     * et charge le groupe s'il n'est pas encore chargé.
     *
     * @param array|ConnectionInterface|string|null $group
     *
     * @return \BlitzPHP\Database\Creator\BaseCreator
     */
    public static function creator($group = null, bool $shared = true)
    {
        $db = static::connect($group, $shared);

        return static::$factory->loadCreator($db);
    }

    /**
     * Retourne une nouvelle de la classe Database Utilities.
     *
     * @param array|string|null $group
     *
     * @return \Blitzphp\Database\BaseUtils
     */
    public static function utils($group = null)
    {
        $db = static::connect($group);

        return static::$factory->loadUtils($db);
    }

    /**
     * S'assure que le gestionnaire de la base de données est chargé et prêt à être utiliser.
     */
    protected static function ensureFactory()
    {
        if (static::$factory instanceof Db) {
            return;
        }

        static::$factory = new Db();
    }

    private static function createMockConnection(): ConnectionInterface
    {
        /* trigger_warning('
            Utilisation d\'une connexion à la base de données invalide.
            Veuillez installer le package `blitz-php/database`.
        '); */

        return new class () implements ConnectionInterface {
            public function initialize()
            {
            }

            public function connect(bool $persistent = false)
            {
            }

            public function persistentConnect()
            {
            }

            public function reconnect()
            {
            }

            public function getConnection(?string $alias = null)
            {
            }

            public function setDatabase(string $databaseName)
            {
            }

            public function getDatabase(): string
            {
                return '';
            }

            public function error(): array
            {
                return [];
            }

            public function getPlatform(): string
            {
                return '';
            }

            public function getVersion(): string
            {
                return '';
            }

            public function query(string $sql, $binds = null)
            {
            }

            public function simpleQuery(string $sql)
            {
            }

            public function table($tableName)
            {
            }

            public function getLastQuery()
            {
            }

            public function escape($str)
            {
            }

            public function callFunction(string $functionName, ...$params)
            {
            }

            public function isWriteType($sql): bool
            {
                return false;
            }
        };
    }
}