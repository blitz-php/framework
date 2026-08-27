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
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\Cache\CacheInterface;
use BlitzPHP\Contracts\Cache\RepositoryInterface;
use BlitzPHP\Contracts\Container\ContainerInterface;
use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Contracts\Mail\MailerInterface;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Contracts\Security\EncrypterInterface;
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
use BlitzPHP\Session\Cookie\CookieManager;
use BlitzPHP\Session\Store;
use BlitzPHP\Translator\Translate;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

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
			CacheInterface::class           => static fn () => service('cache'),
			PsrCacheInterface::class        => static fn () => service('cache'),
			ContainerInterface::class       => static fn () => service('container'),
			PsrContainerInterface::class    => static fn () => service('container'),
			CookieManagerInterface::class   => static fn () => service('cookie'),
			EncrypterInterface::class       => static fn () => service('encrypter'),
			EventManagerInterface::class    => static fn () => service('event'),
			LocatorInterface::class         => static fn () => service('locator'),
			LoggerInterface::class          => static fn () => service('logger'),
			MailerInterface::class          => static fn () => service('mail'),
			RendererInterface::class        => static fn () => service('viewer')->getAdapter(),
			RepositoryInterface::class      => static fn () => service('cache'),
			ResponseInterface::class        => static fn () => service('response'),
			RouteCollectionInterface::class => static fn () => service('routes'),
			ServerRequestInterface::class   => static fn () => service('request'),
			SessionInterface::class         => static fn () => service('session'),
        ];
    }

    /**
     * Enregistre les classes concretes definies comme services
     */
    private static function classes(): array
    {
        return [
            Autoloader::class        => static fn () => service('autoloader'),
            Locator::class           => static fn () => service('locator'),
            Cache::class             => static fn () => service('cache'),
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
}
