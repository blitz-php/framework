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
 * Génère une nouvelle classe de validation.
 */
class Validation extends Command
{
    use GeneratorTrait;

    /**
     * {@inheritDoc}
     */
    protected $group = 'Generateurs';

    /**
     * {@inheritDoc}
     */
    protected $name = 'make:validation';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Génère une nouvelle classe de validation.';

    /**
     * @var string
     */
    protected $service = 'Service de génération de code';

    /**
     * {@inheritDoc}
     */
    protected $arguments = [
        'name' => 'Le nom de la classe de validation.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->component = 'Validation';
        $this->directory = 'Validations';
        $this->template  = 'validation.tpl.php';

        $this->classNameLang = 'CLI.generator.className.validation';
        $this->runGeneration($params);
    }
}
