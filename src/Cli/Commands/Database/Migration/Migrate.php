<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Database\Migration;

use BlitzPHP\Cli\Commands\Database\Helper;
use BlitzPHP\Cli\Console\Command;

/**
 * Execute toutes les nouvelles migrations.
 */
class Migrate extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'Database';

    /**
     * @var string Nom
     */
    protected $name = 'migrate';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Recherche et exécute toutes les nouvelles migrations dans la base de données.';

    /**
     * {@inheritDoc}
     */
    protected $service = 'Service de gestion de base de données';

    /**
     * {@inheritDoc}
     */
    protected $options = [
        '-n, --namespace' => 'Défini le namespace de la migration',
        '-g, --group'     => 'Défini le groupe de la base de données',
        '--all'           => 'Défini pour tous les namespaces, ignore l\'option (-n)',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->colorize(lang('Migrations.latest'), 'yellow');

        $namespace = $this->option('namespace');
        $group     = $this->option('group');

        $runner = Helper::runner($group);

        $runner->clearMessages();
        $runner->setFiles(Helper::getMigrationFiles($this->option('all') === true, $namespace));

        if (! $runner->latest($group)) {
            $this->fail(lang('Migrations.generalFault')); // @codeCoverageIgnore
        }

        $messages = $runner->getMessages();

        foreach ($messages as $message) {
            $this->colorize($message['message'], $message['color']);
        }

        $this->newLine()->success(lang('Migrations.migrated'));
    }
}
