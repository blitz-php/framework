<@php

namespace {namespace};

use BlitzPHP\Cli\Console\Command;
<?php if ($type === 'generator'): ?>
use BlitzPHP\Cli\Traits\GeneratorTrait;
<?php endif ?>

class {class} extends Command
{
<?php if ($type === 'generator'): ?>
    use GeneratorTrait;

<?php endif ?>
    /** @var string Groupe auquel appartient la commande */
    protected string $group = '{group}';

    /** @var string Nom de la commande */
    protected string $name = '{command}';

    /** @var string Description de la commande */
    protected string $description = '';

    /** @var string Utilisation de la commande */
    protected string $usage = '{command} [arguments] [options]';

    /** @var array Arguments de la commande */
    protected array $arguments = [];

    /** @var array Options de la commande */
    protected array $options = [];

    /**
     * Execution de la commande
     */
    public function handle()
    {
<?php if ($type === 'generator'): ?>
        $this->component = 'Command';
        $this->directory = 'Commands';
        $this->template  = 'command.tpl.php';

        $this->generateClass($this->parameters());
<?php else: ?>
        //
<?php endif ?>
    }
}
