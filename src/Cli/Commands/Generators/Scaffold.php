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
 * Génère un ensemble complet de fichiers d'échafaudage.
 */
class Scaffold extends Command
{
    use GeneratorTrait;

    /**
     * {@inheritDoc}
     */
    protected $group = 'Generateurs';

    /**
     * {@inheritDoc}
     */
    protected $name = 'make:scaffold';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Génère un ensemble complet de fichiers d\'échafaudage.';

    /**
     * @var string
     */
    protected $service = 'Service de génération de code';

    /**
     * {@inheritDoc}
     */
    protected $arguments = [
        'name' => 'Le nom de la classe.',
    ];

    /**
     * {@inheritDoc}
     */
    protected $options = [
        '--bare'      => 'Ajoute l\'option "--bare" au composant du contrôleur.',
        '--restful'   => 'Ajoute l\'option "--restful" au composant du contrôleur.',
        '--table'     => 'Ajoute l\'option "--table" au composant du modèle.',
        '--dbgroup'   => 'Ajoute l\'option "--dbgroup" au composant du modèle.',
        '--return'    => 'Ajoute l\'option "--return" au composant du modèle.',
        '--namespace' => ["Définissez l'espace de noms racine. Par défaut\u{a0}: \"APP_NAMESPACE\".", APP_NAMESPACE],
        '--suffix'    => ['Ajoutez le titre du composant au nom de la classe (par exemple, User => UserController).', true],
        '--force'     => 'Forcer l\'écrasement du fichier existant.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $options = [];

        if (null !== $namespace = $this->option('namespace')) {
            $options['namespace'] = $namespace;
        }

        if ($this->option('suffix')) {
            $options['suffix'] = true;
        }

        if ($this->option('force')) {
            $options['force'] = true;
        }

        $controllerOpts = [];

        if ($this->option('bare')) {
            $controllerOpts['bare'] = true;
        } elseif (null !== $restful = $this->option('restful')) {
            $controllerOpts['restful'] = $restful;
        }

        $modelOpts = [
            'table'   => $this->option('table'),
            'dbgroup' => $this->option('dbgroup'),
            'return'  => $this->option('return'),
        ];

        $name = ['name' => $this->argument('name')];

        $this->call('make:controller', $name, array_merge($controllerOpts, $options));

        if ($this->commandExists('make:model')) {
            $this->call('make:model', $name, array_merge($modelOpts, $options));
        }
        if ($this->commandExists('make:migration')) {
            $this->call('make:migration', $name, array_merge($options));
        }
        if ($this->commandExists('make:seeder')) {
            $this->call('make:seeder', $name, array_merge($options));
        }
    }
}
