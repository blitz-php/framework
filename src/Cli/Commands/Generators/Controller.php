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

use BlitzPHP\Controllers\BaseController;
use BlitzPHP\Controllers\ResourceController;
use BlitzPHP\Controllers\ResourcePresenter;

/**
 * Génère un fichier squelette de contrôleur.
 */
class Controller extends GeneratorCommand
{
    protected string $name        = 'make:controller';
    protected string $description = 'Génère un nouveau fichier de contrôleur.';
    protected array $arguments    = [
        'name' => ['Le nom de la classe du contrôleur.'],
    ];
    protected array $options = [
        '--bare'      => ['S\'étend de BlitzPHP\Controllers\BaseController au lieu de AppController.'],
        '--restful'   => ["S'étend à partir d'une ressource RESTful, Options\u{a0}: [controller, presenter]. Par défaut\u{a0}: \"controller\"."],
        '--namespace' => ["Définissez l'espace de noms racine. Par défaut\u{a0}: \"APP_NAMESPACE\".", APP_NAMESPACE],
        '--force'     => ['Forcer l\'écrasement du fichier existant.'],
        '--invokable' => ['Spécifie si on veut avoir un contrôleur à action unique.'],
    ];

	protected string $component     = 'Controller';
	protected string $directory     = 'Controllers';
	protected string $template      = 'controller.tpl.php';
	protected string $classNameLang = 'CLI.generator.className.controller';

    /**
     * {@inheritDoc}
     */
    protected function process(array $parameters)
	{
		$this->task('Creation du controleur')->eol();

		return parent::process($parameters + ['suffix' => true]);
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
