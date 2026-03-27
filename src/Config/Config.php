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
use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\Iterable\Arr;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use ReflectionClass;
use ReflectionMethod;

/**
 * Gestionnaire central de configuration pour le framework.
 *
 * Charge, valide et accède aux configurations via fichiers ou schémas.
 */
class Config
{
    /**
     * Fichiers de configuration déjà chargés (cache).
     *
     * @var array<string, mixed>
     */
    private static array $loaded = [];

    /**
     * Configurations originales issues des fichiers de configuration.
     *
     * Permet de réinitialiser les configuration par défaut au cas où on aurrait fait des modifications à la volée.
     *
     * @var array<string, mixed>
     */
    private static array $originals = [];

    /**
     * Different registrars decouverts.
     *
     * Les registrars sont des mecanismes permettant aux packages externe de definir un elements de configuration.
     *
     * @var array<string, list<mixed>>
     */
    private static array $registrars = [];

    /**
     * Chemins d'accès vers les différents registrars découverts
     * 
     * @var array<string, array<string, array<mixed>>>
     */
    protected static array $registrarPaths = [];

    /**
     *  La découverte des modules est-elle terminée ?
     */
    protected static bool $didDiscovery = false;

    /**
     *  Le module discovery fonctionne-t-il ou non ?
     */
    protected static bool $discovering = false;

    /**
     * Drapeau permettant de savoir si la config a deja ete initialiser
     */
    private static bool $initialized = false;

    /**
     * Instance du configurateur pour validation.
     */
    private readonly Configurator $configurator;

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
            $this->ensureConfigLoaded($key);

            return $this->configurator->exists($key);
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
     * Obtient une valeur de configuration.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->exists($key)) {
            return $this->configurator->get($key);
        }

        if (func_num_args() > 1) {
            return $default;
        }

        throw ConfigException::notFound(str_replace(['.', '/'], ' » ', $key));
    }

    /**
     * Définit une valeur de configuration.
     */
    public function set(string $key, mixed $value): self
    {
        $this->ensureConfigLoaded($key);

        $this->configurator->set($key, $value);

        return $this;
    }

    /**
     * Reinitialise une configuration en fonction des donnees initiales issues des fichiers de configurations
     */
    public function reset(array|string|null $keys = null): void
    {
        $keys = null !== $keys ? (array) $keys : array_keys(self::$originals);

        foreach ($keys as $key) {
            $this->set($key, Helpers::dataGet(self::$originals, $key));

            if (str_starts_with($key, 'app')) {
                $this->initializeAutoDetect();
            }
        }
    }

    /**
     * Rend disponible un groupe de configuration qui n'existe pas (pas de fichier de configuration)
     * Ceci est notament utilse pour definir des configurations à la volée
     */
    public function ghost(array|string $key, array|Schema|null $structure = null): static
    {
        $schema = is_array($structure) ? Expect::mixed($structure) : $structure;

        $this->load($key, null, $schema, true);

        return $this;
    }

    /**
     * Charge une configuration
     *
     * @param list<string>|string $config
     */
    public function load($config, ?string $file = null, ?Schema $schema = null, bool $allow_empty = false): void
    {
        if (is_array($config)) {
            $this->loadMultiple($config, $allow_empty);

            return;
        }

        $this->loadSingle($config, $file, $schema, $allow_empty);
    }

    /**
     * Retourne la valeurs d'un registrar donné
     * 
     * @return array<string, array<mixed>>
     */
    public function registrar(string $key): array
    {
        return self::$registrarPaths[$key] ?? [];
    }

    /**
     * Charge plusieurs configurations
     *
     * @param list<string> $configs
     */
    private function loadMultiple(array $configs, bool $allow_empty = false): void
    {
        foreach ($configs as $key => $value) {
            if (is_string($key)) {
                // Cas: ['config_key' => 'file_path']
                $this->loadSingle($key, $value, null, $allow_empty);
            } else {
                // Cas: ['config_key']
                $this->loadSingle($value, null, null, $allow_empty);
            }
        }
    }

    /**
     * Charge une configuration unique
     */
    private function loadSingle(string $topLevelKey, ?string $file, ?Schema $schema, bool $allow_empty): void
    {
        // Vérifier si déjà chargé
        if (isset(self::$loaded[$topLevelKey])) {
            return;
        }

        $file ??= self::file($topLevelKey);
        $schema ??= self::schema($topLevelKey);

        $configurations = $this->loadConfigurationsFromFile($file);

        if (! empty(self::$registrars[$topLevelKey])) {
            $configurations = Arr::merge(self::$registrars[$topLevelKey], $configurations);
        }

        if ($configurations === [] && ! $allow_empty && ! is_a($schema, Schema::class, true)) {
            return;
        }

        $this->configurator->addSchema($topLevelKey, $schema ?? Expect::mixed());
        $this->configurator->merge([$topLevelKey => $configurations]);

        self::$loaded[$topLevelKey]    = $file;
        self::$originals[$topLevelKey] = $this->configurator->get($topLevelKey);
    }

    /**
     * Charge les configurations depuis un fichier
     */
    private function loadConfigurationsFromFile(string $file): array
    {
        if ($file === '' || $file === '0' || ! file_exists($file)) {
            return [];
        }

        if (in_array($file, get_included_files(), true)) {
            return []; // Le fichier est déjà inclus, on ne peut pas le requirer à nouveau
        }

        $configurations = require $file;

        return is_array($configurations) ? $configurations : [];
    }

    /**
     * S'assure que la configuration demandée est bien chargée
     */
    private function ensureConfigLoaded(string $key): void 
    {
        $topLevelKey = $this->configurator->getTopLevelKey($key);
        if (! isset(self::$loaded[$topLevelKey])) {
            $this->load($topLevelKey);
        }
    }

    /**
     * Renvoie le chemin du fichier d'un groupe de configuration donné
     */
    public static function file(string $topLevelKey): string
    {
        $path = preg_replace('#\.php$#', '', $topLevelKey);

        if (file_exists($file = CONFIG_PATH . $path . '.php')) {
            return $file;
        }

        $paths = service('locator')->search('Config/' . $path);

        if (! empty($paths[0]) && file_exists($paths[0])) {
            return $paths[0];
        }

        return '';
    }

    /**
     * Retrouve le schema de configuration d'un groupe
     */
    public static function schema(string $topLevelKey): ?Schema
    {
        $file = 'schemas' . DS . Helpers::ensureExt($topLevelKey . '.config', 'php');

        if (file_exists($syst_schema = SYST_PATH . 'Config' . DS . $file)) {
            return require $syst_schema;
        }
        if (file_exists($app_schema = CONFIG_PATH . $file)) {
            return require $app_schema;
        }

        $paths = service('locator')->search('Config/schemas/' . $topLevelKey);

        if (! empty($paths[0]) && file_exists($paths[0])) {
            return require $paths[0];
        }

        return null;
    }

    /**
     * Initialiser la configuration du système avec les données des fichier de configuration
     */
    private function initialize(): void
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
    private function loadRegistrar(): void
    {
        if (static::$didDiscovery) {
            return;
        }

        // La decouverte doit etre complete apres la premiere initalisation de la classe.
        if (static::$discovering) {
            $file = last(array_values(self::$registrarPaths));

            throw new ConfigException(
                'Pendant la découverte automatique des Registrars,'
                . ' "' . static::class . '" a été re-éxecuté.'
                . ' "' . clean_path($file) . '" doit avoir un mauvais code.'
            );
        }

        static::$discovering = true;

        $autoloader = new Autoloader(['psr4' => [APP_NAMESPACE => APP_PATH]]);
        $locator    = new Locator($autoloader->initialize());

        $registrarsFiles = $locator->search('Config/Registrar.php');

        foreach ($registrarsFiles as $file) {
            $this->processRegistrarFile($locator, $file);
        }

        static::$didDiscovery = true;
        static::$discovering  = false;
    }

    /**
     * Traite un fichier Registrar
     */
    private function processRegistrarFile(Locator $locator, string $file): void
    {
        if (false === $classname = $locator->findQualifiedNameFromPath($file)) {
            return;
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

            if (! isset(self::$registrarPaths[$name])) {
                self::$registrarPaths[$name] = [];
            }

            self::$registrarPaths[$name][$file] = array_merge(
                self::$registrarPaths[$name][$file] ?? [], 
                $result
            );
        }
    }

    /**
     * Initialise l'URL
     */
    private function initializeURL(): void
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
    private function initializeEnvironment(): void
    {
        $environment = $config = $this->get('app.environment', 'auto');

        $config = match (strtolower($config)) {
            'auto' => is_online() ? 'production' : 'development',
            'dev', 'development' => 'development',
            'prod', 'production' => 'production',
            'test', 'testing' => 'testing',
            default => throw new ConfigException("Environnement invalide : {$config}"),
        };

        if ($config !== $environment) {
            $this->set('app.environment', $config);
        }
    }

    /**
     * Initialise les donnees qui ont une valeur definie a "auto" et donc dependent de certains facteurs
     */
    private function initializeAutoDetect(): void
    {
        $this->initializeURL();
        $this->initializeEnvironment();
    }
}
