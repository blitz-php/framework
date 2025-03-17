<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Nette\Schema\Expect;

return Expect::structure([
    'handler'             => Expect::string(env('cache.handler', 'file')),
    'fallback_handler'    => Expect::string('dummy'),
    'cache_query_string'  => Expect::type('bool|array')->default(false),
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
        'apcu'      => \BlitzPHP\Cache\Handlers\Apcu::class,
        'array'     => \BlitzPHP\Cache\Handlers\ArrayHandler::class,
        'dummy'     => \BlitzPHP\Cache\Handlers\Dummy::class,
        'file'      => \BlitzPHP\Cache\Handlers\File::class,
        'memcached' => \BlitzPHP\Cache\Handlers\Memcached::class,
        'redis'     => \BlitzPHP\Cache\Handlers\RedisHandler::class,
        'wincache'  => \BlitzPHP\Cache\Handlers\Wincache::class,
    ]),
]);
