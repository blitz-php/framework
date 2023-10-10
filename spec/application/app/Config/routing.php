<?php

return [
    'route_files'          => [CONFIG_PATH . 'routes.php'],
    'default_namespace'    => 'App\Controllers',
    'default_controller'   => 'HomeController',
    'default_method'       => 'index',
    'translate_uri_dashes' => false,
    'fallback'             => null,
    'auto_route'           => false,
    'prioritize'           => false,
    'module_routes'        => [],
];
