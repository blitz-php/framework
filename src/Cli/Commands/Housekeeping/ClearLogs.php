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
    protected string $group       = 'Housekeeping';
    protected string $name        = 'logs:clear';
    protected string $description = 'Efface tous les fichiers de log.';
    protected array $options      = [
        '--force' => ['Forcer la suppression de tous les fichiers de logs sans avoir à demander.'],
    ];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $force = $this->hasOption('force');

        if (! $force && ! $this->confirm('Êtes-vous sûr de vouloir supprimer les logs?')) {
            $this->fail('Suppression des logs interrompue.');
            $this->fail('Si vous le souhaitez, utilisez l\'option "-force" pour forcer la suppression de tous les fichiers de log.');
            $this->newLine();

            return EXIT_ERROR;
        }

        helper('filesystem');

        if (! delete_files(STORAGE_PATH . 'logs', htdocs: true)) {
            $this->error('Erreur lors de la suppression des fichiers de logs.')->eol();

            return EXIT_ERROR;
        }

        $this->success(sprintf(
            'Fichiers de logs dans %s netoyés.',
            clean_path(STORAGE_PATH . 'logs')
        ));

        return EXIT_SUCCESS;
    }
}
