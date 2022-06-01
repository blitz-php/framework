<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Core;

use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Traits\SingletonTrait;
use InvalidArgumentException;
use League\Config\Configuration;
use Nette\Schema\Expect;

class Config
{
    use SingletonTrait;

    /**
     * @var Configuration
     */
    private $configurator;

    /**
     * Fichier de configuration déjà chargé
     *
     * @var array
     */
    private static $loaded = [];


    protected function __construct() 
    {
        $this->configurator = new Configuration([
            'app' => Expect::structure([
                'base_url' => Expect::string()->default('auto'),
                'charset' => Expect::string()->default('UTF-8'),
                'environment' => Expect::string()->default('auto'),
                'language' => Expect::string()->default('en')
            ])->otherItems()
        ]);
    } 

    /**
     * Renvoyer une configuration de l'application
     *
     * @return mixed
     */
    public static function get(string $key)
    {
        $configurator = self::instance()->configurator;

        if ($configurator->exists($key)) {
            return $configurator->get($key);
        }

        $config = explode('.', $key);
        self::load($config[0]);

        return self::instance()->configurator->get(implode('.', $config));
    }

    /**
     * Définir une configuration de l'application
     *
     * @param mixed $value
     */
    public static function set(string $key, $value)
    {
        self::instance()->configurator->set($key, $value);
    }

    /**
     * Config constructor.
     */
    public static function init()
    {
        self::load(['autoload', 'data', 'app']);

        self::instance()->initialize();
    }

    /**
     * Charger la configuration spécifique dans le scoope
     *
     * @param string|string[] $config
     */
    public static function load($config, ?string $file = null)
    {
        if (is_array($config)) {
            foreach ($config as $key => $value) {
                if (! is_string($value) || empty($value)) {
                    continue;
                }
                if (is_string($key)) {
                    $file = $value;
                    $conf = $key;
                } else {
                    $file = null;
                    $conf = $value;
                }
                self::load($conf, $file);
            }
        } elseif (is_string($config) && !isset(self::$loaded[$config])) {
            if (empty($file)) {
                $file = self::path($config);
            }
            if (! file_exists($file)) {
                throw ConfigException::configDontExist($config, $file);
            }
            if (! in_array($file, get_included_files(), true)) {
                self::instance()->configurator->addSchema($config, Expect::mixed(), false);
                self::instance()->configurator->merge([$config => require($file)]);
                self::$loaded[$config] = $file;
            }
        }
    }

    /**
     * Affiche l'exception dû à la mauvaise definition d'une configuration
     *
     * @param array|string $accepts_values
     * @param string       $group          (app, data, database, etc.)
     */
    public static function exceptBadConfigValue(string $config_key, $accepts_values, string $group)
    {
        if (is_array($accepts_values)) {
            $accepts_values = '(Accept values: ' . implode('/', $accepts_values) . ')';
        } elseif (! is_string($accepts_values)) {
            throw new InvalidArgumentException('Mauvaise utilisation de la methode ' . __METHOD__);
        }

        throw new ConfigException('
			The <b>' . $group . '.' . $config_key . '</b> configuration is not set correctly. ' . $accepts_values . '.
			<br>
			Please edit &laquo; ' . self::path($group) . ' &raquo; file to correct it
		');
    }

    /**
     * Recherche l'URL de base de l'application independamment de la configuration de l'utilisateur
     */
    public static function findBaseUrl(): string
    {
        if (isset($_SERVER['SERVER_ADDR'])) {
            $server_addr = $_SERVER['HTTP_HOST'] ?? ((strpos($_SERVER['SERVER_ADDR'], ':') !== false) ? '[' . $_SERVER['SERVER_ADDR'] . ']' : $_SERVER['SERVER_ADDR']);

            if (isset($_SERVER['SERVER_PORT'])) {
                $server_addr .= ':' . ((! preg_match('#:' . $_SERVER['SERVER_PORT'] . '$#', $server_addr)) ? $_SERVER['SERVER_PORT'] : '80');
            }

            if (
                (! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
                || (! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
            ) {
                $base_url = 'https';
            } else {
                $base_url = 'http';
            }

            $base_url .= '://' . $server_addr . dirname(substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_FILENAME']))));
        } else {
            $base_url = 'http://localhost:' . ($_SERVER['SERVER_PORT'] ?? '80');
        }

        return $base_url;
    }

    /**
     * Renvoie le chemin du fichier d'un groupe de configuration donné
     */
    public static function path(string $path): string
    {
        $_config_file = [
            'app'      => CONFIG_PATH . 'app.php',
            'autoload' => CONFIG_PATH . 'autoload.php',
            'data'     => CONFIG_PATH . 'data.php',
            'database' => CONFIG_PATH . 'database.php',
            'layout'   => CONFIG_PATH . 'layout.php',

            'email' => CONFIG_PATH . 'email.php',
            'rest'  => CONFIG_PATH . 'rest.php',
        ];
        $path = preg_replace('#\.php$#', '', $path);

        if (isset($_config_file[$path])) {
            return $_config_file[$path];
        }

        return CONFIG_PATH . $path . '.php';
    }


    /**
     * Initialiser la configuration du système avec les données des fichier de configuration
     */
    private function initialize()
    {
        ini_set('log_errors', 1);
        ini_set('error_log', LOG_PATH . 'blitz-logs');

        $this->initializeURL();
        $this->initializeEnvironment();
        $this->initializeView();
        $this->initializeDebugbar();
    }

    /**
     * Initialise l'URL
     */
    private function initializeURL() 
    {
        if (!$this->configurator->exists('app.base_url')) {
            $config = 'auto';
        }
        else {
            $config = $this->configurator->get('app.base_url');
        }

        if ($config === 'auto' || empty($config)) {
            $config = rtrim(str_replace('\\', '/', self::findBaseUrl()), '/');
        }

        $this->configurator->set('app.base_url', $config);
    }

    /**
     * Initialise l'environnement d'execution de l'application
     */
    private function initializeEnvironment() 
    {
        $environment = $config = $this->configurator->get('app.environment');

        if ($config === 'auto') {
            $config = is_online() ? 'production' : 'development';
        }
        else if ($config === 'dev') {
            $config = 'development';
        }
        else if ($config === 'prod') {
            $config = 'production';
        }

        if ($config != $environment) {
            $this->configurator->set('app.environment', $config);
        }
        
        switch ($config) {
            case 'development':
                error_reporting(-1);
                ini_set('display_errors', 1);
                break;

            case 'test':
            case 'production':
                ini_set('display_errors', 0);
                error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
                break;

            default:
                self::exceptBadConfigValue('environment', ['development', 'production', 'test', 'auto'], 'app');
        }
    }

    /**
     * Initialise les paramètres de compression de la vue
     */
    private function initializeView() 
    {
        if (!$this->configurator->exists('app.compress_output')) {
            $config = 'auto';
        }
        else {
            $config = $this->configurator->get('app.compress_output');
        }

        if (! in_array($config, ['auto', true, false], true)) {
            self::exceptBadConfigValue('compress_output', ['auto', true, false], 'app');
        }
        if ($config === 'auto') {
            $this->configurator->set('app.compress_output', is_online());
        }
    }

    /**
     * Initialise les paramètres de la bar de debug
     */
    private function initializeDebugbar()
    {
        if (!$this->configurator->exists('app.show_debugbar')) {
            $config = 'auto';
        }
        else {
            $config = $this->configurator->get('app.show_debugbar');
        }

        if (! in_array($config, ['auto', true, false], true)) {
            self::exceptBadConfigValue('show_debugbar', ['auto', true, false], 'app');
        }
        if ($config === 'auto') {
            $this->configurator->set('app.show_debugbar', ! is_online());
        }
    }
}
