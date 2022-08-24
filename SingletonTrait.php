<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Traits;

use RuntimeException;

/**
 * Ce trait fournit le modèle Singleton (une seule instance pour la classe concrète) aux classes qui l'utilisent.
 * Toute l'application entière peut accepter sa seule instance via la méthode statique publique getInstance(),
 * fourni par le trait.
 * Si quelqu'un essaie de cloner ou de sérialiser l'objet, le trait lève RuntimeException.
 * La propriété statique pour la seule instance est déclarée comme protégée et est instanciée avec le mot-clé 'static'
 * pour assurer la possibilité d'étendre la classe.
 */
trait SingletonTrait
{
    /**
     * La seule instance d'utilisation de la classe
     *
     * @var object
     */
    protected static $_instance;

    /**
     * Vérifie, instancie et renvoie la seule instance de la classe appelée.
	 *
	 * @return static
     */
    public static function instance()
    {
        if (! (static::$_instance instanceof static)) {
            $params            = func_get_args();
            static::$_instance = new static(...$params);
        }

        return static::$_instance;
    }

    /**
     * @alias instance
     */
    public static function getInstance()
    {
        $params = func_get_args();

        return static::instance(...$params);
    }

    /**
     * Constructeur de classe. La classe concrète utilisant ce trait peut le remplacer.
     */
    protected function __construct()
    {
    }

    /**
     * Empêche le clonage des objets
     *
     * @throws RuntimeException
     */
    public function __clone()
    {
        throw new RuntimeException('Cannot clone Singleton objects');
    }

    /**
     * Empêche la sérialisation des objets
     *
     * @throws RuntimeException
     */
    public function __sleep()
    {
        throw new RuntimeException('Cannot serialize Singleton objects');
    }

    /**
     * Renvoie la seule instance si elle est appelée en tant que fonction
     *
     * @return object
     */
    public function __invoke()
    {
        return static::getInstance();
    }
}
