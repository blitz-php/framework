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
 * Génère une nouvelle classe d\'email.
 */
class Mail extends GeneratorCommand
{
    protected string $name        = 'make:mail';
    protected string $description = 'Génère une nouvelle classe d\'email.';
    protected array $arguments    = [
        'name' => ['Le nom de la classe de mail.'],
    ];
    protected string $component     = 'Mail';
    protected string $directory     = 'Mail';
    protected string $template      = 'mail.tpl.php';
    protected string $classNameLang = 'CLI.generator.className.mail';
}
