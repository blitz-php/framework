<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace dFramework\core\loader;

use dFramework\core\exception\LoadException;

/**
 * FileLocator
 *
 * @category    Loader
 *
 * @see		https://dimtrov.hebfree.org/docs/dframework
 * @since       3.2.1
 * @file		/system/core/loader/FileLocator.php
 */
class FileLocator
{
    public static function lang(string $lang, string $locale): array
    {
        $file  = self::ensureExt($lang, 'json');
        $paths = [
            // Path to system languages
            SYST_DIR . 'constants' . DS . 'lang' . DS . config('general.language') . DS . $file,

            // Path to app languages
            LANG_DIR . config('general.language') . DS . $file,

            // Path to system languages
            SYST_DIR . 'constants' . DS . 'lang' . DS . $locale . DS . $file,

            // Path to app languages
            LANG_DIR . $locale . DS . $file,
        ];
        $file_exist = false;
        $languages  = [];

        foreach ($paths as $path) {
            if (file_exists($path) && false !== ($lang = file_get_contents($path))) {
                $languages  = array_merge($languages, json_decode($lang, true));
                $file_exist = true;
            }
        }

        if (true !== $file_exist) {
            LoadException::except('
                Impossible de charger les langues  <b>' . $lang . '</b>.
                <br>
                Aucun fichier accessible en lecture et correspondant à cette langue n\'a été trouvé.
            ');
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
            // Path to system helpers
            SYST_DIR . 'helpers' . DS . $file,

            // Path to app helpers
            APP_DIR . 'helpers' . DS . $file,
        ];
        $file_exist = false;

        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $file_exist = true;
            }
        }

        if (true !== $file_exist) {
            LoadException::except('
                Impossible de charger les fonctions d\'aide <b>' . $helper . '</b>.
                <br>
                Aucun fichier accessible en lecture et correspondant à ce helper n\'a été trouvé.
            ');
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
            // Path to system helpers
            SYST_DIR . 'libraries' . DS . $file,

            // Path to app helpers
            APP_DIR . 'libraries' . DS . $file,
        ];
        $file_syst = $file_exist = false;

        if (file_exists($paths[0])) {
            $lib       = "dFramework\\libraries\\{$lib}";
            $file_syst = $file_exist = true;
        } elseif (file_exists($paths[1])) {
            require_once $paths[1];
            $file_exist = true;
        }

        if (true !== $file_exist) {
            LoadException::except(
                'Library file not found',
                'Impossible de charger la librairie <b>' . $lib . '</b>.
                <br/>
                Aucun fichier accessible en lecture n\'a été trouvé pour cette librairie'
            );
        }

        if (true !== $file_syst && ! class_exists($lib)) {
            LoadException::except(
                'Library class do not exist',
                'Impossible de charger la librarie <b>' . $lib . '</b>.
                <br>
                Le fichier correspondant à cette librairie ne contient pas de classe <b>' . $lib . '</b>'
            );
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

        $path = MODEL_DIR . self::ensureExt(implode(DS, $model), 'php');

        if (! file_exists($path)) {
            LoadException::except(
                'Model file not found',
                'Impossible de charger le modele <b>' . str_replace('Model', '', $mod) . '</b> souhaité.
                <br/>
                Le fichier &laquo; ' . $path . ' &raquo; n\'existe pas'
            );
        }

        require_once $path;

        $class_namespaced = implode('\\', $model);

        if (class_exists($class_namespaced, false)) {
            return Injector::make($class_namespaced);
        }
        if (! class_exists($mod, false)) {
            LoadException::except(
                'Model class do not exist',
                'Impossible de charger le model <b>' . str_replace('Model', '', $mod) . '</b> souhaité.
                <br/>
                Le fichier &laquo; ' . $path . ' &raquo; ne contient pas de classe <b>' . $mod . '</br>
            '
            );
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

        $path = CONTROLLER_DIR . self::ensureExt(implode(DS, $controller), 'php');

        if (! file_exists($path)) {
            LoadException::except(
                'Controller file not found',
                'Impossible de charger le controleur <b>' . str_replace('Controller', '', $con) . '</b> souhaité.
                <br/>
                Le fichier &laquo; ' . $path . ' &raquo; n\'existe pas'
            );
        }

        require_once $path;

        $class_namespaced = implode('\\', $controller);

        if (class_exists($class_namespaced, false)) {
            return Injector::make($class_namespaced);
        }
        if (! class_exists($con, false)) {
            LoadException::except(
                'Controller class do not exist',
                'Impossible de charger le controleur <b>' . str_replace('Controller', '', $con) . '</br> souhaité.
                <br>
                Le fichier &laquo; ' . $path . ' &raquo; ne contient pas de classe <b>' . $con . '</b>'
            );
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
