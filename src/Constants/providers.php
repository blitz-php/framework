<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

return [
    BlitzPHP\Contracts\Database\ConnectionInterface::class         => fn () => BlitzPHP\Db\Database::connect(),
    BlitzPHP\Contracts\Database\ConnectionResolverInterface::class => fn () => service(BlitzPHP\Db\ConnectionResolver::class),
    BlitzPHP\Contracts\Event\EventManagerInterface::class          => fn () => service('event'),
    BlitzPHP\Contracts\Router\RouteCollectionInterface::class      => fn () => service('routes'),
    BlitzPHP\Filesystem\FilesystemManager::class                   => fn () => service('storage'),
    Psr\Container\ContainerInterface::class                        => fn () => service('container'),
    Psr\Http\Message\ResponseInterface::class                      => fn () => service('response'),
    Psr\Http\Message\ServerRequestInterface::class                 => fn () => service('request'),
    Psr\Log\LoggerInterface::class                                 => fn () => service('logger'),
    Psr\SimpleCache\CacheInterface::class                          => fn () => service('cache'),
];
