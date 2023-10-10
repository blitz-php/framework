<?php

return [
    'handler'            => \BlitzPHP\Session\Handlers\File::class,
    'cookie_name'        => 'blitz_session',
    'expiration'         => 7200,
    'savePath'           => FRAMEWORK_STORAGE_PATH . 'session',
    'matchIP'            =>  false,
    'time_to_update'     => 300,
    'regenerate_destroy' => false,
    'group'              => null,
];
