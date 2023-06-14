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

    /**
     * {@inheritDoc}
     */
    protected $group = 'Generators';

    /**
     * {@inheritDoc}
     */
    protected $name = 'make:mail';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Génère une nouvelle classe d\'email.';

    /**
     * @var string
     */
    protected $service = 'Service de génération de code';

    /**
     * {@inheritDoc}
     */
    protected $arguments = [
        'name' => 'Le nom de la classe de mail.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->component = 'Mail';
        $this->directory = 'Mail';
        $this->template  = 'mail.tpl.php';

        $this->classNameLang = 'CLI.generator.className.mail';
        $this->runGeneration($params);
    }
}
