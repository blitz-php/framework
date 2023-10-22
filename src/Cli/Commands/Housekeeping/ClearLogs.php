<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Housekeeping;

use BlitzPHP\Cli\Console\Command;

/**
 * Efface tous les logs
 */
class ClearLogs extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $group = 'Housekeeping';

    /**
     * {@inheritDoc}
     */
    protected $name = 'logs:clear';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Efface tous les fichiers de log.';

    /**
     * {@inheritDoc}
     */
    protected $options = [
        '--force' => 'Forcer la suppression de tous les fichiers de logs sans avoir à demander.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $force = array_key_exists('force', $params) || $this->option('force');

        if (! $force && ! $this->confirm('Êtes-vous sûr de vouloir supprimer les logs?')) {
            // @codeCoverageIgnoreStart
            $this->fail('Suppression des logs interrompue.');
            $this->fail('Si vous le souhaitez, utilisez l\'option "-force" pour forcer la suppression de tous les fichiers de log.');
            $this->newLine();

            return;
            // @codeCoverageIgnoreEnd
        }

        helper('filesystem');

        if (! delete_files(STORAGE_PATH . 'logs', false, true)) {
            // @codeCoverageIgnoreStart
            $this->error('Erreur lors de la suppression des fichiers de logs.')->eol();

            return;
            // @codeCoverageIgnoreEnd
        }

        $this->success('Logs netoyés.')->eol();
    }
}
