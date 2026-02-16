<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Initializer;

use BadFunctionCallException;
use BlitzPHP\Cli\Console\Console;
use BlitzPHP\Container\Services;
use BlitzPHP\Debug\ExceptionManager;
use BlitzPHP\Event\EventDiscover;
use BlitzPHP\Loader\DotEnv;
use BlitzPHP\Router\Dispatcher;

/**
 * Bootstrap pour l'application
 *
 * Fournit les méthodes statiques pour démarrer l'application dans différents contextes.
 *
 * @method static int  console(array $paths, string $path_config_file)                                            Démarre l'application en mode console
 * @method static int  klinge(array $paths, string $path_config_file) Démarre l'application en mode klinge (CLI)
 * @method static void test(array $paths, string $path_config_file)                                               Démarre l'application en mode test
 * @method static int  web(array $paths, string $path_config_file)                                                Démarre l'application en mode web
 *
 * @codeCoverageIgnore
 */
class Boot
{
    /**
     * Tableau des chemins de l'application
     *
     * @var array{
     *     app: string,
     *     boot: string,
     *     storage: string,
     *     test: string,
     *     composer: string,
     *     env_directory: string
     * } $paths
     */
    private array $paths;

    /**
     * Constructeur
     *
     * @param array  $paths             Les chemins de l'application
     * @param string $paths_config_file Le chemin du fichier de configuration des chemins
     */
    public function __construct(array $paths, private string $paths_config_file)
    {
        // Fusionne les chemins fournis avec les valeurs par défaut
        $this->paths = array_merge([
            'app'           => '',
            'boot'          => '',
            'storage'       => '',
            'test'          => '',
            'composer'      => '',
            'env_directory' => '',
        ], $paths);

        // Définit les constantes globales si elles n'existent pas déjà
        defined('DS')                 || define('DS', DIRECTORY_SEPARATOR);
        defined('BLITZ_CORE_VERSION') || define('BLITZ_CORE_VERSION', '0.13');
    }

    /**
     * Méthode magique pour les appels statiques
     *
     * Permet d'appeler les méthodes de démarrage statiquement : Boot::web(), Boot::console(), etc.
     *
     * @param string $name      Le nom de la méthode à appeler
     * @param array  $arguments Les arguments à passer
     *
     * @return int Le code de sortie (pour les méthodes qui retournent un int)
     *
     * @throws BadFunctionCallException Si la méthode n'existe pas
     */
    public static function __callStatic(string $name, array $arguments = [])
    {
        if (! in_array($name, ['web', 'console', 'klinge', 'test'], true)) {
            throw new BadFunctionCallException("Méthode {$name} non trouvée");
        }

        // Crée une nouvelle instance avec les arguments fournis
        $boot   = new static(...$arguments);
        $method = 'boot' . ucfirst($name);

        return $boot->{$method}();
    }

    /**
     * Utilisé par `public/index.php`
     *
     * Contextes d'utilisation :
     *   - web : Invoqué par une requête HTTP
     *   - php-cli : Invoqué par CLI via `php public/index.php`
     *
     * @return int Code de sortie.
     */
    public function bootWeb(): int
    {
        $this->definePathConstants();
        $this->loadConstants();

        $this->loadDotEnv();
        $this->loadCommonFunctions();
        $this->loadEnvironmentBootstrap();
        $this->loadAutoloader();

        $this->initializeKint();
        $this->setupApplication();

        $app = $this->initializeDispatcher();
        $this->runDispatcher($app);

        // Termine l'application, en définissant le code de sortie pour les applications
        // basées sur CLI qui pourraient surveiller ce code.
        return EXIT_SUCCESS;
    }

    /**
     * Utilisé par les scripts en ligne de commande autres que :
     * - `klinge`
     * - `php-cli`
     * - `kahlan`
     *
     * @used-by `system/util_bootstrap.php`
     */
    public function bootConsole(): void
    {
        $this->definePathConstants();
        $this->loadConstants();

        $this->loadDotEnv();
        $this->loadCommonFunctions();
        $this->loadEnvironmentBootstrap();
        $this->loadAutoloader();

        $this->initializeKint();
        $this->setupApplication();

        // Charge les routes pour la console
        service('routes')->loadRoutes();
    }

    /**
     * Utilisé par `klinge` (l'outil CLI du framework)
     *
     * @return int Code de sortie.
     */
    public function bootKlinge(): int
    {
        $this->definePathConstants();
        $this->loadConstants();

        $this->loadDotEnv();
        $this->loadCommonFunctions();
        $this->loadEnvironmentBootstrap();
        $this->loadAutoloader();

        $this->initializeKint();
        $this->setupApplication();

        $this->initializeDispatcher();

        $console = new Console(service('container'));
        $exit = $console->run();

        return is_int($exit) ? $exit : EXIT_SUCCESS;
    }

    /**
     * Utilisé par `spec/bootstrap.php` pour les tests
     */
    public function bootTest(): void
    {
        $this->definePathConstants();
        $this->loadConstants();

        $this->loadDotEnv();
        $this->loadCommonFunctionsMock();
        $this->loadCommonFunctions();
        $this->loadEnvironmentBootstrap(false);
        $this->loadAutoloader();

        $this->initializeKint();
        $this->setupApplication();
    }

    /**
     * Utilisé par `preload.php` pour le préchargement OPcache
     */
    public function preload(): void
    {
        $this->definePathConstants();

        $this->loadConstants();
        $this->loadEnvironmentBootstrap(false);
        $this->loadAutoloader();
    }

    /**
     * Charge les variables d'environnement depuis les fichiers .env
     *
     * Les variables sont chargées dans $_SERVER et $_ENV
     */
    protected function loadDotEnv(): void
    {
        DotEnv::init($this->paths['env_directory'] ?? $this->paths['app'] . '/../');
    }

    /**
     * Définit la constante d'environnement ENVIRONMENT
     *
     * Cherche la valeur dans l'ordre :
     * 1. $_ENV['ENVIRONMENT']
     * 2. $_SERVER['ENVIRONMENT']
     * 3. getenv('ENVIRONMENT')
     * 4. 'production' (valeur par défaut)
     */
    protected function defineEnvironment(): void
    {
        if (! defined('ENVIRONMENT')) {
            $env = function_exists('environment') ? environment() : null;
            if ($env === null) {
                $env = $_ENV['ENVIRONMENT'] ?? $_SERVER['ENVIRONMENT']
                    ?? getenv('ENVIRONMENT')
                    ?: 'production';
            }

            define('ENVIRONMENT', $env);
        }
    }

    /**
     * Charge le fichier de bootstrap spécifique à l'environnement
     *
     * @param bool $exit Si true, arrête l'execution avec un message d'erreur si le fichier n'existe pas
     */
    protected function loadEnvironmentBootstrap(bool $exit = true): void
    {
        $this->defineEnvironment();

        $bootstrapFile = BOOT_PATH . ENVIRONMENT . '.php';

        if (is_file($bootstrapFile)) {
            require_once $bootstrapFile;

            return;
        }

        if ($exit) {
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            echo "L'environnement de l'application n'est pas configuré correctement.";

            exit(EXIT_ERROR);
        }
    }

    /**
     * Définit les constantes de chemins
     *
     * Les constantes de chemins fournissent un accès pratique aux dossiers
     * à travers toute l'application. Elles doivent être définies ici pour
     * être disponibles dans les fichiers de configuration qui seront chargés.
     */
    protected function definePathConstants(): void
    {
        // Constante du chemin de l'application
        if (! defined('APP_PATH')) {
            if (false === $appPath = realpath(rtrim($this->paths['app'], '\\/ '))) {
                header('HTTP/1.1 503 Service Unavailable.', true, 503);
                echo 'Le chemin du dossier de l\'application ne semble pas être correctement configuré. ';
                echo 'Veuillez ouvrir le fichier "' . $this->paths_config_file . '" et corriger la clé "app".';

                exit(3);
            }

            define('APP_PATH', $appPath . DIRECTORY_SEPARATOR);
        }

        // Constante du chemin de stockage
        if (! defined('STORAGE_PATH')) {
            // Essaye de trouver le chemin de stockage s'il est relatif
            if (! is_dir($this->paths['storage']) && is_dir($this->paths['app'] . $this->paths['storage'] . DS)) {
                $this->paths['storage'] = $this->paths['app'] . strtr(trim($this->paths['storage'], '/\\'), '/\\', DS . DS);
            }

            if (false === $storagePath = realpath(rtrim($this->paths['storage'], '\\/ '))) {
                header('HTTP/1.1 503 Service Unavailable.', true, 503);
                echo 'Le chemin du dossier de stockage ne semble pas être correctement configuré. ';
                echo 'Veuillez ouvrir le fichier "' . $this->paths_config_file . '" et corriger la clé "storage".';

                exit(3);
            }

            define('STORAGE_PATH', $storagePath . DIRECTORY_SEPARATOR);
        }

        // Constante du chemin racine du projet (juste au-dessus de APP_PATH)
        if (! defined('ROOTPATH')) {
            define('ROOTPATH', realpath(APP_PATH . '../') . DIRECTORY_SEPARATOR);
        }

        // Constante du chemin de demarrage
        if (! defined('BOOT_PATH')) {
            if (false === $bootPath = realpath(rtrim($this->paths['boot'], '\\/ '))) {
                $bootPath = ROOTPATH . 'boot';
            }

            define('BOOT_PATH', $bootPath . DIRECTORY_SEPARATOR);
        }

        // Constante du chemin des tests
        if (! defined('TEST_PATH')) {
            if (false === $testPath = realpath(rtrim($this->paths['test'], '\\/ '))) {
                $testPath = ROOTPATH . 'spec';
            }

            define('TEST_PATH', $testPath . DIRECTORY_SEPARATOR);
        }
    }

    /**
     * Charge les constantes de l'application et du système
     */
    protected function loadConstants(): void
    {
        if (file_exists(APP_PATH . 'Config' . DS . 'constants.php')) {
            require_once APP_PATH . 'Config' . DS . 'constants.php';
        }

        require_once SYST_PATH . 'Config' . DS . 'constants.php';
    }

    /**
     * Charge les fonctions communes de l'application et du système
     */
    protected function loadCommonFunctions(): void
    {
        if (is_file(APP_PATH . 'Helpers' . DS . 'common.php')) {
            require_once APP_PATH . 'Helpers' . DS . 'common.php';
        }

        require_once SYST_PATH . 'Helpers' . DS . 'common.php';
    }

    /**
     * Charge les fonctions communes mockées pour les tests
     */
    protected function loadCommonFunctionsMock(): void
    {
        require_once SYST_PATH . 'Spec/Mock/MockCommon.php';
    }

    /**
     * Charge l'autoloader
     *
     * L'autoloader permet à toutes les pièces du framework de travailler ensemble.
     * Il doit être chargé ici pour que les fichiers de configuration puissent utiliser
     * les constantes de chemins définies précédemment.
     */
    protected function loadAutoloader(): void
    {
        Services::autoloader()->initialize()->register();
        Services::autoloader()->loadHelpers();
    }

    /**
     * Configure l'application
     *
     * Initialise les services principaux de l'application
     */
    protected function setupApplication(): void
    {
        // Initialisation du conteneur d'injection de dépendances
        service('container')->initialize();

        // Gestionnaires d'exceptions
        service(ExceptionManager::class)->register();

        // Initialisation du gestionnaire d'événements
        Services::singleton(EventDiscover::class)->discover();
        service('event')->emit('app:init');

        $this->configureGlobaleValues();
    }

    /**
     * Configure les valeurs globales de l'application
     *
     * Définit les paramètres globaux comme la locale, le fuseau horaire et le charset
     */
    protected function configureGlobaleValues(): void
    {
        $config = (object) config('app');

        locale_set_default($config->locale ?? 'en');
        date_default_timezone_set($config->timezone ?? 'UTC');
        ini_set('default_charset', strtoupper($config->charset));
    }

    /**
     * Initialise Kint (outil de débogage)
     */
    protected function initializeKint(): void
    {
        require_once __DIR__ . DS . 'kint.php';
    }

    /**
     * Initialise le Dispatcher
     *
     * La classe Dispatcher contient la fonctionnalité centrale pour faire fonctionner
     * l'application et fait tout le travail nécessaire pour que toutes les pièces
     * fonctionnent ensemble.
     */
    protected function initializeDispatcher(): Dispatcher
    {
        return Services::singleton(Dispatcher::class);
    }

    /**
     * Exécute le Dispatcher
     *
     * Maintenant que tout est configuré, il est temps de démarrer réellement
     * les moteurs et de faire fonctionner cette application.
     */
    protected function runDispatcher(Dispatcher $app): void
    {
        $app->run();
    }
}
