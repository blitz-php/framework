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
 * Vide les fichiers de la toolbar
 */
class ClearDebugbar extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $group = 'Housekeeping';

    /**
     * {@inheritDoc}
     */
    protected $name = 'debugbar:clear';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Efface tous les fichiers JSON de la debugbar.';

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        helper('filesystem');

        if (! delete_files(FRAMEWORK_STORAGE_PATH . 'debugbar')) {
            // @codeCoverageIgnoreStart
            $this->error('Erreur lors de la suppression des fichiers de la debugbar.')->eol();

            return;
            // @codeCoverageIgnoreEnd
        }

        $this->success('Debugbar netoyée.')->eol();
    }
}
