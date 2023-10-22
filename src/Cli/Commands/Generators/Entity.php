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
 * Génère un fichier squelette d'entité.
 */
class Entity extends Command
{
    use GeneratorTrait;

    /**
     * @var string Groupe
     */
    protected $group = 'Generateurs';

    /**
     * @var string Nom
     */
    protected $name = 'make:entity';

    /**
     * @var string Description
     */
    protected $description = 'Génère un nouveau fichier d\'entité.';

    /**
     * @var string
     */
    protected $service = 'Service de génération de code';

    /**
     * @var array Arguments
     */
    protected $arguments = [
        'name' => 'Le nom de la classe d\'entité.',
    ];

    /**
     * @var array Options
     */
    protected $options = [
        '--namespace' => ["Définit l'espace de noms racine. Par défaut\u{a0}: \"APP_NAMESPACE\".", APP_NAMESPACE],
        '--suffix'    => 'Ajouter le titre du composant au nom de la classe (par exemple, User => UserEntity).',
        '--force'     => 'Forcer à écraser le fichier existant.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->component = 'Entity';
        $this->directory = 'Entities';
        $this->template  = 'entity.tpl.php';

        $this->classNameLang = 'CLI.generator.className.entity';
        $this->runGeneration($params);
    }
}
