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

use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Exceptions\LoadException;
use BlitzPHP\Exceptions\ViewException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

/**
 * Localisateur de fichiers pour ressources (helpers, schema, config, views, translations).
 *
 * Utilise Locator service, avec préférence app/system.
 */
class FileLocator
{
    /**
     * Cache locates.
     *
     * @var array<string, mixed>
     */
    private static array $locateCache = [];

	protected static $APP_PATH    = APP_PATH;
	protected static $CONFIG_PATH = CONFIG_PATH;
	protected static $SYST_PATH   = SYST_PATH;

    /**
     * Charge des helpers.
     * Prend en charge les helpers de namespace, à la fois dans et hors du répertoire 'helpers' d'un répertoire de namespace.
     *
     * Chargera TOUS les helpers du nom correspondant, dans l'ordre suivant :
     *   1. app/Helpers
     *   2. {namespace}/Helpers
     *   3. system/Helpers
     *
     * @param list<string>|string $filenames Noms des helpers
	 *
     * @throws LoadException
     */
    public static function helper(array|string $filenames): void
    {
        static $loadedHelpers = [];

        /** @var LocatorInterface */
        $locator = service('locator');
        $filenames = (array) $filenames;

        foreach ($filenames as $filename) {
            if (isset($loadedHelpers[$filename])) {
				continue; // Déjà chargé
			}

            if ([] === $files = self::locateHelper($filename, $locator)) {
				throw LoadException::helperNotFound($filename);
            }

            foreach ($files as $file) {
				include_once $file;
			}

			$loadedHelpers[$filename] = true;
        }
    }

    /**
     * Charge un schema de validation, en sélectionnant le premier disponible selon la priorité stricte.
     * Ordre de priorité (système prioritaire pour protéger les contraintes internes du framework) :
     *   1. system/Constants/schemas (base, non surchargeable pour la sécurité)
     *   2. {namespace}/schemas (modules, extensions optionnelles)
     *   3. app/schemas (override limité, mais système reste dominant pour éviter compromettre les validations internes)
     *
     * Retourne le Schema du premier fichier trouvé (priorité système). Les schemas système définissent les contraintes critiques ;
     * les overrides app/modules ne peuvent pas affaiblir ou casser ces validations pour préserver le fonctionnement du framework.
     * Si aucun trouvé, retourne un Schema vide (Expect::mixed()).
     *
     * @param string $name Le nom du schema (ex. : 'database' pour schemas/database.php)
     * @return Schema Le Schema Nette chargé
     */
    public static function schema(string $name): Schema
    {
        $cacheKey = "schema:{$name}";
        if (isset(self::$locateCache[$cacheKey])) {
            return self::$locateCache[$cacheKey];  // Retourne directement si déjà chargé
        }

        /** @var LocatorInterface */
        $locator = service('locator');

        $folder      = 'schemas';
        $directories = [
            'app'    => static::$CONFIG_PATH . $folder . DS,
            'system' => static::$SYST_PATH . 'Constants' . DS . $folder . DS,
        ];

        $files = self::locateFiles($name, $folder, $locator, $directories);

        if ($files === []) {
            return self::$locateCache[$cacheKey] = Expect::mixed();
        }

        // Ordre strict : system (dominant) > modules > app (override, mais system premier pour priorisation)
        // Puisque array_shift prend le premier, system est sélectionné en priorité absolue.
        // Cela empêche les overrides app de compromettre les contraintes système.
        if (isset($files['system']) && $files['system'] !== []) {
            // Si system existe, on le prend directement (priorité absolue)
            $file = array_shift($files['system']);
        } elseif (isset($files['modules']) && $files['modules'] !== []) {
            $file = array_shift($files['modules']);
        } elseif (isset($files['app']) && $files['app'] !== []) {
            $file = array_shift($files['app']);
        } else {
            $file = array_shift($files);
        }

        $schema = $file ? require $file : null;

        return self::$locateCache[$cacheKey] = ($schema && $schema instanceof Schema) ? $schema : Expect::mixed();
    }

    /**
     * Charge une configuration, en fusionnant toutes les versions trouvées par priorité.
     * Ordre de fusion (priorité croissante : système en base, puis modules, puis app en override final) :
     *   1. system/Config
     *   2. {namespace}/Config (si namespacé)
     *   3. app/Config
     *
     * Les fichiers retournent des arrays PHP, fusionnés récursivement.
     *
     * @param string $name Le nom de la config (ex. : 'database' pour Config/database.php)
	 *
     * @return array<string, mixed>
     */
    public static function config(string $name): array
    {
        $cacheKey = "config:{$name}";
        if (isset(self::$locateCache[$cacheKey])) {
            return self::$locateCache[$cacheKey];
        }

        /** @var LocatorInterface */
        $locator = service('locator');

        $files = self::locateFiles($name, 'Config', $locator);

        if (isset($files['app'])) {
            // Ordre de fusion : system (base) > modules > app (override)
            $files = array_merge($files['system'], $files['modules'], $files['app']);
        }

        if ($files === []) {
            return self::$locateCache[$cacheKey] = [];
        }

        $config = [];
        foreach ($files as $file) {
            $partial = require $file;
            if (is_array($partial)) {
                $config = array_merge_recursive($config, $partial);
            }
        }

        return self::$locateCache[$cacheKey] = $config;
    }

    /**
     * Charge une vue, en sélectionnant la première disponible selon la priorité.
     * Ordre de priorité (app en premier pour les overrides) :
     *   1. app/Views
     *   2. {namespace}/Views
     *   3. system/Views
     *
     * Retourne le chemin vers le fichier de vue, ou false si non trouvé.
     * (Le rendu effectif peut être géré ailleurs, ex. via un ViewRenderer.)
     *
     * @param string $name Le nom de la vue (ex. : 'home' pour Views/home.php, ou namespacé 'Admin::home')
     * @return string|false Le chemin du fichier de vue
	 *
     * @throws ViewException Si aucune vue trouvée
     */
    public static function view(string $name): string|false
    {
        $cacheKey = "view:{$name}";
        if (isset(self::$locateCache[$cacheKey])) {
            return self::$locateCache[$cacheKey];
        }

        /** @var LocatorInterface */
        $locator = service('locator');

        $files = self::locateFiles($name, 'Views', $locator);

		if (isset($files['app'])) {
			// Ordre : app > modules > system
			$files = array_merge($files['app'], $files['modules'], $files['system']);
		}

		$file = array_shift($files);

        if ($file === null) {
            throw ViewException::invalidFile($name);
        }

        return self::$locateCache[$cacheKey] = $file;
    }

    /**
     * Charge des traductions, en fusionnant toutes les versions trouvées par priorité.
     * Ordre de fusion (priorité croissante : système en base, puis modules, puis app en override final) :
     *   1. system/Translations/{locale}
     *   2. {namespace}/Translations/{locale} (si namespacé)
     *   3. app/Translations/{locale}
     *
     * Les fichiers retournent des arrays PHP (clés = phrases, valeurs = traductions).
     * Fusion récursif pour overrides (ex. : app peut override une clé système).
     * Le locale est optionnel (défaut : 'en' ou config('app.locale')).
     *
     * @param string $name Le nom du fichier de traduction (ex. : 'validation' pour Translations/en/validation.php)
     * @param string|null $locale Le code de langue (ex. : 'fr', 'en'). Optionnel.
	 *
     * @return array<string, string>
     */
    public static function translation(string $name, ?string $locale = null): array
    {
        $locale   = $locale ?? config('app.locale', 'en');
        $cacheKey = "translation:{$name}:{$locale}";

        if (isset(self::$locateCache[$cacheKey])) {
            return self::$locateCache[$cacheKey];
        }

        /** @var LocatorInterface */
        $locator = service('locator');

        $files = self::locateFiles($name, 'Translations' . DS . $locale, $locator);

        if ($files === []) {
            return self::$locateCache[$cacheKey] = [];
        }

        if (isset($files['app'])) {
            // Ordre de fusion : system (base) > modules > app (override)
            $files = array_merge($files['system'], $files['modules'], $files['app']);
        }

        if ($files === []) {
            return self::$locateCache[$cacheKey] = [];
        }

        $translations = [];
        foreach ($files as $file) {
            $partial = require $file;
            if (is_array($partial)) {
                $translations = array_merge_recursive($translations, $partial);
            }
        }

        return self::$locateCache[$cacheKey] = $translations;
    }

    /**
     * Récupère le nom de base à partir du nom de la classe, namespacé ou non.
     *
     * @param string $name Nom de classe
	 *
     * @return string Basename
     */
    public static function getBasename(string $name): string
    {
        $basename = strrchr($name, '\\');

        return $basename ? substr($basename, 1) : ($name ?: '');
    }

    /**
     * @return array{app: list<string>, system: list<string>, modules: list<string>}|list<string>
     */
    public static function locateFiles(string $filename, string $folder, LocatorInterface $locator, array $directories = []): array
    {
        // Si le fichier est dans un namespace, nous allons simplement le prendre sans rechercher d'autres
        if (str_contains($filename, '\\')) {
            $path = $locator->locateFile($filename, $folder);

            return $path && is_readable($path) ? [$path] : [];
        }

        // Pas de namespace, on recherche donc dans tous les emplacements disponibles

        $directories += [
            'app'    => static::$APP_PATH . $folder . DS,
            'system' => static::$SYST_PATH . $folder . DS,
        ];
		$directories = array_map(fn($directory) => str_replace(['/', '\\'], DS, $directory), $directories);

        $fileMap = ['app' => [], 'system' => [], 'modules' => []];

        $paths = $locator->search($folder . DS . $filename);
		$paths = array_map(fn($path) => str_replace(['/', '\\'], DS, $path), $paths);

        foreach ($paths as $path) {
            if (str_starts_with($path, $directories['app'])) {
                $fileMap['app'][] = $path;
            } elseif (str_starts_with($path, $directories['system'])) {
                $fileMap['system'][] = $path;
            } else {
                $fileMap['modules'][] = $path;
            }
        }

		$fileMap = array_map(
			fn($map) => array_filter($map, fn($file) => !empty($file) && is_readable($file)),
			$fileMap
		);

		$fileMap['app']    = array_slice($fileMap['app'], 0, 1);
		$fileMap['system'] = array_slice($fileMap['system'], 0, 1);

        return $fileMap;
    }

    /**
     * Localise helper.
     *
     * @param string $filename Nom du helper
	 *
     * @return list<string>|false
     */
    private static function locateHelper(string $filename, LocatorInterface $locator)
    {
        $cacheKey = "helper:{$filename}";
        if (isset(self::$locateCache[$cacheKey])) {
            return self::$locateCache[$cacheKey];
        }

        $files = self::locateFiles($filename, 'Helpers', $locator);

        if (isset($files['app'])) {
            $files = array_merge($files['app'], $files['modules'], $files['system']);
        }

        return self::$locateCache[$cacheKey] = $files !== [] ? array_values($files) : [];
    }
}
