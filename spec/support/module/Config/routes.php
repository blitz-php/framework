<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */


// This is a simple file to include for testing the RouteCollection class.
$routes->add('testing', 'TestController::index', ['as' => 'testing-index']);
$routes->get('closure', static fn () => 'closure test');
$routes->get('/', 'Blog::index', ['hostname' => 'blog.example.com']);
$routes->get('/', 'Sub::index', ['subdomain' => 'sub']);
$routes->get('/all', 'AllDomain::index', ['subdomain' => '*']);
