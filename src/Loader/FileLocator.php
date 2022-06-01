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

use BlitzPHP\Exceptions\LoadException;

class FileLocator
{
    /**
     * Charge un fichier de traduction
     */
    public static function lang(string $lang, string $locale): array
    {
        $file  = self::ensureExt($lang, 'php');
        $paths = [
            // Path to system languages
            SYST_PATH . 'Constants' . DS . 'language' . DS . config('app.language') . DS . $file,

            // Path to app languages
            LANG_PATH . config('app.language') . DS . $file,

            // Path to system languages
            SYST_PATH . 'Constants' . DS . 'language' . DS . $locale . DS . $file,

            // Path to app languages
            LANG_PATH . $locale . DS . $file,
        ];
        $file_exist = false;
        $languages  = [];

        foreach ($paths as $path) {
            if (file_exists($path) && ! in_array($path, get_included_files(), true)) {
                $languages  = array_merge($languages, (array) require($path));
                $file_exist = true;
            }
        }

        if (true !== $file_exist) {
            throw LoadException::langNotFound($lang);
        }

        return $languages;
    }

    /**
     * Charge un fichier d'aide (helper)
     *
     * @return void
     */
    public static function helper(string $helper)
    {
        $file  = self::ensureExt($helper, 'php');
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

        $file  = self::ensureExt(implode(DS, $library), 'php');
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
     * @return \dFramework\core\Model
     */
    public static function model(string $model)
    {
        $model = str_replace(DS, '/', $model);
        $model = explode('/', $model);

        $mod                      = ucfirst(end($model));
        $mod                      = (! preg_match('#Model$#', $mod)) ? $mod . 'Model' : $mod;
        $model[count($model) - 1] = $mod;

        foreach ($model as $key => &$value) {
            if (preg_match('#^Models?$#i', $value)) {
                unset($value, $model[$key]);
            }
        }

        $path = MODEL_PATH . self::ensureExt(implode(DS, $model), 'php');

        if (! file_exists($path)) {
            throw LoadException::modelNotFound($mod, $path);
        }

        require_once $path;

        $class_namespaced = implode('\\', $model);

        if (class_exists($class_namespaced, false)) {
            return Injector::make($class_namespaced);
        }
        if (! class_exists($mod, false)) {
            throw LoadException::modelDontExist($mod, $path);
        }

        return Injector::make($mod);
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

        $path = CONTROLLER_PATH . self::ensureExt(implode(DS, $controller), 'php');

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
     * Ensures a extension is at the end of a filename
     */
    private static function ensureExt(string $path, string $ext = 'php'): string
    {
        if ($ext) {
            $ext = '.' . $ext;

            if (substr($path, -strlen($ext)) !== $ext) {
                $path .= $ext;
            }
        }

        return trim($path);
    }
}
