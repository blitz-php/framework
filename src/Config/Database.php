<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Config;

use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Database\Database as Db;
use BlitzPHP\Loader\Services;
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

            if ($group === 'auto') {
                $group = on_test() ? 'test' : (on_prod() ? 'production' : 'development');
            }

            if (! isset($config[$group])) {
                $group = 'default';
            }
        }

        if (is_string($group) && ! isset($config[$group]) && strpos($group, 'custom-') !== 0) {
            throw new InvalidArgumentException($group . ' is not a valid database connection group.');
        }

        if (strpos($group, 'custom-') !== false) {
            $config = [$group => $config];
        }

        return [$group, $config[$group]];
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
}
