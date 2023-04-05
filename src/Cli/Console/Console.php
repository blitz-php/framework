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

use Ahc\Cli\Application;
use Ahc\Cli\Input\Command as AhcCommand;
use BlitzPHP\Debug\Logger;
use BlitzPHP\Exceptions\CLIException;
use BlitzPHP\Loader\Services;
use BlitzPHP\Traits\SingletonTrait;
use Composer\InstalledVersions;
use ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * Classe abstraite pour le fonctionnement de la console
 */
final class Console extends Application
{
    use SingletonTrait;

    /**
     * Defini si on doit suppriemer les information du header (nom/version du framework) ou pas
     */
    private bool $suppress = false;

    private Logger $logger;

    /**
     * Differents logos
     *
     * @var array
     */
    private $logos = [
        '
        /$$$$$$$  /$$ /$$   /$$              /$$$$$$$  /$$   /$$ /$$$$$$$
        | $$__  $$| $$|__/  | $$             | $$__  $$| $$  | $$| $$__  $$
        | $$  \ $$| $$ /$$ /$$$$$$  /$$$$$$$$| $$  \ $$| $$  | $$| $$  \ $$
        | $$$$$$$ | $$| $$|_  $$_/ |____ /$$/| $$$$$$$/| $$$$$$$$| $$$$$$$/
        | $$__  $$| $$| $$  | $$      /$$$$/ | $$____/ | $$__  $$| $$____/
        | $$  \ $$| $$| $$  | $$ /$$ /$$__/  | $$      | $$  | $$| $$
        | $$$$$$$/| $$| $$  |  $$$$//$$$$$$$$| $$      | $$  | $$| $$
        |_______/ |__/|__/   \___/ |________/|__/      |__/  |__/|__/
        ',
        '
        ____  _ _ _       _____  _    _ _____
        |  _ \\| (_) |     |  __ \\| |  | |  __ \\
        | |_) | |_| |_ ___| |__) | |__| | |__) |
        |  _ <| | | __|_  /  ___/|  __  |  ___/
        | |_) | | | |_ / /| |    | |  | | |
        |____/|_|_|\\__/___|_|    |_|  |_|_|
        ',
        '
        ______ _ _ _      ______ _   _ ______
        | ___ \\ (_) |     | ___ \\ | | || ___ \\
        | |_/ / |_| |_ ___| |_/ / |_| || |_/ /
        | ___ \\ | | __|_  /  __/|  _  ||  __/
        | |_/ / | | |_ / /| |   | | | || |
        \\____/|_|_|\\__/___\\_|   \\_| |_/\\_|
        ',
        '
        ____  __    __  ____  ____  ____  _  _  ____
        (  _ \\(  )  (  )(_  _)(__  )(  _ \\/ )( \\(  _ \\
         ) _ (/ (_/\\ )(   )(   / _/  ) __/) __ ( ) __/
        (____/\\____/(__) (__) (____)(__)  \\_)(_/(__)
        ',
        "
        .----. .-.   .-. .---.  .---. .----. .-. .-..----.
        | {}  }| |   | |{_   _}{_   / | {}  }| {_} || {}  }
        | {}  }| `--.| |  | |   /    }| .--' | { } || .--'
        `----' `----'`-'  `-'   `---' `-'    `-' `-'`-'
        ",
        '
        _______   ___        __  ___________  ________     _______    __    __    _______
        |   _  "\ |"  |      |" \("     _   ")("      "\   |   __ "\  /" |  | "\  |   __ "\
        (. |_)  :)||  |      ||  |)__/  \\__/  \___/   :)  (. |__) :)(:  (__)  :) (. |__) :)
        |:     \/ |:  |      |:  |   \\_ /       /  ___/   |:  ____/  \/      \/  |:  ____/
        (|  _  \\  \  |___   |.  |   |.  |      //  \__    (|  /      //  __  \\  (|  /
        |: |_)  :)( \_|:  \  /\  |\  \:  |     (:   / "\  /|__/ \    (:  (  )  :)/|__/ \
        (_______/  \_______)(__\_|_)  \__|      \_______)(_______)    \__|  |__/(_______)

        ',
        "
        ,-----.  ,--.,--.  ,--.         ,------. ,--.  ,--.,------.
        |  |) /_ |  |`--',-'  '-.,-----.|  .--. '|  '--'  ||  .--. '
        |  .-.  \\|  |,--.'-.  .-'`-.  / |  '--' ||  .--.  ||  '--' |
        |  '--' /|  ||  |  |  |   /  `-.|  | --' |  |  |  ||  | --'
        `------' `--'`--'  `--'  `-----'`--'     `--'  `--'`--'
        ",
        '
        ____  _ _ _       ____  _   _ ____
        | __ )| (_) |_ ___|  _ \\| | | |  _ \\
        |  _ \\| | | __|_  / |_) | |_| | |_) |
        | |_) | | | |_ / /|  __/|  _  |  __/
        |____/|_|_|\\__/___|_|   |_| |_|_|
        ',
        "
        ______   __    _   _          _______  ____  ____  _______
        |_   _ \\ [  |  (_) / |_       |_   __ \\|_   ||   _||_   __ \\
          | |_) | | |  __ `| |-'____    | |__) | | |__| |    | |__) |
          |  __'. | | [  | | | [_   ]   |  ___/  |  __  |    |  ___/
         _| |__) || |  | | | |, .' /_  _| |_    _| |  | |_  _| |_
        |_______/[___][___]\\__/[_____]|_____|  |____||____||_____|

        ",
        '
        ██████╗ ██╗     ██╗████████╗███████╗██████╗ ██╗  ██╗██████╗
        ██╔══██╗██║     ██║╚══██╔══╝╚══███╔╝██╔══██╗██║  ██║██╔══██╗
        ██████╔╝██║     ██║   ██║     ███╔╝ ██████╔╝███████║██████╔╝
        ██╔══██╗██║     ██║   ██║    ███╔╝  ██╔═══╝ ██╔══██║██╔═══╝
        ██████╔╝███████╗██║   ██║   ███████╗██║     ██║  ██║██║
        ╚═════╝ ╚══════╝╚═╝   ╚═╝   ╚══════╝╚═╝     ╚═╝  ╚═╝╚═╝
        ',
    ];

    /**
     * Liste des commandes
     *
     * @var array<string, callable>
     */
    private array $_commands = [];

    public function __construct(bool $suppress = false)
    {
        parent::__construct('BlitzPHP', \BlitzPHP\Core\Application::VERSION);

        $this->logo($this->logos[array_rand($this->logos)]);

        $this->suppress = $suppress;

        $this->discoverCommands();

        $this->registerException($this->logger = Services::logger());
    }

    /**
     * Appelle une commande deja enregistree
     * Utile pour executer une commande dans une autre commande ou dans un controleur
     */
    public static function call(string $commandName, array $arguments = [], array $options = [])
    {
        $action = self::instance()->_commands[$commandName] ?? null;

        if ($action === null) {
            throw CLIException::commandNotFound($commandName);
        }

        return $action($arguments, $options, true);
    }

    /**
     * Recherche toutes les commandes dans le framework et dans le code de l'utilisateur
     * et collecte leurs instances pour fonctionner avec eux.
     */
    private function discoverCommands()
    {
        if (count($this->commands) > 1) {
            return;
        }

        $locator = Services::locator();
        $files   = array_merge(
            $locator->listFiles('Commands/'), // Commandes de l'application ou des fournisseurs
            $locator->listFiles('Cli/Commands/') // Commandes internes du framework
        );

        if ($files === []) {
            return; // @codeCoverageIgnore
        }

        foreach ($files as $file) {
            $className = $locator->getClassname($file);

            if ($className === '' || ! class_exists($className)) {
                continue;
            }

            try {
                $this->addCommand($className, $this->logger);
            } catch (CLIException $e) {
                continue;
            } catch (ReflectionException $e) {
                $this->logger->error($e->getMessage());

                continue;
            }
        }
    }

    /**
     * Ajoute une commande à la console
     *
     * @param string $commandName FQCN de la commande
     */
    private function addCommand(string $className, ?Logger $logger = null)
    {
        $class  = new ReflectionClass($className);
        $logger = $logger ?: Services::logger();

        if (! $class->isInstantiable() || ! $class->isSubclassOf(Command::class)) {
            throw CLIException::invalidCommand($className);
        }

        /**
         * @var Command $instance
         */
        $instance = new $className($this, $logger);

        $command = new AhcCommand(
            $instance->name,
            $instance->description,
            false,
            $this
        );

        // Defini le groupe auquel appartient la commande
        $command->inGroup($instance->group);

        // Defini l'usage et la version de la commande
        $command->usage($instance->usage)->version($instance->version);

        // Enregistre les options de la commande
        foreach ($instance->options as $option => $value) {
            $value = (array) $value;

            $description = $value[0];
            if (! is_string($description)) {
                continue;
            }

            $default = $value[1] ?? null;
            $filter  = $value[2] ?? null;
            if ($filter !== null && ! is_callable($filter)) {
                $filter = null;
            }

            $command->option($option, $description, $filter, $default);
        }

        // Enregistre les arguments de la commande
        foreach ($instance->arguments as $argument => $value) {
            $value = (array) $value;

            $description = $value[0];
            if (! is_string($description)) {
                continue;
            }

            $default = $value[1] ?? null;

            $command->argument($argument, $description, $default);
        }

        $console = $this;
        $action  = function (?array $arguments = [], ?array $options = [], ?bool $suppress = null) use ($instance, $command, $console) {
            foreach ($instance->required as $package) {
                $package = explode(':', $package);
                $version = $package[1] ?? null;
                $package = $package[0];

                if (! InstalledVersions::isInstalled($package)) {
                    $console->io()->info('Cette commande nécessite le package "' . $package . '" mais vous ne l\'avez pas', true);
                    if (! $console->io()->confirm('Voulez-vous l\'installer maintenant ?')) {
                        return;
                    }

                    $package .= ($version !== null ? ":{$version}" : '');
                    $console->io()->write('>> Installation de "' . $package . '" en cours', true);
                    $console->io()->eol();

                    chdir(ROOTPATH);
                    passthru('composer require ' . $package, $status);

                    $console->io()->eol();
                }
            }

            $suppress = $suppress ?: $console->suppress;
            if (! $suppress) {
                $console->start($instance->service);
            }

            $parameters = $command->values(false);
            if (empty($arguments)) {
                $arguments = $command->args();
            }
            if (empty($options)) {
                $options = array_diff_key($parameters, $arguments);
            }
            $parameters = array_merge($options, $arguments);

            $result = $instance->setOptions($options)->setArguments($arguments)->execute($parameters);

            if (! $suppress) {
                $console->end();
            }

            return $result;
        };

        $command->action($action);
        $this->_commands[$instance->name] = $action;

        $this->add($command, $instance->alias, false);
    }

    private function registerException(Logger $logger)
    {
        $this->onException(function (Throwable $e, int $exitCode) use ($logger) {
            $logger->error((string) $e, ['exitCode' => $exitCode, 'klinge' => true]);
        
            throw new CLIException($e->getMessage(), $exitCode, $e);
        });
    }

    /**
     * Message d'entete commun a tous les services de la console
     *
     * @return void
     */
    private function start(string $service)
    {
        $io = $this->io();

        $eq_str = str_repeat('=', strlen($service));

        $io->write('==================================' . $eq_str, true);
        $io->write('BlitzPHP Command Line Interface | ' . $service, true);
        $io->write('==================================' . $eq_str, true);
        $io->eol();
    }

    /**
     * Message de pied commun a tous les services de la console
     *
     * @return void
     */
    private function end()
    {
        $io = $this->io();

        $info = 'BlitzPHP v' . $this->version() . ' * klinge v1.0 * ' . date('Y-m-d H:i:s');

        $io->write("\n\n" . str_repeat('-', strlen($info)) . "\n");
        $io->writer()->bold->info($info, true);
    }
}
