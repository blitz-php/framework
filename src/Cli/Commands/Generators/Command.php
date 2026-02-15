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

use BlitzPHP\Cli\Console\Command as ConsoleCommand;
use BlitzPHP\Cli\Traits\GeneratorTrait;

/**
 * Generates a skeleton command file.
 */
class Command extends ConsoleCommand
{
    use GeneratorTrait;

    protected string $group = 'Generateurs';

    protected string $name = 'make:command';

    protected string $description = 'Génère une nouvelle commande klinge.';

    protected string $service = 'Service de génération de code';

    protected array $arguments = [
        'name' => 'Le nom de la classe de commande.',
    ];

    protected array $options = [
        '--command'   => 'Le nom de la commande. Par défaut: "command:name"',
        '--type'      => ['Le type de commande. Options [basic, generator]. Par défault: "basic".', 'basic'],
        '--group'     => 'Le groupe de la commande. Par défaut: [basic -> "{APP_NAME}", generator -> "{APP_NAME}:Generateurs"].',
        '--namespace' => ['Définissez l\'espace de noms racine. Par défaut: "APP_NAMESPACE".', APP_NAMESPACE],
        '--suffix'    => 'Ajouter le titre du composant au nom de la classe (par exemple, User => UserCommand).',
        '--force'     => "Forcer l'écrasement du fichier existant.",
    ];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $this->component = 'Command';
        $this->directory = 'Commands';
        $this->template  = 'command.tpl.php';

        $this->classNameLang = 'CLI.generator.className.command';
        $this->generateClass($this->parameters());
    }

    /**
     * Préparez les options et effectuez les remplacements nécessaires.
     */
    protected function prepare(string $class): string
    {
        $command = $this->option('command');
        $group   = $this->option('group');
        $type    = $this->option('type');

        $command = is_string($command) ? $command : 'command:name';
        $type    = is_string($type) ? $type : 'basic';

        if (! in_array($type, ['basic', 'generator'], true)) {
            // @codeCoverageIgnoreStart
            $type = $this->choice(lang('CLI.generator.commandType'), ['basic', 'generator'], 'basic');
            $this->eol();
            // @codeCoverageIgnoreEnd
        }

        if (! is_string($group)) {
            $group = $type === 'generator' ? config('app.name', 'App') . ':Generateurs' : config('app.name', 'App');
        }

        return $this->parseTemplate(
            $class,
            ['{group}', '{command}'],
            [$group, $command],
            ['type' => $type]
        );
    }
}
