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

use BlitzPHP\Container\AbstractProvider;

class Providers extends AbstractProvider
{
    public static function definitions(): array
    {
        return array_merge(
            self::interfaces(),
            self::classes(),
        );
    }

    /**
     * Enregistre les interfaces
     */
    private static function interfaces(): array
    {
        return [
            \BlitzPHP\Autoloader\LocatorInterface::class               => static fn () => service('locator'),
            \BlitzPHP\Contracts\Event\EventManagerInterface::class     => static fn () => service('event'),
            \BlitzPHP\Contracts\Router\RouteCollectionInterface::class => static fn () => service('routes'),
            \BlitzPHP\Contracts\Security\EncrypterInterface::class     => static fn () => service('encrypter'),
            \BlitzPHP\Contracts\Session\SessionInterface::class        => static fn () => service('session'),
            \BlitzPHP\Mail\MailerInterface::class                      => static fn () => service('mail'),
            \Psr\Container\ContainerInterface::class                   => static fn () => service('container'),
            \Psr\Http\Message\ResponseInterface::class                 => static fn () => service('response'),
            \Psr\Http\Message\ServerRequestInterface::class            => static fn () => service('request'),
            \Psr\Log\LoggerInterface::class                            => static fn () => service('logger'),
            \Psr\SimpleCache\CacheInterface::class                     => static fn () => service('cache'),
        ];
    }

    /**
     * Enregistre les classes concretes definies comme services
     */
    private static function classes(): array
    {
        return [
            \BlitzPHP\Autoloader\Autoloader::class        => static fn () => service('autoloader'),
            \BlitzPHP\Cache\Cache::class                  => static fn () => service('cache'),
            \BlitzPHP\Translator\Translate::class         => static fn () => service('translator'),
            \BlitzPHP\Autoloader\Locator::class           => static fn () => service('locator'),
            \BlitzPHP\Mail\Mail::class                    => static fn () => service('mail'),
            \BlitzPHP\Http\Negotiator::class              => static fn () => service('negotiator'),
            \BlitzPHP\Http\Redirection::class             => static fn () => service('redirection'),
            \BlitzPHP\Cache\ResponseCache::class          => static fn () => service('responsecache'),
            \BlitzPHP\Router\RouteCollection::class       => static fn () => service('routes'),
            \BlitzPHP\Router\Router::class                => static fn () => service('router'),
            \BlitzPHP\Session\Store::class                => static fn () => service('session'),
            \BlitzPHP\Filesystem\FilesystemManager::class => static fn () => service('storage'),
        ];
    }
}
