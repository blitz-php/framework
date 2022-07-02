<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug\Toolbar\Collectors;

use BlitzPHP\Core\Application;
use BlitzPHP\Loader\Services;

/**
 * Configuration de la barre d'outils de débogage
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\Config</a>
 */
class Config
{
    /**
     * Renvoie les valeurs de configuration de la barre d'outils sous forme de tableau.
     */
    public static function display(): array
    {
        $config = (object) config('app');

        return [
            'blitzVersion'  => Application::VERSION,
            'serverVersion' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'phpVersion'    => PHP_VERSION,
            'os'            => PHP_OS_FAMILY,
            'phpSAPI'       => PHP_SAPI,
            'environment'   => $config->environment ?? 'dev',
            'baseURL'       => $config->base_url ?? '',
            'documentRoot'  => $_SERVER['DOCUMENT_ROOT'] ?? WEBROOT,
            'timezone'      => $config->timezone,
            'locale'        => Services::request()->getLocale(),
        ];
    }
}
