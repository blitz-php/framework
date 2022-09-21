<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Database;

use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Traits\SingletonTrait;
use InvalidArgumentException;

/**
 * Database Connection Factory
 *
 * Creates and returns an instance of the appropriate DatabaseConnection
 */
class Database
{
    use SingletonTrait;

    /**
     * Maintains an array of the instances of all connections that have
     * been created.
     *
     * Helps to keep track of all open connections for performance
     * monitoring, logging, etc.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * Parses the connection binds and returns an instance of the driver
     * ready to go.
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function load(array $params = [], string $alias = '')
    {
        if ($alias === '') {
            throw new InvalidArgumentException('You must supply the parameter: alias.');
        }

        if (! empty($params['dsn']) && strpos($params['dsn'], '://') !== false) {
            $params = $this->parseDSN($params);
        }

        if (empty($params['driver'])) {
            throw new InvalidArgumentException('You have not selected a database type to connect to.');
        }

        $this->connections[$alias] = $this->initDriver($params['driver'], 'Connection', $params);

        return $this->connections[$alias];
    }

    /**
     * Creates a Forge instance for the current database type.
     */
    public function loadForge(ConnectionInterface $db): object
    {
        if (! $db->conn) {
            $db->initialize();
        }

        return $this->initDriver($db->driver, 'Forge', $db);
    }

    /**
     * Creates a Utils instance for the current database type.
     */
    public function loadUtils(ConnectionInterface $db): object
    {
        if (! $db->conn) {
            $db->initialize();
        }

        return $this->initDriver($db->driver, 'Utils', $db);
    }

    /**
     * Parse universal DSN string
     *
     * @throws InvalidArgumentException
     */
    protected function parseDSN(array $params): array
    {
        $dsn = parse_url($params['dsn']);

        if (! $dsn) {
            throw new InvalidArgumentException('Your DSN connection string is invalid.');
        }

        $dsnParams = [
            'dsn'      => '',
            'driver'   => $dsn['scheme'],
            'hostname' => isset($dsn['host']) ? rawurldecode($dsn['host']) : '',
            'port'     => isset($dsn['port']) ? rawurldecode((string) $dsn['port']) : '',
            'username' => isset($dsn['user']) ? rawurldecode($dsn['user']) : '',
            'password' => isset($dsn['pass']) ? rawurldecode($dsn['pass']) : '',
            'database' => isset($dsn['path']) ? rawurldecode(substr($dsn['path'], 1)) : '',
        ];

        if (! empty($dsn['query'])) {
            parse_str($dsn['query'], $extra);

            foreach ($extra as $key => $val) {
                if (is_string($val) && in_array(strtolower($val), ['true', 'false', 'null'], true)) {
                    $val = $val === 'null' ? null : filter_var($val, FILTER_VALIDATE_BOOLEAN);
                }

                $dsnParams[$key] = $val;
            }
        }

        return array_merge($params, $dsnParams);
    }

    /**
     * Initialize database driver.
     *
     * @param array|object $argument
     */
    protected function initDriver(string $driver, string $class, $argument): object
    {
        $class = $driver . '\\' . $class;

        if (strpos($driver, '\\') === false) {
            $class = "\\BlitzPHP\\Database\\{$class}";
        }

        return new $class($argument);
    }
}
