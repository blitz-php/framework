<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use BlitzPHP\Cache\Handlers\Apcu;
use BlitzPHP\Cache\Handlers\ArrayHandler;
use BlitzPHP\Cache\Handlers\Dummy;
use BlitzPHP\Cache\Handlers\File;
use BlitzPHP\Cache\Handlers\Memcached;
use BlitzPHP\Cache\Handlers\RedisHandler;
use BlitzPHP\Cache\Handlers\Wincache;
use Nette\Schema\Expect;

return Expect::structure([
    'handler'             => Expect::string(env('cache.handler', 'file')),
    'fallback_handler'    => Expect::string('dummy'),
    'cache_query_string'  => Expect::type('bool|array')->default(false),
    'cache_status_codes'  => Expect::listOf('int')->default([]),
    'prefix'              => Expect::string(env('cache.prefix', config('app.name', 'blitz_app') . '_cache_')),
    'ttl'                 => Expect::int(env('cache.duration', MINUTE)),
    'reserved_characters' => Expect::string('{}()/\@:'),

    'file' => Expect::structure([
        'path' => Expect::string(cache_path()),
        'mode' => Expect::int(0640),
    ]),

    'memcached' => Expect::structure([
        'host' => Expect::string('127.0.0.1'),
        'port' => Expect::int(11211),
    ]),

    'redis' => Expect::structure([
        'host'     => Expect::string('127.0.0.1'),
        'password' => Expect::bool(false),
        'port'     => Expect::int(6379),
        'timeout'  => Expect::int(0),
        'database' => Expect::int(0),
    ]),

    'valid_handlers' => Expect::arrayOf('string', 'string')->default([
        'apcu'      => Apcu::class,
        'array'     => ArrayHandler::class,
        'dummy'     => Dummy::class,
        'file'      => File::class,
        'memcached' => Memcached::class,
        'redis'     => RedisHandler::class,
        'wincache'  => Wincache::class,
    ]),
]);
