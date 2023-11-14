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
use BlitzPHP\Controllers\BaseController;
use BlitzPHP\Controllers\ResourceController;
use BlitzPHP\Controllers\ResourcePresenter;

/**
 * Génère un fichier squelette de contrôleur.
 */
class Controller extends Command
{
    use GeneratorTrait;

    /**
     * @var string Groupe
     */
    protected $group = 'Generateurs';

    /**
     * @var string Nom
     */
    protected $name = 'make:controller';

    /**
     * @var string Description
     */
    protected $description = 'Génère un nouveau fichier de contrôleur.';

    /**
     * @var string
     */
    protected $service = 'Service de génération de code';

    /**
     * @var array Arguments de la commande
     */
    protected $arguments = [
        'name' => 'Le nom de la classe du contrôleur.',
    ];

    /**
     * @var array Options de la commande
     */
    protected $options = [
        '--bare'      => 'S\'étend de BlitzPHP\Controllers\BaseController au lieu de AppController.',
        '--restful'   => "S'étend à partir d'une ressource RESTful, Options\u{a0}: [controller, presenter]. Par défaut\u{a0}: \"controller\".",
        '--namespace' => ["Définissez l'espace de noms racine. Par défaut\u{a0}: \"APP_NAMESPACE\".", APP_NAMESPACE],
        '--suffix'    => ['Ajoutez le titre du composant au nom de la classe (par exemple, User => UserController).', true],
        '--force'     => 'Forcer l\'écrasement du fichier existant.',
        '--invokable' => 'Spécifie si on veut avoir un contrôleur à action unique.',
    ];

    /**
     * Exécutez réellement une commande.
     */
    public function execute(array $params)
    {
        $this->component = 'Controller';
        $this->directory = 'Controllers';
        $this->template  = 'controller.tpl.php';

        $this->classNameLang = 'CLI.generator.className.controller';

        $this->task('Creation du controleur')->eol();

        $this->runGeneration($params);
    }

    /**
     * Préparez les options et effectuez les remplacements nécessaires.
     */
    protected function prepare(string $class): string
    {
        $bare = $this->option('bare');
        $rest = $this->option('restful');

        $useStatement = trim(APP_NAMESPACE, '\\') . '\Controllers\AppController';
        $extends      = 'AppController';

        // Obtient la classe parent appropriée à étendre.
        if ($bare || $rest) {
            if ($bare) {
                $useStatement = BaseController::class;
                $extends      = 'BaseController';
            } elseif ($rest) {
                $rest = is_string($rest) ? $rest : 'controller';

                if (! in_array($rest, ['controller', 'presenter'], true)) {
                    // @codeCoverageIgnoreStart
                    $rest = $this->choice(lang('CLI.generator.parentClass'), ['controller', 'presenter']);
                    $this->newLine();
                    // @codeCoverageIgnoreEnd
                }

                if ($rest === 'controller') {
                    $useStatement = ResourceController::class;
                    $extends      = 'ResourceController';
                } elseif ($rest === 'presenter') {
                    $useStatement = ResourcePresenter::class;
                    $extends      = 'ResourcePresenter';
                }
            }
        }

        return $this->parseTemplate(
            $class,
            ['{useStatement}', '{extends}'],
            [$useStatement, $extends],
            [
				'type'      => $rest,
				'invokable' => $this->option('invokable'),
			]
        );
    }
}
