<?php 

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
            \BlitzPHP\Contracts\Event\EventManagerInterface::class     => fn () => service('event'),
            \BlitzPHP\Contracts\Router\RouteCollectionInterface::class => fn () => service('routes'),
            \BlitzPHP\Contracts\Session\SessionInterface::class        => fn () => service('session'),
            \BlitzPHP\Mail\MailerInterface::class                      => fn () => service('mail'),
            \Psr\Container\ContainerInterface::class                   => fn () => service('container'),
            \Psr\Http\Message\ResponseInterface::class                 => fn () => service('response'),
            \Psr\Http\Message\ServerRequestInterface::class            => fn () => service('request'),
            \Psr\Log\LoggerInterface::class                            => fn () => service('logger'),
            \Psr\SimpleCache\CacheInterface::class                     => fn () => service('cache'),
        ];
    }

    /** 
     * Enregistre les classes concretes definies comme services 
     */
    private static function classes(): array
    {
        return [
            \BlitzPHP\Autoloader\Autoloader::class        => fn () => service('autoloader'),
            \BlitzPHP\Cache\Cache::class                  => fn () => service('cache'),
            \BlitzPHP\Translator\Translate::class         => fn () => service('translator'),
            \BlitzPHP\Autoloader\Locator::class           => fn () => service('locator'),
            \BlitzPHP\Mail\Mail::class                    => fn () => service('mail'),
            \BlitzPHP\Http\Negotiator::class              => fn () => service('negotiator'),
            \BlitzPHP\Http\Redirection::class             => fn () => service('redirection'),
            \BlitzPHP\Cache\ResponseCache::class          => fn () => service('responsecache'),
            \BlitzPHP\Router\RouteCollection::class       => fn () => service('routes'),
            \BlitzPHP\Router\Router::class                => fn () => service('router'),
            \BlitzPHP\Session\Store::class                => fn () => service('session'),
            \BlitzPHP\Filesystem\FilesystemManager::class => fn () => service('storage'),
        ];
    }
}
