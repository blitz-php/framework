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
