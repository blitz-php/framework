<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Loader;

use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Exceptions\LoadException;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\String\Text;

class FileLocator
{
    /**
     * Charge un fichier d'aide (helper)
     *
     * @return void
     */
    public static function helper(string $helper)
    {
        $file  = Helpers::ensureExt($helper, 'php');
        $paths = [
            // Helpers système
            SYST_PATH . 'Helpers' . DS . $file,

            // Helpers de l'application
            APP_PATH . 'Helpers' . DS . $file,
        ];
        $file_exist = false;

        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $file_exist = true;
            }
        }

        if (true !== $file_exist) {
            throw LoadException::helperNotFound($helper);
        }
    }

    /**
     * Cree et renvoie une librairie donnée
     *
     * @return mixed
     */
    public static function library(string $library)
    {
        $library = str_replace(DS, '/', $library);
        $library = explode('/', $library);

        $lib                          = ucfirst(end($library));
        $library[count($library) - 1] = $lib;

        $file  = Helpers::ensureExt(implode(DS, $library), 'php');
        $paths = [
            SYST_PATH . 'Libraries' . DS . $file,

            APP_PATH . 'Libraries' . DS . $file,
        ];
        $file_syst = $file_exist = false;

        if (file_exists($paths[0])) {
            $lib       = "BlitzPhp\\Libraries\\{$lib}";
            $file_syst = $file_exist = true;
        } elseif (file_exists($paths[1])) {
            require_once $paths[1];
            $file_exist = true;
        }

        if (true !== $file_exist) {
            throw LoadException::libraryNotFound($lib);
        }

        if (true !== $file_syst && ! class_exists($lib)) {
            throw LoadException::libraryDontExist($lib);
        }

        return Injector::make($lib);
    }

    /**
     * Cree et renvoi un model donné
     *
     * @template T of \BlitzPHP\Models\BaseModel
     *
     * @param class-string<T> $model
     *
     * @return T
     */
    public static function model(string $model, ?ConnectionInterface $connection = null)
    {
        if (! class_exists($model) && ! Text::endsWith($model, 'Model')) {
            $model .= 'Model';
        }

        if (! class_exists($model)) {
            $model = str_replace(APP_NAMESPACE . '\\Models\\', '', $model);
            $model = APP_NAMESPACE . '\\Models\\' . $model;
        }

        if (! class_exists($model)) {
            throw LoadException::modelNotFound($model);
        }

        return Injector::make($model, ['db' => $connection]);
    }

    /**
     * Cree et renvoi un controleur donné
     *
     * @return \dFramework\core\controllers\BaseController
     */
    public static function controller(string $controller)
    {
        $controller = str_replace(DS, '/', $controller);
        $controller = explode('/', $controller);

        $con                                = ucfirst(end($controller));
        $con                                = (! preg_match('#Controller$#', $con)) ? $con . 'Controller' : $con;
        $controller[count($controller) - 1] = $con;

        foreach ($controller as $key => &$value) {
            if (preg_match('#^Controllers?$#i', $value)) {
                unset($value, $controller[$key]);
            }
        }

        $path = CONTROLLER_PATH . Helpers::ensureExt(implode(DS, $controller), 'php');

        if (! file_exists($path)) {
            throw LoadException::controllerNotFound(str_replace('Controller', '', $con), $path);
        }

        require_once $path;

        $class_namespaced = implode('\\', $controller);

        if (class_exists($class_namespaced, false)) {
            return Injector::make($class_namespaced);
        }
        if (! class_exists($con, false)) {
            throw LoadException::controllerDontExist(str_replace('Controller', '', $con), $path);
        }

        return Injector::make($con);
    }

    /**
     * Recupere le nom de base a partir du nom de la classe, namespacé ou non.
     */
    public static function getBasename(string $name): string
    {
        // Determine le basename
        if ($basename = strrchr($name, '\\')) {
            return substr($basename, 1);
        }

        return $name;
    }

    /**
     * Verifie si la classe satisfait l'option "preferApp"
     *
     * @param array  $options directives specifier pqr le composant
     * @param string $name    Nom de la classe, namespace optionel
     */
    protected static function verifyPreferApp(array $options, string $name): bool
    {
        // Tout element sans restriction passe
        if (! $options['preferApp']) {
            return true;
        }

        return strpos($name, APP_NAMESPACE) === 0;
    }
}
