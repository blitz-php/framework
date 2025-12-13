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

/**
 * Facade pour chargement de ressources (helpers, models, etc.).
 *
 * Gère cache statique par module.
 */
class Load
{
    /**
     * Éléments déjà chargés (module → [element → value]).
     * Si un element est deja chargé, on le renvoie simplement sans avoir besoin de le construire à nouveau
     *
     * @var array<string, array<string, bool|array|string>>
     */
    private static array $loaded = [];

    /**
     * Charge helpers
     *
     * @param list<string>|string $helpers Noms.
	 *
     * @throws LoadException Si empty/invalide.
     */
    public static function helper(array|string $helpers): void
    {
        if ($helpers === '' || $helpers === '0' || $helpers === []) {
            throw new LoadException('Veuillez spécifier le helper à charger.');
        }

       foreach ((array) $helpers as $helper) {
            $helper = trim($helper);
            if (empty($helper)) {
                continue;
            }
            if (! self::isLoaded('helper', $helper)) {
                FileLocator::helper($helper);
                self::loaded('helper', $helper, true);
            }
        }
    }

    /**
     * Charge config (wrap FileLocator, cache result)
     *
     * @return array<string, mixed>
     */
    public static function config(string $name): array
    {
        if (! self::isLoaded('config', $name)) {
            $config = FileLocator::config($name);
            self::loaded('config', $name, $config);
        }

        return self::getLoaded('config', $name);
    }

    /**
     * Charge view (wrap, cache path)
     *
     * @return string|false
	 *
     * @throws \BlitzPHP\Exceptions\ViewException
     */
    public static function view(string $name)
    {
        if (! self::isLoaded('view', $name)) {
            $path = FileLocator::view($name);
            self::loaded('view', $name, $path);
        }

        return self::getLoaded('view', $name);
    }

    /**
     * Décharge élément.
     */
    public static function unload(string $module, string|object $element): void
    {
        $key = is_object($element) ? get_class($element) : $element;

        unset(self::$loaded[$module][$key]);
    }

    /**
     * Décharge tout pour un module.
     *
     * @param string $module Module optionnel (all si vide)
     */
    public static function unloadAll(?string $module = null): void
    {
		if ($module && isset(self::$loaded[$module])) {
            self::$loaded[$module] = [];
        } else {
            self::$loaded = [];
        }
    }

    /**
     * Vérifie si un élément est chargé dans la liste des modules.
     * Gère objects comme string keys.
     */
    protected static function isLoaded(string $module, string|object $element): bool
    {
        if (!isset(self::$loaded[$module]) || !is_array(self::$loaded[$module])) {
            return false;
        }

        $key = is_object($element) ? get_class($element) : $element;

        return isset(self::$loaded[$module][$key]);
    }

    /**
     * Ajoute un element aux elements chargés.
     *
     * @param bool|array|string $value Valeur (ou true pour helpers)
     */
    protected static function loaded(string $module, string|object $element, bool|array|string $value): void
    {
        $key = is_object($element) ? get_class($element) : $element;

        if (! isset(self::$loaded[$module])) {
            self::$loaded[$module] = [];
        }

        self::$loaded[$module][$key] = $value;
    }

    /**
     * Renvoie un élément chargé.
     *
     * @return bool|array|string|null
     */
    protected static function getLoaded(string $module, string|object $element): mixed
    {
        $key = is_object($element) ? get_class($element) : $element;

        if (! isset(self::$loaded[$module])) {
            self::$loaded[$module] = [];
        }

        return self::$loaded[$module][$key] ?? null;
    }
}
