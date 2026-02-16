<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Container;

use BlitzPHP\Autoloader\Autoloader;
use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Autoloader\LocatorCached;
use BlitzPHP\Cache\Handlers\FileVarExportHandler;
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Contracts\Router\RouterInterface;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Router\Router;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * {@internal utilisé par \BlitzPHP\Container\Services}
 */
class BaseServices
{
    /**
     * Cache des instances des services demandés comme instance "partagee".
     * Les clés doivent être des noms de service en minuscules.
     *
     * @var array<string, object> [key => instance]
     */
    protected static array $instances = [];

    /**
     * Liste des methodes de fabrique.
     *
     * @var array<string, (callable(mixed ...$params): object)> [key => callable]
     */
    protected static array $factories = [];

    /**
     * Objets simulés à tester qui sont renvoyés s'ils existent.
     *
     * @var array<string, object> [key => instance]
     */
    protected static array $mocks = [];

    /**
     * Avons-nous déjà découvert d'autres Services ?
     */
    protected static bool $discovered = false;

    /**
     * Un cache des noms de classes de services trouvés.
     *
     * @var list<string>
     */
    private static array $serviceNames = [];

    /**
     * Cache des noms canoniques des services.
     *
     * @var list<string>
     */
    private static array $nameCache = [];

    /**
     * Mapping alias → nom canonique.
     *
     * @var array<string, list<string>>
     */
    private static array $aliases = [
        'locator'  => [Locator::class, LocatorInterface::class],
        'request'  => [Request::class, ServerRequest::class, ServerRequestInterface::class],
        'response' => [Response::class, ResponseInterface::class],
        'router'   => [Router::class, RouterInterface::class],
        'routes'   => [RouteCollection::class, RouteCollectionInterface::class],
    ];

    /**
     * La classe Autoloader permet de charger les fichiers simplement.
     */
    public static function autoloader(bool $shared = true): Autoloader
    {
        if ($shared) {
            return static::sharedInstance('autoloader');
        }

        $config  = static::config()->get('autoload');
        $helpers = array_merge(['url'], ($config['helpers'] ?? []));

        return new Autoloader(/** @scrutinizer ignore-type */ $config, $helpers);
    }

    /**
     * Le file locator fournit des methodes utilitaire pour chercher les fichiers non-classes dans les dossiers de namespace.
     * C'est une excelente methode pour charger les 'vues', 'helpers', et 'libraries'.
     */
    public static function locator(bool $shared = true): LocatorInterface
    {
        if ($shared) {
            if (! isset(static::$instances['locator'])) {
                $locator = new Locator(static::autoloader());
                if (true === config('optimize.locator_cache_enabled', false)) {
                    static::$instances['locator'] = new LocatorCached($locator, new FileVarExportHandler(FRAMEWORK_STORAGE_PATH . 'cache'));
                } else {
                    static::$instances['locator'] = $locator;
                }
            }

            return static::$mocks['locator'] ?? static::$instances['locator'];
        }

        return new Locator(static::autoloader());
    }

    /**
     * Méthode simple pour obtenir rapidement une entrée.
     *
     * @param string $key Identifiant de l'entrée à rechercher.
     *
     * @return object|null Entrée.
     */
    public static function get(string $key): ?object
    {
        return static::$instances[$key] ?? static::__callStatic($key, []);
    }

    /**
     * Définit une entrée
     */
    public static function set(string $key, object $value)
    {
        $name = self::serviceName($key);

        if (isset(static::$instances[$name])) {
            throw new InvalidArgumentException("L'entrée pour '" . $key . "' est déjà définie.");
        }

        static::$instances[$name] = $value;
    }

    /**
     * Remplace une entrée existante.
     */
    public static function override(string $key, object $value): void
    {
        static::$instances[$name = self::serviceName($key)] = $value;

        if (isset(self::$aliases[$name])) {
            foreach (self::$aliases[$name] ?? [] as $item) {
                static::container()->set($item, $value);
            }
        } else {
            static::container()->set($name, $value);
        }
    }

    /**
     * Injecter une seule instance de la classe donnée
     *
     * @template T
     *
     * @param class-string<T>|string $name
     *
     * @return mixed|T
     */
    public static function singleton(string $name): mixed
    {
        $arguments = func_get_args();
        $name      = array_shift($arguments);
        $name      = self::serviceName($name);

        if (empty(static::$instances[$name])) {
            static::$instances[$name] = $arguments !== []
                ? static::factory($name, $arguments)
                : static::container()->get($name);
        }

        return static::$instances[$name];
    }

    /**
     * Injecter une nouvelle instance de la classe donnée
     *
     * @template T
     *
     * @param class-string<T>|string $name
     *
     * @return object|T
     */
    public static function factory(string $name, array $arguments = []): mixed
    {
        return static::container()->make($name, $arguments);
    }

    /**
     * Injectez un objet fictif pour les tests.
     *
     * @testTag disponible uniquement pour le code de test
     */
    public static function injectMock(string $name, object $mock): void
    {
        static::$instances[$name]         = $mock;
        static::$mocks[strtolower($name)] = $mock;
    }

    /**
     * Réinitialisez les instances partagées et les simulations pour les tests.
     *
     * @testTag disponible uniquement pour le code de test
     */
    public static function reset(bool $initAutoloader = true): void
    {
        static::$mocks     = [];
        static::$instances = [];
        static::$factories = [];

        if ($initAutoloader) {
            static::autoloader()->initialize();
        }
    }

    /**
     * Réinitialise toutes les instances fictives et partagées pour un seul service.
     *
     * @testTag disponible uniquement pour le code de test
     */
    public static function resetSingle(string ...$name): void
    {
        foreach ($name as $n) {
            $n = strtolower($n);
            unset(static::$mocks[$n], static::$instances[$n]);
        }
    }

    /**
     * Vérifiez si le service demandé est défini et renvoyez la classe déclarante.
     * Renvoie null s'il n'est pas trouvé.
     */
    public static function serviceExists(string $name): ?string
    {
        static::cacheServices();

        $services = array_merge(self::$serviceNames, [Services::class]);
        $name     = strtolower($name);

        foreach ($services as $service) {
            if (method_exists($service, $name)) {
                static::$factories[$name] = [$service, $name];

                return $service;
            }
        }

        return null;
    }

    /**
     * Normalise le nom de service via mapping.
     *
     * @param string $name Nom original.
     *
     * @return string Nom canonique.
     */
    public static function serviceName(string $name): string
    {
        if (isset(self::$nameCache[$name])) {
            return self::$nameCache[$name];
        }

        if (array_key_exists($n = strtolower($name), self::$aliases)) {
            return self::$nameCache[$name] = $n;
        }

        foreach (self::$aliases as $k => $v) {
            if (in_array($name, $v, true)) {
                return self::$nameCache[$name] = $k;
            }
        }

        return self::$nameCache[$name] = $name;
    }

    /**
     * Résout tous les aliases pour un nom donné.
     * Inclut le nom original du service et tous ses aliases
     *
     * @param string $name Nom original.
     *
     * @return list<string> Liste de tous les alias du service
     */
    public static function resolveServiceAliases(string $name): array
    {
        $keys = [$name];

        foreach (self::$aliases as $canonical => $aliases) {
            // Si le nom est le nom canonique
            if ($canonical === $n = strtolower($name)) {
                $keys = array_merge([$n], $aliases);
                break;
            }

            // Si le nom est dans les aliases
            if (in_array($name, $aliases, true)) {
                $keys = array_merge($keys, [$canonical], $aliases);
                break;
            }
        }

        return array_unique($keys);
    }

    /**
     * Offre la possibilité d'effectuer des appels insensibles à la casse des noms de service.
     *
     * @return object|null
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (isset(static::$factories[$name])) {
            return static::$factories[$name](...$arguments);
        }

        if (null === $service = static::serviceExists($name)) {
            return static::discoverServices($name, $arguments);
        }

        return $service::$name(...$arguments);
    }

    /**
     * Renvoie une instance partagée de l'un des services de la classe.
     *
     * $key doit être un nom correspondant à un service.
     *
     * @param array|bool|float|int|object|string|null ...$params
     */
    protected static function sharedInstance(string $key, ...$params)
    {
        $key = strtolower($key);

        //  Renvoie une simulation si elle existe
        if (isset(static::$mocks[$key])) {
            return static::$mocks[$key];
        }

        if (! isset(static::$instances[$key])) {
            // Assurez-vous que $shared est faux.
            $params[] = false;

            static::$instances[$key] = Services::$key(...$params);
        }

        return static::$instances[$key];
    }

    /**
     * Essaie d'obtenir un service à partir du conteneur
     *
     * @return mixed
     */
    protected static function discoverServices(string $name, array $arguments)
    {
        if (true !== array_pop($arguments)) {
            return static::factory($name, $arguments);
        }

        return static::singleton($name, ...$arguments);
    }

    /**
     * Découvre et cache les classes de services.
     */
    protected static function cacheServices(): void
    {
        if (static::$discovered) {
            return;
        }

        $locator = static::locator();
        $files   = $locator->search('Config/Services');

        // Obtenez des instances de toutes les classes de service et mettez-les en cache localement.
        foreach ($files as $file) {
            if (false === $classname = $locator->findQualifiedNameFromPath($file)) {
                continue;
            }
            if (! in_array($classname, [Services::class, self::class], true)) {
                self::$serviceNames[] = $classname;
            }
        }

        static::$discovered = true;
    }
}
