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
use BlitzPHP\Config\Config;
use BlitzPHP\Db\ConnectionResolver;
use BlitzPHP\Debug\Logger;
use BlitzPHP\Debug\Timer;
use BlitzPHP\Debug\Toolbar;
use BlitzPHP\Event\EventManager;
use BlitzPHP\Filesystem\Filesystem;
use BlitzPHP\Filesystem\FilesystemManager;
use BlitzPHP\Http\Negotiator;
use BlitzPHP\HTTP\Redirection;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Http\ResponseEmitter;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Http\Uri;
use BlitzPHP\HttpClient\Request as ClientRequest;
use BlitzPHP\Mail\Mail;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Router\Router;
use BlitzPHP\Session\Cookie\Cookie;
use BlitzPHP\Session\Handlers\Database as DatabaseSessionHandler;
use BlitzPHP\Session\Handlers\Database\MySQL as MySQLSessionHandler;
use BlitzPHP\Session\Handlers\Database\Postgre as PostgreSessionHandler;
use BlitzPHP\Session\Store;
use BlitzPHP\Translator\Translate;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\String\Text;
use BlitzPHP\View\View;
use DI\NotFoundException;
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
     * Cache d'autres classe de que nous avons trouver via la methode discoverService.
     */
    protected static array $services = [];

    /**
     * @return Injector
     */
    public static function injector()
    {
        return Injector::instance();
    }

    /**
     * @return \DI\Container
     */
    public static function container()
    {
        return Injector::container();
    }

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

        return static::$instances[Autoloader::class] = static::factory(Autoloader::class, compact('config', 'helpers'));
    }

    /**
     * La classe de cache fournit un moyen simple de stocker et de récupérer
     * données complexes pour plus tard
     */
    public static function cache(?array $config = null, bool $shared = true): Cache
    {
        if (empty($config)) {
            $config = static::config()->get('cache');
        }

        if (true === $shared && isset(static::$instances[Cache::class])) {
            $instance = static::$instances[Cache::class];
            if (empty(func_get_args()[0])) {
                return $instance;
            }

            return $instance->setConfig($config);
        }

        return static::$instances[Cache::class] = static::factory(Cache::class, compact('config'));
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
     * Émetteur de réponse au client
     */
    public static function emitter(bool $shared = true): ResponseEmitter
    {
        if (true === $shared && isset(static::$instances[ResponseEmitter::class])) {
            return static::$instances[ResponseEmitter::class];
        }

        return static::$instances[ResponseEmitter::class] = static::factory(ResponseEmitter::class);
    }

    /**
     * Gestionnaire d'evenement
     */
    public static function event(bool $shared = true): EventManager
    {
        if (true === $shared && isset(static::$instances[EventManager::class])) {
            return static::$instances[EventManager::class];
        }

        return static::$instances[EventManager::class] = static::factory(EventManager::class);
    }

    /**
     * System de gestion de fichier
     */
    public static function fs(bool $shared = true): Filesystem
    {
        if (true === $shared && isset(static::$instances[Filesystem::class])) {
            return static::$instances[Filesystem::class];
        }

        return static::$instances[Filesystem::class] = static::factory(Filesystem::class);
    }

    /**
     * Le client HTTP fourni une interface simple pour interagir avec d'autres serveurs.
     * Typiquement a traver des APIs.
     */
    public static function httpclient(?string $baseUrl = null, bool $shared = true): ClientRequest
    {
        if (true === $shared && isset(static::$instances[ClientRequest::class])) {
            return static::$instances[ClientRequest::class]->baseUrl((string) $baseUrl);
        }

        return static::$instances[ClientRequest::class] = static::factory(ClientRequest::class, ['event' => static::event()])->baseUrl((string) $baseUrl);
    }

    /**
     * Responsable du chargement des traductions des chaînes de langue.
     */
    public static function language(?string $locale = null, bool $shared = true): Translate
    {
        if (empty($locale)) {
            if (empty($locale = static::$instances[Translate::class . 'locale'] ?? null)) {
                $config = static::config()->get('app');

                if (empty($locale = static::negotiator()->language($config['supported_locales']))) {
                    $locale = $config['language'];
                }

                static::$instances[Translate::class . 'locale'] = $locale;
            }
        }

        if (true === $shared && isset(static::$instances[Translate::class])) {
            return static::$instances[Translate::class]->setLocale($locale);
        }

        return static::$instances[Translate::class] = static::factory(Translate::class, ['locale' => $locale, 'locator' => static::locator()]);
    }

    /**
     * Le file locator fournit des methodes utilitaire pour chercher les fichiers non-classes
     * dans les dossiers de namespace. C'est une excelente methode pour charger les 'vues', 'helpers', et 'libraries'.
     */
    public static function locator(bool $shared = true): Locator
    {
        if ($shared && isset(static::$instances[Locator::class])) {
            return static::$instances[Locator::class];
        }

        return static::$instances[Locator::class] = static::factory(Locator::class, ['autoloader' => static::autoloader()]);
    }

    /**
     * La classe Logger est une classe Logging compatible PSR-3 qui prend en charge
     * plusieurs gestionnaires qui traitent la journalisation réelle.
     */
    public static function logger(bool $shared = true): Logger
    {
        if ($shared && isset(static::$instances[Logger::class])) {
            return static::$instances[Logger::class];
        }

        return static::$instances[Logger::class] = static::factory(Logger::class);
    }

    /**
     * La classe de mail vous permet d'envoyer par courrier électronique via mail, sendmail, SMTP.
     */
    public static function mail(?array $config = null, bool $shared = true): Mail
    {
        if (empty($config)) {
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

        return static::$instances[Mail::class] = static::factory(Mail::class, compact('config'));
    }

    /**
     * La classe Input générale modélise une requête HTTP.
     */
    public static function negotiator(?ServerRequest $request = null, bool $shared = true): Negotiator
    {
        if (empty($request)) {
            $request = static::request(true);
        }

        if (true === $shared && isset(static::$instances[Negotiator::class])) {
            $instance = static::$instances[Negotiator::class];
            if (empty(func_get_args()[0])) {
                return $instance;
            }

            return $instance->setRequest($request);
        }

        return static::$instances[Negotiator::class] = static::factory(Negotiator::class, compact('request'));
    }

    /**
     * La classe des redirections HTTP
     */
    public static function redirection(bool $shared = true): Redirection
    {
        if (true === $shared && isset(static::$instances[Redirection::class])) {
            return static::$instances[Redirection::class];
        }

        return static::$instances[Redirection::class] = static::factory(Redirection::class);
    }

    /**
     * La classe Resquest modélise une reqûete HTTP.
     */
    public static function request(bool $shared = true): Request
    {
        if (true === $shared && isset(static::$instances[Request::class])) {
            return static::$instances[Request::class];
        }

        return static::$instances[Request::class] = static::factory(Request::class);
    }

    /**
     * La classe Response modélise une réponse HTTP.
     */
    public static function response(bool $shared = true): Response
    {
        if (true === $shared && isset(static::$instances[Response::class])) {
            return static::$instances[Response::class];
        }

        return static::$instances[Response::class] = static::factory(Response::class);
    }

    /**
     * Le service Routes est une classe qui permet de construire facilement
     * une collection d'itinéraires.
     */
    public static function routes(bool $shared = true): RouteCollection
    {
        if (true === $shared && isset(static::$instances[RouteCollection::class])) {
            return static::$instances[RouteCollection::class];
        }

        return static::$instances[RouteCollection::class] = static::factory(RouteCollection::class, [
			'routing' => (object) config('routing'),
			'locator' => static::locator(),
		]);
    }

    /**
     * La classe Router utilise le tableau de routes d'une RouteCollection et détermine
     * le contrôleur et la méthode corrects à exécuter.
     */
    public static function router(?RouteCollection $routes = null, ?ServerRequest $request = null, bool $shared = true): Router
    {
        if (true === $shared) {
            return static::singleton(Router::class);
        }
        if (empty($routes)) {
            $routes = static::routes(true);
        }
        if (empty($request)) {
            $request = static::request(true);
        }

        return static::factory(Router::class, compact('routes', 'request'));
    }

    /**
     * Retourne le gestionnaire de session.
     */
    public static function session(bool $shared = true): Store
    {
        if (true === $shared && isset(static::$instances[Store::class])) {
            return static::$instances[Store::class];
        }

        $config = static::config()->get('session');
        $db     = null;

        if (Text::contains($config['handler'], [DatabaseSessionHandler::class, 'database'])) {
            $group = $config['group'] ?? static::config()->get('database.connection');
            $db    = static::singleton(ConnectionResolver::class)->connection($group);

            $driver = $db->getPlatform();

            if (Text::contains($driver, ['mysql', MySQLSessionHandler::class])) {
                $config['handler'] = MySQLSessionHandler::class;
            } elseif (Text::contains($driver, ['postgre', PostgreSessionHandler::class])) {
                $config['handler'] = PostgreSessionHandler::class;
            }
        }

        Cookie::setDefaults($cookies = static::config()->get('cookie'));
        $session = new Store($config, $cookies, Helpers::ipAddress());
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

        return static::$instances[FilesystemManager::class] = new FilesystemManager(static::config()->get('filesystems'));
    }

    /**
     * La classe Timer fournit un moyen simple d'évaluer des parties de votre application.
     */
    public static function timer(bool $shared = true): Timer
    {
        if (true === $shared && isset(static::$instances[Timer::class])) {
            return static::$instances[Timer::class];
        }

        return static::$instances[Timer::class] = static::factory(Timer::class);
    }

    /**
     * Renvoie la barre d'outils de débogage.
     */
    public static function toolbar(?stdClass $config = null, bool $shared = true): Toolbar
    {
        if ($shared && isset(static::$instances[Toolbar::class])) {
            return static::$instances[Toolbar::class];
        }

        $config ??= (object) config('toolbar');

        return static::$instances[Toolbar::class] = static::factory(Toolbar::class, compact('config'));
    }

    /**
     * La classe URI fournit un moyen de modéliser et de manipuler les URI.
     */
    public static function uri(?string $uri = null, bool $shared = true): Uri
    {
        if (true === $shared && isset(static::$instances[Uri::class])) {
            return static::$instances[Uri::class]->setURI($uri);
        }

        return static::$instances[Uri::class] = static::factory(Uri::class, compact('uri'));
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

        return static::$instances[View::class] = static::factory(View::class);
    }

    /**
     * Offre la possibilité d'effectuer des appels insensibles à la casse des noms de service.
     *
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (method_exists(static::class, $name)) {
            return static::$name(...$arguments);
        }

        return static::discoverServices($name, $arguments);
    }

    /**
     * Essaie d'obtenir un service à partir du conteneur
     *
     * @return mixed
     */
    protected static function discoverServices(string $name, array $arguments)
    {
        $shared = array_pop($arguments);
        if ($shared !== true) {
            return static::discoverServiceFactory($name, $arguments);
        }

        return static::discoverServiceSingleton($name, ...$arguments);
    }

    /**
     * Essaie d'obtenir un service à partir du conteneur
     *
     * @return mixed
     */
    private static function discoverServiceFactory(string $name, array $arguments)
    {
        try {
            return static::factory($name, $arguments);
        } catch (NotFoundException $e) {
            try {
                return static::factory($name . 'Service', $arguments);
            } catch (NotFoundException $ex) {
                throw $e;
            }
        }
    }

    /**
     * Essaie de trouver un seul service
     *
     * @return mixed
     */
    private static function discoverServiceSingleton(string $name)
    {
        $arguments = func_get_args();
        $name      = array_shift($arguments);

        try {
            return static::singleton($name, ...$arguments);
        } catch (NotFoundException $e) {
            try {
                return static::singleton($name . 'Service', ...$arguments);
            } catch (NotFoundException $ex) {
                throw $e;
            }
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
            if (! empty($arguments)) {
                static::$instances[$name] = static::factory($name, $arguments);
            } else {
                static::$instances[$name] = static::injector()->get($name);
            }
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
        return static::injector()->make($name, $arguments);
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
}
