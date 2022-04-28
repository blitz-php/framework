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

use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Router\Router;
use DI\NotFoundException;

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
     * The Request class models an HTTP request.
     */
    public static function request(bool $shared = true): ServerRequest
    {
        if (true === $shared) {
            return self::singleton(ServerRequest::class);
        }

        return self::factory(ServerRequest::class);
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

        return self::factory(Router::class, [$routes, $request]);
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
    private static function singleton(string $name)
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
