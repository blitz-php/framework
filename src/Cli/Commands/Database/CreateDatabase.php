<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Database;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Config\Database;
use BlitzPHP\Database\Connection\SQLite;
use InvalidArgumentException;

class CreateDatabase extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'Database';

    /**
     * @var string Nom
     */
    protected $name = 'db:create';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Créez un nouveau schéma de base de données.';

    /**
     * {@inheritDoc}
     */
    protected $service = 'Service de gestion de base de données';

    /**
     * {@inheritDoc}
     */
    protected $arguments = [
        'name' => 'Le nom de la base de données à utiliser',
    ];

    /**
     * {@inheritDoc}
     */
    protected $options = [
        '--ext' => 'Extension de fichier du fichier de base de données pour SQLite3. Peut être `db` ou `sqlite`. La valeur par défaut est `db`.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $name = $this->argument('name');
        if (empty($name)) {
            $name = $this->prompt('Nom de la base de données', null, static function ($val) {
                if (empty($val)) {
                    throw new InvalidArgumentException('Veuillez entrer le nom de la base de données.');
                }

                return $val;
            });
        }

        [$group, $config] = Database::connectionInfo();

        $config['database'] = '';
        $config['debug']    = false;

        $db = Database::connect($config);

        // Specialement pour SQLite3
        if ($db instanceof SQLite) {
            $ext = $this->option('ext', 'db');

            if (! in_array($ext, ['db', 'sqlite'], true)) {
                $ext = $this->prompt('Please choose a valid file extension', ['db', 'sqlite']); // @codeCoverageIgnore
            }

            if ($name !== ':memory:') {
                $name = str_replace(['.db', '.sqlite'], '', $name) . ".{$ext}";
            }

            $config['driver']   = 'pdosqlite';
            $config['database'] = $name;

            if ($name !== ':memory:') {
                $dbName = strpos($name, DIRECTORY_SEPARATOR) === false ? STORAGE_PATH . 'app' . DS . $name : $name;

                if (is_file($dbName)) {
                    $this->error("La base de données \"{$dbName}\" existe déjà.");

                    return;
                }

                unset($dbName);
            }

            // Connection a un nouveau SQLite3 pour creer la bd
            $db = Database::connect($config, false);
            $db->connect();

            if (! is_file($db->getDatabase()) && $name !== ':memory:') {
                // @codeCoverageIgnoreStart
                $this->error('Echec de la création de la base de données');

                return;
                // @codeCoverageIgnoreEnd
            }
        } else if (! Database::creator($db)->createDatabase($name)) {
            // @codeCoverageIgnoreStart
            $this->error('Echec de la création de la base de données');

            return;
            // @codeCoverageIgnoreEnd
        }

        $this->success("Base de données \"{$name}\" créée avec succès.");
    }
}
