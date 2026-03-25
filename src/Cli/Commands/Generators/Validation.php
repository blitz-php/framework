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

/**
 * Génère une nouvelle classe de validation.
 */
class Validation extends GeneratorCommand
{
    protected string $name        = 'make:validation';
    protected string $description = 'Génère une nouvelle classe de validation.';
    protected array $arguments    = [
        'name' => ['Le nom de la classe de validation.'],
    ];

	protected string $component     = 'Validation';
	protected string $directory     = 'Validations';
	protected string $template      = 'validation.tpl.php';
	protected string $classNameLang = 'CLI.generator.className.validation';
}
