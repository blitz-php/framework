<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Loader;

use BlitzPHP\Cache\Cache;
use BlitzPHP\Config\Config;
use BlitzPHP\Debug\Logger;
use BlitzPHP\Debug\Timer;
use BlitzPHP\Debug\Toolbar;
use BlitzPHP\Event\EventManager;
use BlitzPHP\Http\Negotiator;
use BlitzPHP\HTTP\Redirection;
use BlitzPHP\Http\Response;
use BlitzPHP\Http\ResponseEmitter;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Http\Session;
use BlitzPHP\Http\Uri;
use BlitzPHP\Output\Language;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Router\Router;
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
     * La classe de cache fournit un moyen simple de stocker et de récupérer
     * données complexes pour plus tard
     */
    public static function cache(bool $shared = true): Cache
    {
        $config = Config::get('cache');

        if (true === $shared) {
            return self::singleton(Cache::class)->setConfig($config);
        }

        return self::factory(Cache::class, [$config]);
    }

    /**
     * Émetteur de réponse au client
     */
    public static function emitter(bool $shared = true): ResponseEmitter
    {
        if (true === $shared) {
            return self::singleton(ResponseEmitter::class);
        }

        return self::factory(ResponseEmitter::class);
    }

    /**
     * Gestionnaire d'evenement
     */
    public static function event(bool $shared = true): EventManager
    {
        if (true === $shared) {
            return self::singleton(EventManager::class);
        }

        return self::factory(EventManager::class);
    }

    /**
     * Responsable du chargement des traductions des chaînes de langue.
     */
    public static function language(?string $locale = null, bool $shared = true): Language
    {
        if (true === $shared) {
            return self::singleton(Language::class)->setLocale($locale);
        }

        return self::factory(Language::class)->setLocale($locale);
    }

    /**
     * La classe Logger est une classe Logging compatible PSR-3 qui prend en charge
     * plusieurs gestionnaires qui traitent la journalisation réelle.
     */
    public static function logger(bool $shared = true): Logger
    {
        if ($shared) {
            return self::singleton(Logger::class);
        }

        return self::factory(Logger::class);
    }

    /**
     * La classe Input générale modélise une requête HTTP.
     */
    public static function negotiator(?ServerRequest $request = null, bool $shared = true): Negotiator
    {
        if (empty($request)) {
            $request = static::request(true);
        }

        if (true === $shared) {
            return self::singleton(Negotiator::class)->setRequest($request);
        }

        return self::factory(Negotiator::class, [$request]);
    }

    /**
     * La classe des redirections HTTP
     */
    public static function redirection(bool $shared = true): Redirection
    {
        if (true === $shared) {
            return self::singleton(Redirection::class);
        }

        return self::factory(Redirection::class);
    }

    /**
     * La classe Resquest modélise une reqûete HTTP.
     */
    public static function request(bool $shared = true): ServerRequest
    {
        if (true === $shared) {
            return self::singleton(ServerRequest::class);
        }

        return self::factory(ServerRequest::class);
    }

    /**
     * La classe Response modélise une réponse HTTP.
     */
    public static function response(bool $shared = true): Response
    {
        if (true === $shared) {
            return self::singleton(Response::class);
        }

        return self::factory(Response::class);
    }

    /**
     * Le service Routes est une classe qui permet de construire facilement
     * une collection d'itinéraires.
     */
    public static function routes(bool $shared = true): RouteCollection
    {
        if (true === $shared) {
            return self::singleton(RouteCollection::class);
        }

        return self::factory(RouteCollection::class);
    }

    /**
     * La classe Router utilise le tableau de routes d'une RouteCollection et détermine
     * le contrôleur et la méthode corrects à exécuter.
     */
    public static function router(?RouteCollection $routes = null, ?ServerRequest $request = null, bool $shared = true): Router
    {
        if (true === $shared) {
            return self::singleton(Router::class);
        }
        if (empty($routes)) {
            $routes = static::routes(true);
        }
        if (empty($request)) {
            $request = static::request(true);
        }

        return self::factory(Router::class)->init($routes, $request);
    }

    /**
     * Return the session manager.
     */
    public static function session(bool $shared = true): Session
    {
        if (true === $shared) {
            return self::singleton(Session::class);
        }

        /**
         * @var Session
         */
        $session = self::factory(Session::class);

        if (session_status() === PHP_SESSION_NONE) {
            $session->start();
        }

        return $session;
    }

    /**
     * La classe Timer fournit un moyen simple d'évaluer des parties de votre application.
     */
    public static function timer(bool $shared = true): Timer
    {
        if (true === $shared) {
            return self::singleton(Timer::class);
        }

        return self::factory(Timer::class);
    }

    /**
     * Renvoie la barre d'outils de débogage.
     */
    public static function toolbar(?stdClass $config = null, bool $shared = true): Toolbar
    {
        if ($shared) {
            return self::singleton(Toolbar::class);
        }

        $config ??= (object) config('toolbar');

        return self::factory(Toolbar::class, [$config]);
    }

    /**
     * La classe URI fournit un moyen de modéliser et de manipuler les URI.
     */
    public static function uri(?string $uri = null, bool $shared = true): Uri
    {
        if (true === $shared) {
            return self::singleton(Uri::class)->setURI($uri);
        }

        return self::factory(Uri::class, [$uri]);
    }

    /**
     * La classe Renderer est la classe qui affiche réellement un fichier à l'utilisateur.
     * La classe View par défaut dans BlitzPHP est intentionnellement simple, mais
     * le service peut facilement être remplacé par un moteur de modèle si l'utilisateur en a besoin.
     */
    public static function viewer(bool $shared = true): View
    {
        if (true === $shared) {
            return self::singleton(View::class);
        }

        return self::factory(View::class);
    }

    /**
     * Offre la possibilité d'effectuer des appels insensibles à la casse des noms de service.
     *
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (method_exists(self::class, $name)) {
            return self::$name(...$arguments);
        }

        return self::discoverServices($name, $arguments);
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
            return self::discoverServiceFactory($name, $arguments);
        }

        return self::discoverServiceSingleton($name);
    }

    /**
     * Essaie d'obtenir un service à partir du conteneur
     *
     * @return mixed
     */
    private static function discoverServiceFactory(string $name, array $arguments)
    {
        try {
            return self::factory($name, $arguments);
        } catch (NotFoundException $e) {
            try {
                return self::factory($name . 'Service', $arguments);
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
        try {
            return self::singleton($name);
        } catch (NotFoundException $e) {
            try {
                return self::singleton($name . 'Service');
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
        return self::injector()->get($name);
    }

    /**
     * Injecter une nouvelle instance de la classe donnée
     *
     * @return mixed
     */
    public static function factory(string $name, array $arguments = [])
    {
        return self::injector()->make($name, $arguments);
    }
}
