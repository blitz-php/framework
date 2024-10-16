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

class Load
{
    /**
     * Liste des elements deja chargés,
     * Si un element est deja chargé, on le renvoie simplement sans avoir besoin de le construire à nouveau
     */
    private static array $loads = [
        'controllers' => [],
        'helpers'     => [],
        'langs'       => [],
        'libraries'   => [],
        'models'      => [],
    ];

    /**
     * Charge un fichier d'aide
     *
     * @throws LoadException
     */
    public static function helper(array|string $helpers)
    {
        if ($helpers === '' || $helpers === '0' || $helpers === []) {
            throw new LoadException('Veuillez specifier le helper à charger');
        }

        $helpers = (array) $helpers;

        foreach ($helpers as $helper) {
            FileLocator::helper($helper);
        }
    }

    /**
     * Charge un modele
     *
     * @return list<object>|object
     *
     * @throws LoadException
     */
    public static function model(array|string $model, ?ConnectionInterface $connection = null)
    {
        if ($model === '' || $model === '0' || $model === []) {
            throw new LoadException('Veuillez specifier le modele à charger');
        }

        if (is_array($model)) {
            $models = [];

            foreach ($model as $value) {
                $models[] = self::model($value, $connection);
            }

            return $models;
        }

        if (! self::isLoaded('models', $model)) {
            self::loaded('models', $model, FileLocator::model($model, $connection));
        }

        return self::getLoaded('models', $model);
    }

    /**
     * Verifie si un element est chargé dans la liste des modules
     */
    private static function isLoaded(string $module, string $element): bool
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
    private static function getLoaded(string $module, string $element)
    {
        return self::$loads[$module][$element] ?? null;
    }
}
