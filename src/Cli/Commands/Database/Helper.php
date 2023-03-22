<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Database;

use BlitzPHP\Config\Database;
use BlitzPHP\Database\Migration\Runner;
use BlitzPHP\Loader\Services;

/**
 * Aide a l'initialisation de la bd
 */
class Helper
{
    /**
     * Recupere une instance de l'executeur de migration
     */
    public static function runner(?string $group): Runner
    {
        [$group, $config] = Database::connectionInfo($group);

        return Runner::instance(config('migrations'), $config);
    }

    /**
     * Recupere les fichiers de migrations dans les namespaces
     */
    public static function getMigrationFiles(bool $all, ?string $namespace = null): array
    {
        if ($all) {
            $namespaces = array_keys(Services::autoloader()->getNamespace());
        } elseif ($namespace) {
            $namespaces = [$namespace];
        } else {
            $namespaces = [APP_NAMESPACE];
        }

        $locator = Services::locator();

        $files = [];

        foreach ($namespaces as $namespace) {
            $files[$namespace] = $locator->listNamespaceFiles($namespace, '/Database/Migrations/');
        }

        return $files;
    }
}
