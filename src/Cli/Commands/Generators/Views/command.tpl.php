<@php

namespace {namespace};

use BlitzPHP\Cli\Console\Command;
<?php if ($type === 'generator'): ?>
use BlitzPHP\Cli\Console\GeneratorTrait;
<?php endif ?>

class {class} extends Command
{
<?php if ($type === 'generator'): ?>
    use GeneratorTrait;

<?php endif ?>
    /** @var string Groupe auquel appartient la commande */
    protected $group = '{group}';

    /** @var string Nom de la commande */
    protected $name = '{command}';

    /** @var string Description de la commande */
    protected $description = '';

    /** @var string Utilisation de la commande */
    protected $usage = '{command} [arguments] [options]';

    /** @var array Arguments de la commande */
    protected $arguments = [];

    /** @var array Options de la commande */
    protected $options = [];

    /**
     * Execution de la commande
     *
     * @param array $params
     */
    public function execute(array $params)
    {
<?php if ($type === 'generator'): ?>
        $this->component = 'Command';
        $this->directory = 'Commands';
        $this->template  = 'command.tpl.php';

        $this->run($params);
<?php else: ?>
        //
<?php endif ?>
    }
}
