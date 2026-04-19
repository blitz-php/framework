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
use BlitzPHP\Cache\Cache;
use BlitzPHP\Cache\ResponseCache;
use BlitzPHP\Container\AbstractProvider;
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\Container\ContainerInterface;
use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Contracts\Mail\MailerInterface;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Contracts\Security\HasherInterface;
use BlitzPHP\Contracts\Session\CookieManagerInterface;
use BlitzPHP\Contracts\Session\SessionInterface;
use BlitzPHP\Contracts\View\RendererInterface;
use BlitzPHP\Filesystem\FilesystemManager;
use BlitzPHP\Http\Negotiator;
use BlitzPHP\Http\Redirection;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Mail\Mail;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Router\Router;
use BlitzPHP\Security\Encryption\Encryption;
use BlitzPHP\Security\Hashing\Hasher;
use BlitzPHP\Session\Cookie\CookieManager;
use BlitzPHP\Session\Store;
use BlitzPHP\Translator\Translate;
use BlitzPHP\Utilities\Reflection\ReflectionClass;
use Closure;
use Dimtrovich\UserAgent\Extensions\BlitzPHP\AgentProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;

/**
 * Fournisseur de services principal pour le framework.
 *
 * Enregistre les bindings pour interfaces et classes concrètes.
 * Supporte lazy loading et boot post-resolution.
 */
class Providers extends AbstractProvider
{
    /**
     * Définitions des bindings.
     *
     * @return array<string, Closure|mixed> Les bindings pour le container.
     */
    public static function definitions(): array
    {
        return array_merge(
            self::interfaces(),
            self::classes(),
            self::services(),
			AgentProvider::definitions(),
        );
    }

    /**
     * Enregistre les interfaces avec leurs implémentations.
     *
     * @return array<string, Closure>
     */
    private static function interfaces(): array
    {
        return [
            LocatorInterface::class                  => static fn () => service('locator'),
            ContainerInterface::class                => static fn () => service('container'),
            EventManagerInterface::class             => static fn () => service('event'),
            MailerInterface::class                   => static fn () => service('mail'),
            RouteCollectionInterface::class          => static fn () => service('routes'),
            EncrypterInterface::class                => static fn () => service('encrypter'),
            HasherInterface::class                   => static fn () => service('hashing'),
			CookieManagerInterface::class            => static fn () => service('cookie'),
            SessionInterface::class                  => static fn () => service('session'),
            RendererInterface::class                 => static fn () => service('viewer')->getAdapter(),
            \Psr\Container\ContainerInterface::class => static fn () => service('container'),
            ResponseInterface::class                 => static fn () => service('response'),
            ServerRequestInterface::class            => static fn () => service('request'),
            LoggerInterface::class                   => static fn () => service('logger'),
            CacheInterface::class                    => static fn () => service('cache'),
        ];
    }

    /**
     * Enregistre les classes concrètes comme services.
     *
     * @return array<string, Closure>
     */
    private static function classes(): array
    {
        return [
            Autoloader::class        => static fn () => service('autoloader'),
            Locator::class           => static fn () => service('locator'),
            Cache::class             => static fn () => service('cache'),
            Encryption::class        => static fn () => service('encrypter'),
			Hasher::class            => static fn () => service('hashing'),
			ResponseCache::class     => static fn () => service('responsecache'),
            FilesystemManager::class => static fn () => service('storage'),
            Negotiator::class        => static fn () => service('negotiator'),
            Redirection::class       => static fn () => service('redirection'),
            Request::class           => static fn () => service('request'),
            Response::class          => static fn () => service('response'),
            Mail::class              => static fn () => service('mail'),
            RouteCollection::class   => static fn () => service('routes'),
            Router::class            => static fn () => service('router'),
            CookieManager::class     => static fn () => service('cookie'),
            Store::class             => static fn () => service('session'),
            Translate::class         => static fn () => service('translator'),
        ];
    }

    /**
     * Enregistre les services.
     *
     * @return array<string, Closure>
     */
    private static function services(): array
    {
        $services   = [];
        $reflection = new ReflectionClass(Services::class);

        $internal = [
            'get', 'set', 'override',
            'singleton', 'factory',
            'injectMock', 'reset', 'resetSingle',
            'serviceExists', 'getRegistryServices', '__callStatic',
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC) as $method) {
            if (! in_array($method->getName(), $internal, true)) {
                $services[$method->getName()] = static fn () => service($method->getName());
            }
        }

        return $services;
    }
}
