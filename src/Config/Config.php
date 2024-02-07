<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Config;

use BlitzPHP\Autoloader\Autoloader;
use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Container\Services;
use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\Iterable\Arr;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use ReflectionClass;
use ReflectionMethod;

class Config
{
    /**
     * Fichier de configuration déjà chargé
     */
    private static array $loaded = [];

    /**
     * Configurations originales issues des fichiers de configuration
     *
     * Permet de réinitialiser les configuration par défaut au cas où on aurrait fait des modifications à la volée
     */
    private static array $originals = [];

    /**
     * Different registrars decouverts.
     *
     * Les registrars sont des mecanismes permettant aux packages externe de definir un elements de configuration
     */
    private static array $registrars = [];

    /**
     * Drapeau permettant de savoir si la config a deja ete initialiser
     */
    private static bool $initialized = false;

    private Configurator $configurator;

    public function __construct()
    {
        $this->configurator = new Configurator();
        $this->initialize();
    }

    /**
     * Détermine si une clé de configuration existe.
     */
    public function exists(string $key): bool
    {
        if (! $this->configurator->exists($key)) {
            $config = explode('.', $key);
            $this->load($config[0]);

            return $this->configurator->exists(implode('.', $config));
        }

        return true;
    }

    /**
     * Détermine s'il y'a une clé de configuration.
     */
    public function has(string $key): bool
    {
        return $this->exists($key);
    }

    /**
     * Détermine s'il manque une clé de configuration.
     */
    public function missing(string $key): bool
    {
        return ! $this->exists($key);
    }

    /**
     * Renvoyer une configuration de l'application
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null)
    {
        if ($this->exists($key)) {
            return $this->configurator->get($key);
        }

        if (func_num_args() > 1) {
            return $default;
        }

        $path = explode('.', $key);

        throw ConfigException::notFound(implode(' » ', $path));
    }

    /**
     * Définir une configuration de l'application
     *
     * @param mixed $value
     */
    public function set(string $key, $value)
    {
        $path = explode('.', $key);
        $this->load($path[0]);

        $this->configurator->set($key, $value);
    }

    /**
     * Reinitialise une configuration en fonction des donnees initiales issues des fichiers de configurations
     */
    public function reset(null|array|string $keys = null): void
    {
        if (null !== $keys) {
            $keys = (array) $keys;
        } else {
            $keys = array_keys(self::$originals);
        }

        foreach ($keys as $key) {
            $this->set($key, Arr::dataGet(self::$originals, $key));

            if (str_starts_with($key, 'app')) {
                $this->initializeAutoDetect();
            }
        }
    }

    /**
     * Rend disponible un groupe de configuration qui n'existe pas (pas de fichier de configuration)
     * Ceci est notament utilse pour definir des configurations à la volée
     */
    public function ghost(array|string $key, ?Schema $schema = null): static
    {
        $this->load($key, null, $schema, true);

        return $this;
    }

    /**
     * Charger la configuration spécifique dans le scoope
     *
     * @param string|string[] $config
     */
    public function load($config, ?string $file = null, ?Schema $schema = null, bool $allow_empty = false)
    {
        if (is_array($config)) {
            foreach ($config as $key => $value) {
                if (is_string($key)) {
                    $file = $value;
                    $conf = $key;
                } else {
                    $file = null;
                    $conf = $value;
                }
                $this->load($conf, $file, null, $allow_empty);
            }
        } elseif (! isset(self::$loaded[$config])) {
            $file ??= self::path($config);
            $schema ??= self::schema($config);

            $configurations = [];
            if (file_exists($file) && ! in_array($file, get_included_files(), true)) {
                $configurations = (array) require $file;
            }

            $configurations = Arr::merge(self::$registrars[$config] ?? [], $configurations);

            if (empty($configurations) && ! $allow_empty && (empty($schema) || ! is_a($schema, Schema::class))) {
                return;
            }

            $this->configurator->addSchema($config, $schema ?: Expect::mixed(), false);
            $this->configurator->merge([$config => $configurations]);

            self::$loaded[$config]    = $file;
            self::$originals[$config] = $this->configurator->get($config);
        }
    }

    /**
     * Affiche l'exception dû à la mauvaise definition d'une configuration
     *
     * @param string $group (app, data, database, etc.)
     */
    public static function exceptBadConfigValue(string $config_key, array|string $accepts_values, string $group)
    {
        if (is_array($accepts_values)) {
            $accepts_values = '(Accept values: ' . implode('/', $accepts_values) . ')';
        }

        throw new ConfigException("The '{$group}.{$config_key} configuration is not set correctly. {$accepts_values} \n Please edit '{" . self::path($group) . "}' file to correct it");
    }

    /**
     * Renvoie le chemin du fichier d'un groupe de configuration donné
     */
    public static function path(string $path): string
    {
        $path = preg_replace('#\.php$#', '', $path);

        if (file_exists($file = CONFIG_PATH . $path . '.php')) {
            return $file;
        }

        $paths = Services::locator()->search('Config/' . $path);

        if (isset($paths[0]) && file_exists($path[0])) {
            return $paths[0];
        }

        return '';
    }

    /**
     * Retrouve le schema de configuration d'un groupe
     */
    public static function schema(string $key): ?Schema
    {
        $file        = 'schemas' . DS . Helpers::ensureExt($key . '.config', 'php');
        $syst_schema = SYST_PATH . 'Constants' . DS . $file;
        $app_schema  = CONFIG_PATH . $file;

        if (file_exists($syst_schema)) {
            $schema = require $syst_schema;
        } elseif (file_exists($app_schema)) {
            $schema = require $app_schema;
        } else {
			$paths = Services::locator()->search('Config/schemas/' . $key);

			if (isset($paths[0]) && file_exists($path[0])) {
				$schema = require $paths[0];
			}
		}

        return $schema ?? null;
    }

    /**
     * Initialiser la configuration du système avec les données des fichier de configuration
     */
    private function initialize()
    {
        if (self::$initialized) {
            return;
        }

        $this->loadRegistrar();
        $this->load(['app']);

        ini_set('log_errors', 1);
        ini_set('error_log', LOG_PATH . 'blitz-logs');

        $this->initializeAutoDetect();

        self::$initialized = true;
    }

    /**
     * Charges les registrars disponible pour l'application.
     * Les registrars sont des mecanismes permettant aux packages externe de definir un elements de configuration
     */
    private function loadRegistrar()
    {
        $autoloader = new Autoloader(['psr4' => [APP_NAMESPACE => APP_PATH]]);
        $locator    = new Locator($autoloader->initialize());

        $registrarsFiles = $locator->search('Config/Registrar.php');

        foreach ($registrarsFiles as $file) {
            if (false === $classname = $locator->findQualifiedNameFromPath($file)) {
                continue;
            }

            $class   = new ReflectionClass($classname);
            $methods = $class->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if (! ($method->isPublic() && $method->isStatic())) {
                    continue;
                }

                if (! is_array($result = $method->invoke(null))) {
                    continue;
                }

                $name                    = $method->getName();
                self::$registrars[$name] = Arr::merge(self::$registrars[$name] ?? [], $result);
            }
        }
    }

    /**
     * Initialise l'URL
     */
    private function initializeURL()
    {
        $config = $this->get('app.base_url', 'auto');

        if ($config === 'auto' || empty($config)) {
            $config = rtrim(str_replace('\\', '/', Helpers::findBaseUrl()), '/');
        }

        $this->set('app.base_url', $config);
    }

    /**
     * Initialise l'environnement d'execution de l'application
     */
    private function initializeEnvironment()
    {
        $environment = $config = $this->get('app.environment');

        $config = match ($config) {
            'auto'  => is_online() ? 'production' : 'development',
            'dev'   => 'development',
            'prod'  => 'production',
            'test'  => 'testing',
            default => $config,
        };

        if ($config !== $environment) {
            $this->set('app.environment', $config);
        }

        switch ($config) {
            case 'development':
                error_reporting(-1);
                ini_set('display_errors', 1);
                break;

            case 'testing':
            case 'production':
                ini_set('display_errors', 0);
                error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
                break;

            default:
                self::exceptBadConfigValue('environment', ['development', 'production', 'testing', 'auto'], 'app');
        }

        defined('BLITZ_DEBUG') || define('BLITZ_DEBUG', $config !== 'production');
    }

    /**
     * Initialise les paramètres de la bar de debug
     */
    private function initializeDebugbar()
    {
        $config = $this->get('app.show_debugbar', 'auto');

        if (! in_array($config, ['auto', true, false], true)) {
            self::exceptBadConfigValue('show_debugbar', ['auto', true, false], 'app');
        }

        if ($config === 'auto') {
            $this->set('app.show_debugbar', ! is_online());
        }
    }

    /**
     * Initialise les donnees qui ont une valeur definie a "auto" et donc dependent de certains facteurs
     */
    private function initializeAutoDetect(): void
    {
        $this->initializeURL();
        $this->initializeEnvironment();
        $this->initializeDebugbar();
    }
}
