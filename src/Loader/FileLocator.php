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

class FileLocator
{
    /**
     * Charge un fichier de traduction
     */
    public static function lang(string $lang, string $locale): array
    {
        $languages  = [];
        $file_exist = false;

        $path = self::findLangFile($lang, $locale, 'json');
        if (null !== $path && false !== ($lang = file_get_contents($path))) {
            $file_exist = true;
            $languages  = array_merge($languages, json_decode($lang, true));
        }

        $path = self::findLangFile($lang, $locale, 'php');
        if (null !== $path) {
            $file_exist = true;
            if (! in_array($path, get_included_files(), true)) {
                $languages = array_merge($languages, require($path));
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
    public static function model(string $model, array $options = [], ?ConnectionInterface $connection = null)
    {
        $options = array_merge([
            'preferApp' => true,
        ], $options);

        if (! preg_match('#Model$#', $model)) {
            $model .= 'Model';
        }

        if ($options['preferApp'] === true) {
            // $model = self::getBasename($model);

            $model = str_replace(APP_NAMESPACE . '\\Models\\', '', $model);
            $model = APP_NAMESPACE . '\\Models\\' . $model;
        }

        if (! class_exists($model)) {
            throw LoadException::modelNotFound($model);
        }

        return Injector::make($model, [$connection]);
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

    /**
     * Trouve le premier chemin correspondant a une locale
     */
    private static function findLangFile(string $lang, string $locale, string $ext): ?string
    {
        $file  = Helpers::ensureExt($lang, $ext);
        $paths = [
            // Chemin d'accès aux langues de l'application
            LANG_PATH . $locale . DS . $file,

            // Chemin d'accès aux langues de l'application
            LANG_PATH . config('app.language') . DS . $file,

            // Chemin vers les langues du système
            SYST_PATH . 'Constants' . DS . 'language' . DS . $locale . DS . $file,

            // Chemin vers les langues du système
            SYST_PATH . 'Constants' . DS . 'language' . DS . config('app.language') . DS . $file,
        ];
        $paths = array_unique($paths);

        foreach ($paths as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
