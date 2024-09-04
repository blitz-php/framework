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
use BlitzPHP\Cache\Cache;
use BlitzPHP\Cache\ResponseCache;
use BlitzPHP\Config\Config;
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\Cache\CacheInterface;
use BlitzPHP\Contracts\Container\ContainerInterface;
use BlitzPHP\Contracts\Database\ConnectionResolverInterface;
use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Contracts\Mail\MailerInterface;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Contracts\Router\RouterInterface;
use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Contracts\Session\CookieManagerInterface;
use BlitzPHP\Contracts\Session\SessionInterface;
use BlitzPHP\Debug\Logger;
use BlitzPHP\Debug\Timer;
use BlitzPHP\Debug\Toolbar;
use BlitzPHP\Event\EventManager;
use BlitzPHP\Filesystem\Filesystem;
use BlitzPHP\Filesystem\FilesystemManager;
use BlitzPHP\Http\Negotiator;
use BlitzPHP\Http\Redirection;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Http\ResponseEmitter;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Http\ServerRequestFactory;
use BlitzPHP\Http\Uri;
use BlitzPHP\Http\UrlGenerator;
use BlitzPHP\Mail\Mail;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Router\Router;
use BlitzPHP\Security\Encryption\Encryption;
use BlitzPHP\Session\Cookie\Cookie;
use BlitzPHP\Session\Cookie\CookieManager;
use BlitzPHP\Session\Handlers\Database as DatabaseSessionHandler;
use BlitzPHP\Session\Handlers\Database\MySQL as MySQLSessionHandler;
use BlitzPHP\Session\Handlers\Database\Postgre as PostgreSessionHandler;
use BlitzPHP\Session\Store;
use BlitzPHP\Translator\Translate;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\String\Text;
use BlitzPHP\View\Components\ComponentLoader;
use BlitzPHP\View\View;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Service
 *
 * Les services sont simplement d'autres classes/bibliothèques que le système utilise
 * pour faire son travail. Ceci est utilisé par BlitzPHP pour permettre au coeur du
 * framework à échanger facilement sans affecter l'utilisation à l'intérieur
 * le reste de votre application.
 *
 * Ceci est utilisé à la place d'un conteneur d'injection de dépendance principalement
 * en raison de sa simplicité, qui permet un meilleur entretien à long terme
 * des applications construites sur BlitzPHP. Un effet secondaire bonus
 * est que les IDE sont capables de déterminer quelle classe vous appelez
 * alors qu'avec les conteneurs DI, il n'y a généralement aucun moyen pour eux de le faire.
 */
class Services
{
    /**
     * Cache des instances des services demander comme instance "partagee".
     * La cle est le FQCN du service.
     */
    protected static array $instances = [];

    /**
     * Objets simulés à tester qui sont renvoyés s'ils existent.
     */
    protected static array $mocks = [];

    /**
     * Cache d'autres classe de que nous avons trouver via la methode cacheService.
     */
    protected static array $services = [];

    /**
     * Avons-nous déjà découvert d'autres Services ?
     */
    protected static bool $discovered = false;

    /**
     * Un cache des noms de classes de services trouvés.
     *
     * @var array<string>
     */
    private static array $serviceNames = [];

    /**
     * La classe Autoloader permet de charger les fichiers simplement.
     */
    public static function autoloader(bool $shared = true): Autoloader
    {
        if (true === $shared && isset(static::$instances[Autoloader::class])) {
            return static::$instances[Autoloader::class];
        }

        $config  = static::config()->get('autoload');
        $helpers = array_merge(['url'], ($config['helpers'] ?? []));

        return static::$instances[Autoloader::class] = new Autoloader(/** @scrutinizer ignore-type */ $config, $helpers);
    }

    /**
     * La classe de cache fournit un moyen simple de stocker et de récupérer
     * données complexes pour plus tard
     *
     * @return Cache
     */
    public static function cache(?array $config = null, bool $shared = true): CacheInterface
    {
        if ($config === null || $config === []) {
            $config = static::config()->get('cache');
        }

        if (true === $shared && isset(static::$instances[Cache::class])) {
            $instance = static::$instances[Cache::class];
            if (empty(func_get_args()[0])) {
                return $instance;
            }

            return $instance->setConfig($config);
        }

        return static::$instances[Cache::class] = new Cache($config);
    }

    /**
     * Les composants sont destinées à vous permettre d'insérer du HTML dans la vue
     * qui a été généré par n'importe quel appel dans le système.
     */
    public static function componentLoader(bool $shared = true): ComponentLoader
    {
        if (true === $shared && isset(static::$instances[ComponentLoader::class])) {
            return static::$instances[ComponentLoader::class];
        }

        return static::$instances[ComponentLoader::class] = new ComponentLoader(static::cache());
    }

    /**
     * La clase Config offre une api fluide por gerer les configurations de l'application
     */
    public static function config(bool $shared = true): Config
    {
        if (true === $shared && isset(static::$instances[Config::class])) {
            return static::$instances[Config::class];
        }

        return static::$instances[Config::class] = new Config();
    }

    /**
     * Conteneur d'injection de dependances
     *
     * @return Container
     */
    public static function container(bool $shared = true): ContainerInterface
    {
        if (true === $shared && isset(static::$instances[Container::class])) {
            return static::$instances[Container::class];
        }

        return static::$instances[Container::class] = new Container();
    }

    /**
     * Gestionnaire de cookies
     *
     * @return CookieManager
     */
    public static function cookie(bool $shared = true): CookieManagerInterface
    {
        if (true === $shared && isset(static::$instances[CookieManager::class])) {
            return static::$instances[CookieManager::class];
        }

        $config = (object) static::config()->get('cookie');

        return static::$instances[CookieManager::class] = (new CookieManager())->setDefaultPathAndDomain(
            $config->path ?: '/',
            $config->domain ?: '',
            $config->secure ?: false,
            $config->httponly ?: true,
            $config->samesite ?: 'Lax'
        );
    }

    /**
     * Émetteur de réponse au client
     */
    public static function emitter(bool $shared = true): ResponseEmitter
    {
        if (true === $shared && isset(static::$instances[ResponseEmitter::class])) {
            return static::$instances[ResponseEmitter::class];
        }

        return static::$instances[ResponseEmitter::class] = new ResponseEmitter();
    }

    /**
     * La classe Encryption fournit un cryptage bidirectionnel.
     *
     * @return Encryption
     */
    public static function encrypter(?array $config = null, bool $shared = false): EncrypterInterface
    {
        if (true === $shared && isset(static::$instances[Encryption::class])) {
            return static::$instances[Encryption::class];
        }

        $config ??= config('encryption');
        $config     = (object) $config;
        $encryption = new Encryption($config);
        $encryption->initialize($config);

        return static::$instances[Encryption::class] = $encryption;
    }

    /**
     * Gestionnaire d'evenement
     *
     * @return EventManager
     */
    public static function event(bool $shared = true): EventManagerInterface
    {
        if (true === $shared && isset(static::$instances[EventManager::class])) {
            return static::$instances[EventManager::class];
        }

        return static::$instances[EventManager::class] = new EventManager();
    }

    /**
     * System de gestion de fichier
     */
    public static function fs(bool $shared = true): Filesystem
    {
        if (true === $shared && isset(static::$instances[Filesystem::class])) {
            return static::$instances[Filesystem::class];
        }

        return static::$instances[Filesystem::class] = new Filesystem();
    }

    /**
     * Responsable du chargement des traductions des chaînes de langue.
     *
     * @deprecated 0.9 use translators instead
     */
    public static function language(?string $locale = null, bool $shared = true): Translate
    {
        return static::translator($locale, $shared);
    }

    /**
     * Le file locator fournit des methodes utilitaire pour chercher les fichiers non-classes dans les dossiers de namespace.
     * C'est une excelente methode pour charger les 'vues', 'helpers', et 'libraries'.
     *
     * @return Locator
     */
    public static function locator(bool $shared = true): LocatorInterface
    {
        if ($shared && isset(static::$instances[Locator::class])) {
            return static::$instances[Locator::class];
        }

        return static::$instances[Locator::class] = new Locator(static::autoloader());
    }

    /**
     * La classe Logger est une classe Logging compatible PSR-3 qui prend en charge
     * plusieurs gestionnaires qui traitent la journalisation réelle.
     *
     * @return Logger
     */
    public static function logger(bool $shared = true): LoggerInterface
    {
        if ($shared && isset(static::$instances[Logger::class])) {
            return static::$instances[Logger::class];
        }

        return static::$instances[Logger::class] = new Logger();
    }

    /**
     * La classe de mail vous permet d'envoyer par courrier électronique via mail, sendmail, SMTP.
     *
     * @return Mail
     */
    public static function mail(?array $config = null, bool $shared = true): MailerInterface
    {
        if ($config === null || $config === []) {
            $config = static::config()->get('mail');
        }

        if (true === $shared && isset(static::$instances[Mail::class])) {
            /** @var Mail $instance */
            $instance = static::$instances[Mail::class];
            if (empty(func_get_args()[0])) {
                return $instance;
            }

            return $instance->merge($config);
        }

        return static::$instances[Mail::class] = new Mail($config);
    }

    /**
     * La classe Input générale modélise une requête HTTP.
     */
    public static function negotiator(?ServerRequest $request = null, bool $shared = true): Negotiator
    {
        if ($request === null) {
            $request = static::request(true);
        }

        if (true === $shared && isset(static::$instances[Negotiator::class])) {
            $instance = static::$instances[Negotiator::class];
            if (empty(func_get_args()[0])) {
                return $instance;
            }

            return $instance->setRequest($request);
        }

        return static::$instances[Negotiator::class] = new Negotiator($request);
    }

    /**
     * La classe des redirections HTTP
     */
    public static function redirection(bool $shared = true): Redirection
    {
        if (true === $shared && isset(static::$instances[Redirection::class])) {
            return static::$instances[Redirection::class];
        }

        return static::$instances[Redirection::class] = new Redirection(static::factory(UrlGenerator::class));
    }

    /**
     * La classe Resquest modélise une reqûete HTTP.
     */
    public static function request(bool $shared = true): Request
    {
        if (true === $shared && isset(static::$instances[Request::class])) {
            return static::$instances[Request::class];
        }

        return static::$instances[Request::class] = ServerRequestFactory::fromGlobals();
    }

    /**
     * La classe Response modélise une réponse HTTP.
     */
    public static function response(bool $shared = true): Response
    {
        if (true === $shared && isset(static::$instances[Response::class])) {
            return static::$instances[Response::class];
        }

        return static::$instances[Response::class] = new Response();
    }

    /**
     * CacheResponse
     */
    public static function responsecache(bool $shared = true): ResponseCache
    {
        if (true === $shared && isset(static::$instances[ResponseCache::class])) {
            return static::$instances[ResponseCache::class];
        }

        return static::$instances[ResponseCache::class] = new ResponseCache(static::cache(), /** @scrutinizer ignore-type */ static::config()->get('cache.cache_query_string'));
    }

    /**
     * Le service Routes est une classe qui permet de construire facilement une collection de routes.
     *
     * @return RouteCollection
     */
    public static function routes(bool $shared = true): RouteCollectionInterface
    {
        if (true === $shared && isset(static::$instances[RouteCollection::class])) {
            return static::$instances[RouteCollection::class];
        }

        return static::$instances[RouteCollection::class] = new RouteCollection(static::locator(), (object) static::config()->get('routing'));
    }

    /**
     * La classe Router utilise le tableau de routes d'une RouteCollection et détermine
     * le contrôleur et la méthode corrects à exécuter.
     *
     * @return Router
     */
    public static function router(?RouteCollection $routes = null, ?ServerRequest $request = null, bool $shared = true): RouterInterface
    {
        if (true === $shared && isset(static::$instances[Router::class])) {
            return static::$instances[Router::class];
        }

        if ($routes === null) {
            $routes = static::routes(true);
        }
        if ($request === null) {
            $request = static::request(true);
        }

        return static::$instances[Router::class] = new Router($routes, $request);
    }

    /**
     * Retourne le gestionnaire de session.
     *
     * @return Store
     */
    public static function session(bool $shared = true): SessionInterface
    {
        if (true === $shared && isset(static::$instances[Store::class])) {
            return static::$instances[Store::class];
        }

        $config = static::config()->get('session');
        $db     = null;

        if (Text::contains($config['handler'], [DatabaseSessionHandler::class, 'database'])) {
            $group = $config['group'] ?? static::config()->get('database.connection');
            $db    = static::singleton(ConnectionResolverInterface::class)->connection($group);

            $driver = $db->getPlatform();

            if (Text::contains($driver, ['mysql', MySQLSessionHandler::class])) {
                $config['handler'] = MySQLSessionHandler::class;
            } elseif (Text::contains($driver, ['postgre', PostgreSessionHandler::class])) {
                $config['handler'] = PostgreSessionHandler::class;
            }
        }

        Cookie::setDefaults($cookies = /** @scrutinizer ignore-type */ static::config()->get('cookie'));
        $session = new Store((array) $config, (array) $cookies, Helpers::ipAddress());
        $session->setLogger(static::logger());
        $session->setDatabase($db);

        if (session_status() === PHP_SESSION_NONE) {
            $session->start();
        }

        return static::$instances[Store::class] = $session;
    }

    /**
     * System de gestion de fichier par disque
     */
    public static function storage(bool $shared = true): FilesystemManager
    {
        if ($shared && isset(static::$instances[FilesystemManager::class])) {
            return static::$instances[FilesystemManager::class];
        }

        return static::$instances[FilesystemManager::class] = new FilesystemManager(/** @scrutinizer ignore-type */ static::config()->get('filesystems'));
    }

    /**
     * La classe Timer fournit un moyen simple d'évaluer des parties de votre application.
     */
    public static function timer(bool $shared = true): Timer
    {
        if (true === $shared && isset(static::$instances[Timer::class])) {
            return static::$instances[Timer::class];
        }

        return static::$instances[Timer::class] = new Timer();
    }

    /**
     * Renvoie la barre d'outils de débogage.
     */
    public static function toolbar(?stdClass $config = null, bool $shared = true): Toolbar
    {
        if ($shared && isset(static::$instances[Toolbar::class])) {
            return static::$instances[Toolbar::class];
        }

        $config ??= (object) static::config()->get('toolbar');

        return static::$instances[Toolbar::class] = new Toolbar($config);
    }

    /**
     * Responsable du chargement des traductions des chaînes de langue.
     */
    public static function translator(?string $locale = null, bool $shared = true): Translate
    {
        if (empty($locale) && empty($locale = static::$instances[Translate::class . 'locale'] ?? null)) {
            $config = static::config()->get('app');
            if (($locale = static::negotiator()->language($config['supported_locales'])) === '' || ($locale = static::negotiator()->language($config['supported_locales'])) === '0') {
                $locale = $config['language'];
            }
            static::$instances[Translate::class . 'locale'] = $locale;
        }

        if (true === $shared && isset(static::$instances[Translate::class])) {
            return static::$instances[Translate::class]->setLocale($locale);
        }

        return static::$instances[Translate::class] = new Translate($locale, static::locator());
    }

    /**
     * La classe URI fournit un moyen de modéliser et de manipuler les URI.
     *
     * @return Uri
     */
    public static function uri(?string $uri = null, bool $shared = true): UriInterface
    {
        if (true === $shared && isset(static::$instances[Uri::class])) {
            return static::$instances[Uri::class]->setURI($uri);
        }

        return static::$instances[Uri::class] = new Uri($uri);
    }

    /**
     * La classe Renderer est la classe qui affiche réellement un fichier à l'utilisateur.
     * La classe View par défaut dans BlitzPHP est intentionnellement simple, mais
     * le service peut facilement être remplacé par un moteur de modèle si l'utilisateur en a besoin.
     */
    public static function viewer(bool $shared = true): View
    {
        if (true === $shared && isset(static::$instances[View::class])) {
            return static::$instances[View::class];
        }

        return static::$instances[View::class] = new View();
    }

    /**
     * Offre la possibilité d'effectuer des appels insensibles à la casse des noms de service.
     *
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (null === $service = static::serviceExists($name)) {
            return static::discoverServices($name, $arguments);
        }

        return $service::$name(...$arguments);
    }

    /**
     * Vérifiez si le service demandé est défini et renvoyez la classe déclarante.
     * Renvoie null s'il n'est pas trouvé.
     */
    public static function serviceExists(string $name): ?string
    {
        static::cacheServices();
        $services = array_merge(self::$serviceNames, [self::class]);
        $name     = strtolower($name);

        foreach ($services as $service) {
            if (method_exists($service, $name)) {
                return $service;
            }
        }

        return null;
    }

    /**
     * Injectez un objet fictif pour les tests.
     */
    public static function injectMock(string $name, object $mock): void
    {
        static::$mocks[strtolower($name)] = $mock;
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

    protected static function cacheServices(): void
    {
        if (! static::$discovered) {
            $locator = static::locator();
            $files   = $locator->search('Config/Services');

            // Obtenez des instances de toutes les classes de service et mettez-les en cache localement.
            foreach ($files as $file) {
                if (false === $classname = $locator->findQualifiedNameFromPath($file)) {
                    continue;
                }
                if (self::class !== $classname) {
                    self::$serviceNames[] = $classname;
                    static::$services[]   = new $classname();
                }
            }

            static::$discovered = true;
        }
    }

    /**
     * Injecter une seule instance de la classe donnée
     *
     * @return mixed
     */
    public static function singleton(string $name)
    {
        $arguments = func_get_args();
        $name      = array_shift($arguments);

        if (empty(static::$instances[$name])) {
            static::$instances[$name] = $arguments !== [] ? static::factory($name, $arguments) : static::container()->get($name);
        }

        return static::$instances[$name];
    }

    /**
     * Injecter une nouvelle instance de la classe donnée
     *
     * @return mixed
     */
    public static function factory(string $name, array $arguments = [])
    {
        return static::container()->make($name, $arguments);
    }

    /**
     * Définissez un objet ou une valeur dans le conteneur.
     *
     * @param string $name  Nom de l'entrée
     * @param mixed  $value utilisez les aides à la définition pour définir les objets
     */
    public static function set(string $name, $value)
    {
        static::$instances[$name] = $value;
        static::container()->set($name, $value);
    }

    /**
     * Réinitialisez les instances partagées et les simulations pour les tests.
     */
    public static function reset(bool $initAutoloader = true): void
    {
        // static::$mocks     = [];
        static::$instances = [];

        if ($initAutoloader) {
            static::autoloader()->initialize();
        }
    }
}
