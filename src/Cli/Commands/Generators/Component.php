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
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\String\Text;

/**
 * Génère un fichier squelette de composant.
 */
class Component extends Command
{
    use GeneratorTrait;

    /**
     * @var string Groupe
     */
    protected $group = 'Generateurs';

    /**
     * @var string Nom
     */
    protected $name = 'make:component';

    /**
     * @var string Description
     */
    protected $description = 'Génère un nouveau composant contrôlé et sa vue.';

    /**
     * @var string
     */
    protected $service = 'Service de génération de code';

    /**
     * @var array Arguments de la commande
     */
    protected $arguments = [
        'name' => 'Le nom de la classe du composant contrôlé.',
    ];

    /**
     * @var array Options de la commande
     */
    protected $options = [
        '--namespace' => ["Définissez l'espace de noms racine. Par défaut\u{a0}: \"APP_NAMESPACE\".", APP_NAMESPACE],
        '--force'     => 'Forcer l\'écrasement du fichier existant.',
    ];

    /**
     * Exécutez réellement une commande.
     */
    public function execute(array $params)
    {
        $this->component     = 'Component';
        $this->directory     = 'Components';
        $this->template      = 'component.tpl.php';
        $this->classNameLang = 'CLI.generator.className.component';
        $params              = array_merge($params, ['suffix' => true]);

        $this->task('Creation du composant')->eol();

        if (null === $className = $this->generateClass($params)) {
            return 0;
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

        return 0;
    }
}
