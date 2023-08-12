<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Generators;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Cli\Traits\GeneratorTrait;
use InvalidArgumentException;

/**
 * Genere un skelette de fichier de migration.
 */
class Migration extends Command
{
    use GeneratorTrait;

    /**
     * @var string Groupe
     */
    protected $group = 'Generateurs';

    /**
     * @var string Nom
     */
    protected $name = 'make:migration';

    /**
     * La description de l'usage de la commande
     *
     * @var string
     */
    protected $usage = 'make:migration <name> [options]';

    /**
     * @var string Description
     */
    protected $description = 'Génère un nouveau fichier de migration.';

    /**
     * @var string
     */
    protected $service = 'Service de génération de code';

    /**
     * @var array Arguments
     */
    protected $arguments = [
        'name' => 'Le nom de la classe de migration.',
    ];

    /**
     * @var array Options
     */
    protected $options = [
        '--table'     => 'Nom de la table à utiliser.',
        '--create'    => 'Spécifie qu\'on veut créer une nouvelle table.',
        '--modify'    => 'Spécifie qu\'on veut modifier une table existante.',
        '--session'   => 'Génère un fichier de migration pour les sessions de la base de données',
        '--group'     => ['Groupe de base de données utilisé pour les sessions de la base de données. Par défaut: "default".', 'default'],
        '--namespace' => ['Définissez l\'espace de noms racine. Par défaut: "APP_NAMESPACE".', APP_NAMESPACE],
        '--suffix'    => 'Ajouter le titre du composant au nom de la classe (par exemple, User => UserMigration).',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->component = 'Migration';
        $this->directory = 'Database\Migrations';
        $this->template  = 'migration.tpl.php';

        $this->classNameLang = 'CLI.generator.className.migration';
        $this->runGeneration($params);
    }

    /**
     * Préparez les options et effectuez les remplacements nécessaires.
     */
    protected function prepare(string $class): string
    {
        $data            = [];
        $data['session'] = false;
        $data['matchIP'] = true; // @todo a recuperer via les fichiers de configurations

        $create = $this->option('create', false);
        $modify = $this->option('modify', false);

        if ($create && $modify) {
            throw new InvalidArgumentException('Impossible d\'utiliser "create" et "modify" au même moment pour la génération des migrations.');
        }

        if (! $create && ! $modify) {
            $data['action'] = null;
        } else {
            $data['action'] = $create ? 'create' : 'modify';
        }

        $table = $this->option('table');
        $group = $this->option('group');

        $data['group']  = is_string($group) ? $group : 'default';
        $data['driver'] = config('database.' . $data['group'] . '.driver');

        if (true === $this->option('session')) {
            $data['session'] = true;
            if ($data['action'] === null) {
                $data['action'] = 'create';
            }
        }

        if (! is_string($table) || $table === '') {
            if ($data['session']) {
                $table = 'blitz_sessions';
            } elseif (is_string($create)) {
                $table = $create;
            } elseif (is_string($modify)) {
                $table = $modify;
            } else {
                $table = null;
            }
        }

        $data['table'] = $table;

        return $this->parseTemplate($class, [], [], $data);
    }

    /**
     * Modifiez le nom de base du fichier avant de l'enregistrer.
     */
    protected function basename(string $filename): string
    {
        return gmdate(config('migrations.timestampFormat')) . basename($filename);
    }
}
