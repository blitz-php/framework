<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Console;

use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\Container\ContainerInterface;
use Dimtrovich\Console\Application;
use Psr\Log\LoggerInterface;

/**
 * Classe abstraite pour le fonctionnement de la console
 */
class Console
{
    protected const APP_NAME        = 'BlitzPHP';
    protected const APP_VERSION     = BLITZ_CORE_VERSION;
    protected const CONSOLE_NAME    = 'klinge';
    protected const CONSOLE_VERSION = '1.0';

    private Application $app;

    private bool $discovered = false;

    public function __construct(ContainerInterface $container)
    {
        $this->app = Application::create(static::APP_NAME, static::APP_VERSION)
            ->withContainer($container)
            ->withLogger($container->get(LoggerInterface::class), static::CONSOLE_NAME)
            ->withCommands($this->discoverCommands($container->get(LocatorInterface::class)))
            ->withIcons(logger: true)
            ->withLocale(config('app.locale'))
            ->withTheme(config('klinge.theme', 'monokai'))
            ->withStyles(config('klinge.styles', []))
            ->withHeadTitle(static::APP_NAME . ' Command Line Interface - v' . self::APP_VERSION . ' | Server time: ' . date('Y-m-d H:i:s'));
    }

    public function run(): mixed 
    {
        $argv = $_SERVER['argv'];
        
        // Affiche les informations de base avant de faire quoi que ce soit d'autre
        // Vérifie si l'option --no-header est présente
        if (is_int($suppress = array_search('--no-header', $argv, true))) {
            unset($argv[$suppress]);
            
            $this->app->withoutHeadTitle();
        }

        return $this->app->run($argv);
    }

    /**
     * Definie les fichiers qui pourront etre considerer comme commandes
     *
     * @return list<string> Chemins absolus des fichiers
     */
    protected function files(LocatorInterface $locator): array
    {
        $files = array_merge(
            $locator->listFiles('Commands/'), // Commandes de l'application ou des fournisseurs
            $locator->listFiles('Cli/Commands/') // Commandes internes du framework
        );

        return array_unique($files);
    }

    private function discoverCommands(LocatorInterface $locator): array 
    {
        if ($this->discovered) {
            return [];
        }

        $classes = [];

        foreach ($this->files($locator) as $file) {
             $className = $locator->findQualifiedNameFromPath($file);

            if ($className && class_exists($className) && is_subclass_of($className, Command::class, true)) {
                $classes[] = $className;
            }
        }

        $this->discovered = true;

        return $classes;
    }
}
