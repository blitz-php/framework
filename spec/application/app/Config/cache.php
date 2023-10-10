<?php

return [
    'handler'             => 'file',
    'fallback_handler'    => 'dummy',
    'cache_query_string'  => false,
    'prefix'              => '',
    'ttl'                 => 60,
    'reserved_characters' => '{}()/\@:',
    'file'                => ['path' => cache_path(), 'mode' => 0640],
    'valid_handlers'      => ['file' => \BlitzPHP\Cache\Handlers\File::class],
];
