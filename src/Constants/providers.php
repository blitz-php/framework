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
    BlitzPHP\Contracts\Event\EventManagerInterface::class     => service('event'),
    BlitzPHP\Contracts\Router\RouteCollectionInterface::class => service('routes'),
    Psr\Container\ContainerInterface::class                   => service('container'),
    Psr\Http\Message\ResponseInterface::class                 => service('response'),
    Psr\Http\Message\ServerRequestInterface::class            => service('request'),
    Psr\Log\LoggerInterface::class                            => service('logger'),
    Psr\SimpleCache\CacheInterface::class                     => service('cache'),
    
    BlitzPHP\Filesystem\FilesystemManager::class => service('storage'),
];
