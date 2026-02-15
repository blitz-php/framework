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
 * Génère un fichier squelette de middleware.
 */
class Middleware extends Command
{
    use GeneratorTrait;

    protected string $group = 'Generateurs';

    protected string $name = 'make:middleware';

    protected string $description = 'Génère un nouveau fichier de middleware.';

    protected string $service = 'Service de génération de code';

    protected array $arguments = [
        'name' => 'Le nom de la classe de middleware.',
    ];

    protected array $options = [
        '--namespace' => ["Définit l'espace de noms racine. Par défaut\u{a0}: \"APP_NAMESPACE\".", APP_NAMESPACE],
        '--suffix'    => 'Ajouter le titre du composant au nom de la classe (par exemple, User => UserMiddleware).',
        '--force'     => 'Forcer à écraser le fichier existant.',
        '--standard'  => 'Le standard utilisé pour le middleware. Par défaut: "psr15"',
    ];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $this->component = 'Middleware';
        $this->directory = 'Middlewares';
        $this->template  = 'middleware.tpl.php';

        $this->classNameLang = 'CLI.generator.className.middleware';
        $this->generateClass($this->parameters());
    }

    /**
     * Préparez les options et effectuez les remplacements nécessaires.
     */
    protected function prepare(string $class): string
    {
        $standard = $this->option('standard', 'psr15');

        if (! in_array($standard, ['psr15', 'psr7'], true)) {
            // @codeCoverageIgnoreStart
            $standard = $this->choice(lang('CLI.generator.middlewareStandard'), ['psr15', 'psr7'], 'psr15');
            $this->eol();
            // @codeCoverageIgnoreEnd
        }

        return $this->parseTemplate(
            $class,
            [],
            [],
            ['standard' => $standard]
        );
    }
}
