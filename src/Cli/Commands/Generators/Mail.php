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
 * Génère une nouvelle classe d\'email.
 */
class Mail extends Command
{
    use GeneratorTrait;

    protected string $group = 'Generateurs';

    protected string $name = 'make:mail';

    protected string $description = 'Génère une nouvelle classe d\'email.';

    protected string $service = 'Service de génération de code';

    protected array $arguments = [
        'name' => 'Le nom de la classe de mail.',
    ];

    public function handle()
    {
        $this->component = 'Mail';
        $this->directory = 'Mail';
        $this->template  = 'mail.tpl.php';

        $this->classNameLang = 'CLI.generator.className.mail';
        $this->generateClass($this->parameters());
    }
}
