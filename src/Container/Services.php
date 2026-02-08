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

use BlitzPHP\Cache\Cache;
use BlitzPHP\Cache\ResponseCache;
use BlitzPHP\Config\Config;
use BlitzPHP\Contracts\Cache\CacheInterface;
use BlitzPHP\Contracts\Container\ContainerInterface;
use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Contracts\Mail\MailerInterface;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Contracts\Router\RouterInterface;
use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Contracts\Security\HasherInterface;
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
use BlitzPHP\Security\Hashing\Hasher;
use BlitzPHP\Session\Cookie\Cookie;
use BlitzPHP\Session\Cookie\CookieManager;
use BlitzPHP\Session\Store;
use BlitzPHP\Translator\Translate;
use BlitzPHP\Utilities\Helpers;
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
class Services extends BaseServices
{
    /**
     * La classe de cache fournit un moyen simple de stocker et de récupérer
     * données complexes pour plus tard
     *
     * @return Cache
     */
    public static function cache(?array $config = null, bool $shared = true): CacheInterface
    {
        if ($shared) {
            return static::sharedInstance('cache', $config);
        }

        if ($config === null || $config === []) {
            $config = static::get('config')->get('cache');
        }

        return new Cache($config);
    }

    /**
     * Les composants sont destinées à vous permettre d'insérer du HTML dans la vue
     * qui a été généré par n'importe quel appel dans le système.
     */
    public static function componentLoader(bool $shared = true): ComponentLoader
    {
        if ($shared) {
            return static::sharedInstance('componentLoader');
        }

        return new ComponentLoader(static::get('cache'));
    }

    /**
     * La clase Config offre une api fluide por gerer les configurations de l'application
     */
    public static function config(bool $shared = true): Config
    {
        if ($shared) {
            return static::sharedInstance('config');
        }

        return new Config();
    }

    /**
     * Conteneur d'injection de dependances
     *
     * @return Container
     */
    public static function container(bool $shared = true): ContainerInterface
    {
        if ($shared) {
            return static::sharedInstance('container');
        }

        return new Container();
    }

    /**
     * Gestionnaire de cookies
     *
     * @return CookieManager
     */
    public static function cookie(bool $shared = true): CookieManagerInterface
    {
        if ($shared) {
            return static::sharedInstance('cookie');
        }

        $config = (object) static::get('config')->get('cookie');

        return (new CookieManager())->setDefaultPathAndDomain(
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
        if ($shared) {
            return static::sharedInstance('emitter');
        }

        return new ResponseEmitter();
    }

    /**
     * La classe Encryption fournit un cryptage bidirectionnel.
     *
     * @return Encryption
     */
    public static function encrypter(?array $config = null, bool $shared = false): EncrypterInterface
    {
        if ($shared) {
            return static::sharedInstance('encrypter', $config);
        }

        if ($config === null || $config === []) {
            $config = static::get('config')->get('encryption');
        }

        $config     = (object) $config;
        $encryption = new Encryption($config);
        $encryption->initialize($config);

        return $encryption;
    }

    /**
     * Gestionnaire d'evenement
     *
     * @return EventManager
     */
    public static function event(bool $shared = true): EventManagerInterface
    {
        if ($shared) {
            return static::sharedInstance('event');
        }

        return new EventManager();
    }

    /**
     * System de gestion de fichier
     */
    public static function fs(bool $shared = true): Filesystem
    {
        if ($shared) {
            return static::sharedInstance('fs');
        }

        return new Filesystem();
    }

    /**
     * La classe Encryption fournit un cryptage bidirectionnel.
     *
     * @return Hasher
     */
    public static function hashing(?array $config = null, bool $shared = true): HasherInterface
    {
        if ($shared) {
            return static::sharedInstance('hashing', $config);
        }

        if ($config === null || $config === []) {
            $config = static::get('config')->get('hashing');
        }

        $config = (object) $config;
        $hasher = new Hasher($config);
        $hasher->initialize($config);

        return $hasher;
    }

    /**
     * La classe Logger est une classe Logging compatible PSR-3 qui prend en charge
     * plusieurs gestionnaires qui traitent la journalisation réelle.
     *
     * @return Logger
     */
    public static function logger(bool $shared = true): LoggerInterface
    {
        if ($shared) {
            return static::sharedInstance('logger');
        }

        return new Logger();
    }

    /**
     * La classe de mail vous permet d'envoyer par courrier électronique via mail, sendmail, SMTP.
     *
     * @return Mail
     */
    public static function mail(?array $config = null, bool $shared = true): MailerInterface
    {
        if ($shared) {
            return static::sharedInstance('mail', $config);
        }

        if ($config === null || $config === []) {
            $config = static::get('config')->get('mail');
        }

        return new Mail($config, static::event());
    }

    /**
     * La classe Negotiator fournit les fonctionnalités de négociation de contenu permettant de traiter
     * la requête afin de déterminer la langue, l'encodage, le jeu de caractères et d'autres éléments appropriés.
     */
    public static function negotiator(?ServerRequest $request = null, bool $shared = true): Negotiator
    {
        if ($shared) {
            return static::sharedInstance('negotiator', $request);
        }

        $request ??= static::get('request');

        return new Negotiator($request);
    }

    /**
     * La classe des redirections HTTP
     */
    public static function redirection(bool $shared = true): Redirection
    {
        if ($shared) {
            return static::sharedInstance('redirection');
        }

        return new Redirection(static::factory(UrlGenerator::class));
    }

    /**
     * La classe Resquest modélise une reqûete HTTP.
     */
    public static function request(bool $shared = true): Request
    {
        if ($shared) {
            return static::sharedInstance('request');
        }

        return ServerRequestFactory::fromGlobals();
    }

    /**
     * La classe Response modélise une réponse HTTP.
     */
    public static function response(bool $shared = true): Response
    {
        if ($shared) {
            return static::sharedInstance('response');
        }

        return new Response();
    }

    /**
     * CacheResponse
     */
    public static function responsecache(?CacheInterface $cache = null, array|bool|null $cacheQueryString = null, bool $shared = true): ResponseCache
    {
        if ($shared) {
            return static::sharedInstance('responsecache', $cache, $cacheQueryString);
        }

        $cache ??= static::get('cache');
        $cacheQueryString ??= static::get('config')->get('cache.cache_query_string');

        return new ResponseCache($cache, /** @scrutinizer ignore-type */ $cacheQueryString);
    }

    /**
     * Le service Routes est une classe qui permet de construire facilement une collection de routes.
     *
     * @return RouteCollection
     */
    public static function routes(bool $shared = true): RouteCollectionInterface
    {
        if ($shared) {
            return static::sharedInstance('routes');
        }

        return new RouteCollection(static::get('locator'), (object) static::get('config')->get('routing'));
    }

    /**
     * La classe Router utilise le tableau de routes d'une RouteCollection et détermine
     * le contrôleur et la méthode corrects à exécuter.
     *
     * @return Router
     */
    public static function router(?RouteCollection $routes = null, ?ServerRequest $request = null, bool $shared = true): RouterInterface
    {
        if ($shared) {
            return static::sharedInstance('router', $routes, $request);
        }

        $routes ??= static::get('routes');
        $request ??= static::get('request');

        return new Router($routes, $request);
    }

    /**
     * Retourne le gestionnaire de session.
     *
     * @return Store
     */
    public static function session(bool $shared = true): SessionInterface
    {
        if ($shared) {
            return static::sharedInstance('session');
        }

        $config = static::get('config')->get('session');

        Cookie::setDefaults($cookies = /** @scrutinizer ignore-type */ static::get('config')->get('cookie'));
        $session = new Store((array) $config, (array) $cookies, Helpers::ipAddress());
        $session->setLogger(static::get('logger'));

        if (session_status() === PHP_SESSION_NONE) {
            $session->start();
        }

        return $session;
    }

    /**
     * System de gestion de fichier par disque
     */
    public static function storage(bool $shared = true): FilesystemManager
    {
        if ($shared) {
            return static::sharedInstance('storage');
        }

        return new FilesystemManager(/** @scrutinizer ignore-type */ static::get('config')->get('filesystems'));
    }

    /**
     * La classe Timer fournit un moyen simple d'évaluer des parties de votre application.
     */
    public static function timer(bool $shared = true): Timer
    {
        if ($shared) {
            return static::sharedInstance('timer');
        }

        return new Timer();
    }

    /**
     * Renvoie la barre d'outils de débogage.
     */
    public static function toolbar(?stdClass $config = null, bool $shared = true): Toolbar
    {
        if ($shared) {
            return static::sharedInstance('toolbar', $config);
        }

        $config ??= (object) static::get('config')->get('toolbar');

        return new Toolbar($config);
    }

    /**
     * Responsable du chargement des traductions des chaînes de langue.
     */
    public static function translator(?string $locale = null, bool $shared = true): Translate
    {
        if ($shared) {
            return static::sharedInstance('translator', $locale);
        }

        if (null === $locale || $locale === '' || $locale === '0') {
            $locale = is_cli() ? static::get('config')->get('app.locale') : static::get('request')->getLocale();
        }

        return new Translate($locale, static::get('locator'));
    }

    /**
     * La classe URI fournit un moyen de modéliser et de manipuler les URI.
     *
     * @return Uri
     */
    public static function uri(?string $uri = null, bool $shared = true): UriInterface
    {
        if ($shared) {
            return static::sharedInstance('uri', $uri);
        }

        return new Uri($uri);
    }

    /**
     * La classe Renderer est la classe qui affiche réellement un fichier à l'utilisateur.
     * La classe View par défaut dans BlitzPHP est intentionnellement simple, mais
     * le service peut facilement être remplacé par un moteur de modèle si l'utilisateur en a besoin.
     */
    public static function viewer(bool $shared = true): View
    {
        if ($shared) {
            return static::sharedInstance('viewer');
        }

        return new View();
    }
}
