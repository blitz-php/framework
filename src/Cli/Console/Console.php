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
            ->withCommands($this->discoverCommands($container->get(LocatorInterface::class)))
            ->withLocale(config('app.locale'))
            ->withTheme(config('klinge.theme', 'monokai'))
            ->withStyles(config('klinge.styles', []))
            ->withHeadTitle(static::APP_NAME . ' Command Line Interface - v' . self::APP_VERSION . ' | Server time: ' . date('Y-m-d H:i:s'))
            ->withIcons(
				alert: config('klinge.icons.alert', false),
				badge: config('klinge.icons.badge', false),
				logger: config('klinge.icons.logger', true)
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

	/**
	 * Découvre automatiquement les commandes dans les dossiers standards.
	 *
	 * Parcourt les dossiers Commands/ et Cli/Commands/ pour trouver
	 * toutes les classes qui étendent Command.
	 *
	 * @param LocatorInterface $locator Service de localisation de fichiers
	 *
	 * @return array<class-string<Command>> Liste des classes de commandes découvertes
	 */
    private function discoverCommands(LocatorInterface $locator): array
    {
        if ($this->discovered) {
            return [];
        }

        $classes = [];

        foreach ($this->files($locator) as $file) {
             $className = $locator->findQualifiedNameFromPath($file);

            if ($className && is_subclass_of($className, Command::class, true)) {
                $classes[] = $className;
            }
        }

        $this->discovered = true;

        return $classes;
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
}
