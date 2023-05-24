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

/**
 * Génère un fichier squelette de seeder.
 */
class Seeder extends Command
{
    use GeneratorTrait;

    /**
     * {@inheritDoc}
     */
    protected $group = 'Generators';

    /**
     * {@inheritDoc}
     */
    protected $name = 'make:seeder';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Génère un nouveau fichier seeder.';

    /**
     * @var string
     */
    protected $service = 'Service de génération de code';

    /**
     * {@inheritDoc}
     */
    protected $arguments = [
        'name' => 'Le nom de la classe du seeder.',
    ];

    /**
     * {@inheritDoc}
     */
    protected $options = [
        '--namespace' => ["Définissez l'espace de noms racine. Par défaut\u{a0}: \"APP_NAMESPACE\".", APP_NAMESPACE],
        '--suffix'    => 'Ajoutez le titre du composant au nom de la classe (par exemple, User => UserSeeder).',
        '--force'     => 'Forcer l\'écrasement du fichier existant.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->component = 'Seeder';
        $this->directory = 'Database\Seeds';
        $this->template  = 'seeder.tpl.php';

        $this->classNameLang = 'CLI.generator.className.seeder';
        $this->runGeneration($params);
    }
}
