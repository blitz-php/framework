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
use Composer\InstalledVersions;
use Dimtrovich\Console\Application;
use Psr\Log\LoggerInterface;
use ReflectionClass;

use function Ahc\Cli\t;

/**
 * Classe abstraite pour le fonctionnement de la console
 */
class Console
{
    protected const APP_NAME        = 'BlitzPHP';
    protected const APP_VERSION     = BLITZ_CORE_VERSION;
    protected const CONSOLE_NAME    = 'klinge';
    protected const CONSOLE_VERSION = '1.0';

    private readonly Application $app;
    private bool $discovered = false;

    public function __construct(ContainerInterface $container)
    {
        $this->app = Application::create(static::APP_NAME, static::APP_VERSION)
            ->withContainer($container)
            ->withLogger($container->get(LoggerInterface::class), static::CONSOLE_NAME)
            ->withCommandInstances($this->discoverCommands($container))
            ->withLocale(config('app.locale'))
            ->withTheme(config('klinge.theme', 'monokai'))
            ->withStyles(config('klinge.styles', []))
            ->withHeadTitle($this->headtitle())
            ->withIcons(
                alert: config('klinge.icons.alert', false),
                badge: config('klinge.icons.badge', false),
                logger: config('klinge.icons.logger', true),
            )
            ->withHooks(
                before: $this->beforeHook(...),
            );
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
     * Appelle une commande déjà enregistrée
     * Utile pour exécuter une commande dans une autre commande ou dans un contrôleur
     */
    public function call(string $commandName, array $arguments = [], array $options = []): mixed
    {
        return $this->app->getConsole()->call($commandName, $arguments, $options);
    }

    /**
     * Appelle une commande déjà enregistrée sans afficher sa sortie
     * Utile pour exécuter une commande dans une autre commande ou dans un contrôleur
     */
    public function callSilent(string $commandName, array $arguments = [], array $options = []): mixed
    {
        return $this->app->getConsole()->callSilent($commandName, $arguments, $options);
    }

    /**
     * Appelle une commande déjà enregistrée sous forme de chaine
     * Entrée attendue dans une seule chaîne comme celle qui serait utilisée sur la ligne de commande elle-même :
     *
     *  Exp: callRaw('migrate:create SomeMigration');
     */
    public function callRaw(string $commandLine): mixed
    {
        return $this->app->getConsole()->callRaw($commandLine);
    }

    /**
     * Appelle une commande déjà enregistrée sous forme de chaine sans afficher sa sortie
     * Entrée attendue dans une seule chaîne comme celle qui serait utilisée sur la ligne de commande elle-même :
     *
     *  Exp: callRawSilent('migrate:create SomeMigration');
     */
    public function callRawSilent(string $commandLine): mixed
    {
        return $this->app->getConsole()->callRawSilent($commandLine);
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
            $locator->listFiles('Cli/Commands/'), // Commandes internes du framework
        );

        return array_unique($files);
    }

    /**
     * Découvre automatiquement les commandes dans les dossiers standards.
     *
     * Parcourt les dossiers Commands/ et Cli/Commands/ pour trouver
     * toutes les classes qui étendent Command.
     *
     * @return list<Command> Liste des instances de commandes découvertes
     */
    private function discoverCommands(ContainerInterface $container): array
    {
        if ($this->discovered) {
            return [];
        }

        /** @var LocatorInterface */
        $locator = $container->get(LocatorInterface::class);

        $commands = [];

        foreach ($this->files($locator) as $file) {
            if (false === $className = $locator->findQualifiedNameFromPath($file)) {
                continue;
            }

            $class = new ReflectionClass($className);

            if ($class->isInstantiable() && $class->isSubclassOf(Command::class)) {
                $commands[] = $container->make($className);
            }
        }

        $this->discovered = true;

        return $commands;
    }

    /**
     * Hook exécuté avant chaque commande.
     *
     * Vérifie les dépendances requises par la commande et propose
     * de les installer automatiquement si elles sont manquantes.
     *
     * @param bool    $suppress Si vrai, supprime les informations du header
     * @param Command $command  Instance de la commande en cours d'exécution
     *
     * @return void
     *
     * @internal
     */
    public function beforeHook(bool $suppress, Command $command)
    {
        if (! $suppress) {
            $command->eol()->io()->help_header($this->headtitle())->eol(2);
        }

        foreach ($command->required() as $package) {
            $package = explode(':', $package);
            $version = $package[1] ?? null;
            $package = $package[0];

            if (! InstalledVersions::isInstalled($package)) {
                $command->badge()->info(t('Cette commande nécessite le package "%s" mais vous ne l\'avez pas', [$package]));
                if (! $command->confirm(t('Voulez-vous l\'installer maintenant ?'))) {
                    return;
                }

                $package .= ($version !== null ? ":{$version}" : '');
                $command->task(t('Installation de "%s" en cours', [$package]))->eol();

                chdir(ROOTPATH);
                passthru('composer require ' . $package, $status);

                $command->eol();
            }
        }
    }

    private function headtitle(): string
    {
        return static::APP_NAME . ' Command Line Interface - v' . self::APP_VERSION . ' | Server time: ' . date('Y-m-d H:i:s');
    }
}
