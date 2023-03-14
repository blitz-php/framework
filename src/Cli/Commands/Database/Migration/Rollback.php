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
 * Exécute toutes les migrations dans l'ordre inverse, jusqu'à ce qu'elles aient toutes été désappliquées.
 */
class Rollback extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'Database';

    /**
     * @var string Nom
     */
    protected $name = 'migrate:rollback';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Recherche et annule toutes les migrations précédement exécutees.';

    /**
     * {@inheritDoc}
     */
    protected $service = 'Service de gestion de base de données';
    
    /**
     * {@inheritDoc}
     */
    protected $options = [
        '-b, --batch' => 'Spécifiez un lot à restaurer ; par exemple. "3" pour revenir au lot #3 ou "-2" pour revenir en arrière deux fois',
        '-g, --group' => 'Défini le groupe de la base de données',
        '-f, --force' => 'Forcer la commande - cette option vous permet de contourner la question de confirmation lors de l\'exécution de cette commande dans un environnement de production',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        if (on_prod()) {
            // @codeCoverageIgnoreStart
            $force = $this->option('force');

            if (! $force && ! $this->confirm(lang('Migrations.rollBackConfirm'))) {
                return;
            }
            // @codeCoverageIgnoreEnd
        }
        
        $group  = $this->option('group');

        $runner = Helper::runner($group);

        $batch = $this->option('batch') ?? ($runner->getLastBatch() - 1);
        
        $this->colorize(lang('Migrations.rollingBack') . ' ' . $batch, 'yellow');

        $runner->setFiles(Helper::getMigrationFiles(true));

        if (! $runner->regress($batch, $group)) {
            $this->error(lang('Migrations.generalFault')); // @codeCoverageIgnore
        }

        $messages = $runner->getMessages();

        foreach ($messages as $message) {
            $this->colorize($message['message'], $message['color']);
        }

        $this->newLine()->success('Done rolling back migrations.');
    }
}
