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

use BlitzPHP\Traits\SingletonTrait;
use DI\Container;
use DI\ContainerBuilder;

/**
 * Injector
 *
 *  Conteneur d'Injection de Dependences
 */
class Injector
{
    use SingletonTrait;
    
    /**
     * @var \DI\Container
     */
    private $container;

    /**
     * @var ContainerBuilder
     */
    private $builder;

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->builder = new ContainerBuilder();
        $this->builder->useAutowiring(true);

        if (on_prod()) {
            $this->builder->enableCompilation(SYST_PATH.'Constants'.DS);
        }

        $this->container = new Container();
    }

    /**
     * Charge les definitions pour le container
     */
    private function loadProviders() {
        $providers = Load::providers();
        
        $this->builder->addDefinitions($providers);

        $this->container = $this->builder->build();
    }

    /**
     * Initialise le container d'injection de dependences
     */
    public static function init()
    {
        self::instance()->loadProviders();
    }

    /**
     * Renvoie l'instance du conteneur
     */
    public static function container(): Container
    {
        return self::instance()->container;
    }

    /**
     * Renvoie une entrée du conteneur par son nom.
     *
     * @param string $name Entry name or a class name.
     *
     * @return mixed
     */
    public static function get(string $name)
    {
        return self::container()->get($name);
    }

    /**
     * Alias de self::get
     */
    public static function singleton(string $classname)
    {
        return self::get($classname);
    }

    /**
     * Construire une entrée du conteneur par son nom.
     *
     * Cette méthode se comporte comme singleton() sauf qu'elle résout à nouveau l'entrée à chaque fois.
     * Par exemple, si l'entrée est une classe, une nouvelle instance sera créée à chaque fois.
     *
     * Cette méthode fait que le conteneur se comporte comme une usine.
     *
     * @param string $name       Nom d'entrée ou nom de classe.
     * @param array  $parameters Paramètres facultatifs à utiliser pour construire l'entrée. Utilisez ceci pour forcer des paramètres spécifiques
     *                           à des valeurs spécifiques. Les paramètres non définis dans ce tableau seront résolus en utilisant le conteneur.
     *
     * @return mixed
     */
    public static function make(string $name, array $parameters = [])
    {
        return self::container()->make($name, $parameters);
    }

    /**
     * Alias de self::make
     */
    public static function factory(string $classname, array $parameters = [])
    {
        return self::make($classname, $parameters);
    }

    /**
     * Appelez la fonction donnée en utilisant les paramètres donnés.
     *
     * Les paramètres manquants seront résolus à partir du conteneur.
     *
     * @param callable $callable   Fonction à appeler.
     * @param array    $parameters Paramètres à utiliser. Peut être indexé par les noms de paramètre
     *                             ou non indexé (même ordre que les paramètres).
     *                             Le tableau peut également contenir des définitions DI, par ex. DI\get().
     *
     * @return mixed Resultat de la fonction.
     */
    public static function call($callable, array $params = [])
    {
        return self::container()->call($callable, $params);
    }

    /**
     * Testez si le conteneur peut fournir quelque chose pour le nom donné.
     *
     * @param string $name Nom d'entrée ou nom de classe
     */
    public static function has(string $name): bool
    {
        return self::container()->has($name);
    }

    /**
     * Définissez un objet ou une valeur dans le conteneur.
     *
     * @param string $name  Nom de l'entrée
     * @param mixed  $value utilisez les aides à la définition pour définir les objets
     */
    public static function add(string $name, $value): void
    {
        self::container()->set($name, $value);
    }
}
