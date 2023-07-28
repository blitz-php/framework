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

use BlitzPHP\Container\Injector;
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Exceptions\LoadException;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\String\Text;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class FileLocator
{
    /**
     * Charge un fichier d'aide en mémoire.
     * Prend en charge les helpers d'espace de noms, à la fois dans et hors du répertoire 'helpers' d'un répertoire d'espace de noms.
     *
     * Chargera TOUS les helpers du nom correspondant, dans l'ordre suivant :
     *   1. app/Helpers
     *   2. {namespace}/Helpers
     *   3. system/Helpers
     *
     * @throws FileNotFoundException
     */
    public static function helper(array|string $filenames)
    {
        static $loaded = [];

        $loader = Services::locator();

        if (! is_array($filenames)) {
            $filenames = [$filenames];
        }

        // Enregistrez une liste de tous les fichiers à inclure...
        $includes = [];

        foreach ($filenames as $filename) {
            // Stockez nos versions d'helpers système et d'application afin que nous puissions contrôler l'ordre de chargement.
            $systemHelper  = null;
            $appHelper     = null;
            $localIncludes = [];

            // Vérifiez si ce helper a déjà été chargé
            if (in_array($filename, $loaded, true)) {
                continue;
            }

            // Si le fichier est dans un espace de noms, nous allons simplement saisir ce fichier et ne pas en rechercher d'autres
            if (strpos($filename, '\\') !== false) {
                $path = $loader->locateFile($filename, 'Helpers');

                if (empty($path)) {
                    throw LoadException::helperNotFound($filename);
                }

                $includes[] = $path;
                $loaded[]   = $filename;
            } else {
                // Pas d'espaces de noms, donc recherchez dans tous les emplacements disponibles
                $paths = $loader->search('Helpers/' . $filename);

                foreach ($paths as $path) {
                    if (strpos($path, APP_PATH . 'Helpers' . DS) === 0) {
                        $appHelper = $path;
                    } elseif (strpos($path, SYST_PATH . 'Helpers' . DS) === 0) {
                        $systemHelper = $path;
                    } else {
                        $localIncludes[] = $path;
                        $loaded[]        = $filename;
                    }
                }

                // Les helpers au niveau de l'application doivent remplacer tous les autres
                if (! empty($appHelper)) {
                    $includes[] = $appHelper;
                    $loaded[]   = $filename;
                }

                // Tous les fichiers avec espace de noms sont ajoutés ensuite
                $includes = [...$includes, ...$localIncludes];

                // Et celui par défaut du système doit être ajouté en dernier.
                if (! empty($systemHelper)) {
                    $includes[] = $systemHelper;
                    $loaded[]   = $filename;
                }
            }
        }

        // Incluez maintenant tous les fichiers
        foreach ($includes as $path) {
            include_once $path;
        }
    }

	/**
     * Charge un fichier d'aide en mémoire.
     * Prend en charge les helpers d'espace de noms, à la fois dans et hors du répertoire 'helpers' d'un répertoire d'espace de noms.
     */
    public static function schema(string $name): Schema
    {
        static $loadedSchema = [];

        $loader = Services::locator();

		// Stockez nos versions de schame système et d'application afin que nous puissions contrôler l'ordre de chargement.
		$systemSchema  = null;
		$appSchema     = null;
		$vendorSchema  = null;
		
		// Le fichier de schema qui sera finalement utiliser
		$file = null;
		
		// Vérifiez si ce schama a déjà été chargé
		if (in_array($name, $loadedSchema, true)) {
            return $loadedSchema[$name];
		}

		// Si le fichier est dans un espace de noms, nous allons simplement saisir ce fichier et ne pas en rechercher d'autres
		if (strpos($name, '\\') !== false) {
			if (!empty($path = $loader->locateFile($name, 'schemas'))) {
				$file = $path;
			}
		} else {
			// Pas d'espaces de noms, donc recherchez dans tous les emplacements disponibles
			$paths = $loader->search('schemas/' . $name);

			foreach ($paths as $path) {
				if (strpos($path, CONFIG_PATH . 'schemas' . DS) === 0) {
					$appSchema = $path;
				} elseif (strpos($path, SYST_PATH . 'Constants' . DS . 'schemas' . DS) === 0) {
					$systemSchema = $path;
				} else {
					$vendorSchema = $path;
				}
			}

			// Les schema des vendor sont prioritaire, ensuite vienne ceux de l'application
			if (!empty($vendorSchema)) {
				$file = $vendorSchema;
			} else if (!empty($appSchema)) {
				$file = $appSchema;
			} else if (!empty($systemSchema)) {
				$file = $systemSchema;
			}
        }

		if (!empty($file)) {
			$schema = require($file);
		} else {
			$schema = null;
		}

        if (empty($schema) || ! ($schema instanceof Schema)) {
            $schema = Expect::mixed();
        }

        return $loadedSchema[$name] = $schema;
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
