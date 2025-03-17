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
    'route_files'                 => Expect::listOf('string')->default([CONFIG_PATH . 'routes.php']),
    'default_namespace'           => Expect::string(APP_NAMESPACE . '\Controllers'),
    'default_controller'          => Expect::string('HomeController'),
    'default_method'              => Expect::string('index'),
    'translate_uri_dashes'        => Expect::bool(false),
    'translate_uri_to_camel_case' => Expect::bool(false),
    'fallback'                    => Expect::mixed()->nullable()->default(null),
    'auto_route'                  => Expect::bool(false),
    'prioritize'                  => Expect::bool(false),
    'multiple_segments_one_param' => Expect::bool(false),
    'use_supported_locales_only'  => Expect::bool(false),
    'module_routes'               => Expect::array()->default([]),
])->otherItems();
