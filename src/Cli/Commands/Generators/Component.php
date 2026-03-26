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

use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\String\Text;

/**
 * Génère un fichier squelette de composant.
 */
class Component extends GeneratorCommand
{
    protected string $name        = 'make:component';
    protected string $description = 'Génère un nouveau composant contrôlé et sa vue.';
    protected array $arguments    = [
        'name' => ['Le nom de la classe du composant contrôlé.'],
    ];
    protected array $options = [
        '--namespace' => ["Définissez l'espace de noms racine. Par défaut\u{a0}: \"APP_NAMESPACE\".", APP_NAMESPACE],
        '--force'     => ['Forcer l\'écrasement du fichier existant.'],
    ];

	protected string $component     = 'Component';
	protected string $directory     = 'Components';
	protected string $template      = 'component.tpl.php';
	protected string $classNameLang = 'CLI.generator.className.component';


    /**
     * {@inheritDoc}
     */
    protected function process(array $params)
    {
        $params['suffix'] = true;

        if (null === $className = $this->generateClass($params)) {
            return EXIT_SUCCESS;
        }

        $this->template = 'component_view.tpl.php';

        $viewName = Text::convertTo(Helpers::classBasename($className), 'kebab');
        $viewName = preg_replace(
            '/([a-z][a-z0-9_\/\\\\]+)(-component)$/i',
            '$1',
            $viewName
        ) ?? $viewName;
        $namespace = substr($className, 0, strrpos($className, '\\') + 1);

        $this->generateView($namespace . $viewName . '-component', $params);

        return EXIT_SUCCESS;
    }
}
