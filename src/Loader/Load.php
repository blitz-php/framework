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

class Load
{
    /**
     * @var array
     */
    private static $loads = [
        'controllers' => [],
        'helpers'     => [],
        'langs'       => [],
        'libraries'   => [],
        'models'      => [],
    ];

    /**
     * Recupere toutes les definitions des services a injecter dans le container
     */
    public static function providers(): array
    {
        $providers = [];

        // services système
        $filename = SYST_PATH . 'Constants' . DS . 'providers.php';
        if (! file_exists($filename)) {
            throw LoadException::providersDefinitionDontExist($filename);
        }
        if (! in_array($filename, get_included_files(), true)) {
            $providers = array_merge($providers, require $filename);
        }

        // services de l'application
        $filename = CONFIG_PATH . 'providers.php';
        if (file_exists($filename) && ! in_array($filename, get_included_files(), true)) {
            $providers = array_merge($providers, require $filename);
        }

        return $providers;
    }

    /**
     * Charge un fichier d'aide
     *
     * @param string|array $helpers
     * @throws LoadException
     * @throws InvalidArgumentException
     */
    public static function helper(string|array $helpers)
    {
        if (empty($helpers)) {
            throw new LoadException('Veuillez specifier le helper à charger');
        }

        $helpers = (array) $helpers;
        foreach ($helpers As $helper) {
           FileLocator::helper($helper);
        }
    }

    /**
     * Charge un fichier de gestion de langue
     */
    public static function lang(string $file, ?string $locale = null): array
    {
        $locale ??= config('app.language');
        
        if (! self::isLoaded('langs', $file.$locale)) {
            self::loaded('langs', $file.$locale, FileLocator::lang($file, $locale));
        }

        return self::getLoaded('langs', $file.$locale);
    }

    /**
     * Verifie si un element est chargé dans la liste des modules
     *
     * @param $element
     */
    private static function isLoaded(string $module, $element): bool
    {
        if (! isset(self::$loads[$module]) || ! is_array(self::$loads[$module])) {
            return false;
        }

        return in_array($element, self::$loads[$module], true);
    }

    /**
     * Ajoute un element aux elements chargés
     *
     * @param mixed $value
     */
    private static function loaded(string $module, string $element, $value = null): void
    {
        self::$loads[$module][$element] = $value;
    }

    /**
     * Renvoie un element chargé
     *
     * @return mixed
     */
    private static function getLoaded(string $module, $element)
    {
        return self::$loads[$module][$element] ?? null;
    }
}
