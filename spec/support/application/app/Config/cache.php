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
    'handler'             => 'file',
    'fallback_handler'    => 'dummy',
    'cache_query_string'  => false,
    'prefix'              => '',
    'ttl'                 => 60,
    'reserved_characters' => '{}()/\@:',
    'file'                => ['path' => cache_path(), 'mode' => 0o640],
    'valid_handlers'      => ['file' => \BlitzPHP\Cache\Handlers\File::class],
];
