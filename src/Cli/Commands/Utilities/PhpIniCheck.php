<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Utilities;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Security\CheckPhpIni;

/**
 * Check php.ini values.
 */
final class PhpIniCheck extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    /**
     * @var string Nom
     */
    protected $name = 'phpini:check';

    /**
     * @var string Description
     */
    protected $description = 'Vérifiez les valeurs de votre php.ini dans l\'environnement de production.';

    protected $service = 'Service de configuration';

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
	{
        /** @var array $data */
		$data = CheckPhpIni::run();

		$this->table($data);

        return EXIT_SUCCESS;
    }
}
