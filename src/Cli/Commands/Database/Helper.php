<?php 

namespace BlitzPHP\Cli\Commands\Database;

use BlitzPHP\Database\Migration\Runner;
use BlitzPHP\Loader\Services;
use InvalidArgumentException;

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
        $config = config('database');
        
        if (empty($group)) {
            $group = $config['group'] ?? 'auto';

            if ($group === 'auto') {
                $group = on_test() ? 'test' : (on_prod() ? 'production' : 'development');
            }

            if (! isset($config[$group])) {
                $group = 'default';
            }
        }
        
        if (is_string($group) && ! isset($config[$group]) && strpos($group, 'custom-') !== 0) {
            throw new InvalidArgumentException($group . ' is not a valid database connection group.');
        }

        return Runner::instance(config('migrations'), $config[$group]);
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