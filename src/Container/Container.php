<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Container;

use BadMethodCallException;
use DI\Container as DIContainer;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

/**
 * Conteneur d’injection de dépendances.
 * 
 * @method mixed make(string $name, array $parameters = []) Construire une entrée du conteneur par son nom.
 *                                                          Cette méthode se comporte comme singleton() sauf qu'elle résout à nouveau l'entrée à chaque fois.
 *                                                          Par exemple, si l'entrée est une classe, une nouvelle instance sera créée à chaque fois.
 *                                                          Cette méthode fait que le conteneur se comporte comme une usine.
 *
 *                                                          @param array  $parameters Paramètres facultatifs à utiliser pour construire l'entrée. 
 *                                                                                    Utilisez ceci pour forcer des paramètres spécifiques à des valeurs spécifiques. 
 *                                                                                    Les paramètres non définis dans ce tableau seront résolus en utilisant le conteneur.
 * @method mixed call(array|callable|string $callable, array $parameters = []) Appelez la fonction donnée en utilisant les paramètres donnés.
 *                                                                             Les paramètres manquants seront résolus à partir du conteneur.
 *                                                        
 *                                                          @param array  $parameters Paramètres à utiliser. 
 *                                                                                    Peut être indexé par les noms de paramètre ou non indexé (même ordre que les paramètres).
 *                                                                                    Le tableau peut également contenir des définitions DI, par ex. DI\get().
 * @method string debugEntry(string $name) Obtenir les informations de débogage de l'entrée.
 * @method object injectOn(object $instance) Injectez toutes les dépendances sur une instance existante.
 * @method void set(string $name, mixed $value) Définissez un objet ou une valeur dans le conteneur.
 * @method void add(string $name, mixed $value) Définissez un objet ou une valeur dans le conteneur.
 * @method array getKnownEntryNames() Obtenez des entrées de conteneur définies.
 */
class Container implements ContainerInterface
{
    protected DIContainer $container;

    /**
     * Providers deja charges (cache)
     */
    private static array $providers = [];

    /**
     * methodes aliases 
     */
    private static array $alias = [
        'add' => 'set'
    ];

    /**
     * Drapeau pour determiner si le conteneur est deja initialiser
     */
    private bool $initialized = false;

    /**
     * Renvoie une entrée du conteneur par son nom.
     *
     * @param string $name Nom de l’entrée ou nom de classe.
     *
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->container->get($name);
    }

    /**
     * Testez si le conteneur peut fournir quelque chose pour le nom donné.
     *
     * @param string $name Nom d'entrée ou nom de classe
     */
    public function has(string $name): bool
    {
        return $this->container->has($name);
    }

    public function __call($name, $arguments)
    {
        if (isset(self::$alias[$name])) {
            $name = self::$alias[$name];
        }

        if (method_exists($this->container, $name)) {
            return call_user_func_array([$this->container, $name], $arguments);
        }

        throw new BadMethodCallException('Methode "' . $name . '" non definie');
    }
    
    public function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAttributes(true);

        if (on_prod(true)) {
            if (extension_loaded('apcu')) {
                $builder->enableDefinitionCache(str_replace([' ', '/', '\\', '.'], '', APP_PATH));
            }
            
            $builder->enableCompilation(FRAMEWORK_STORAGE_PATH . 'cache');
        }

        $builder->addDefinitions(...self::providers());

        $this->container = $builder->build();

        $this->set(self::class, $this);
        $this->set(ContainerInterface::class, $this);

        $this->initialized = true;
    }

    /**
     * Recupere toutes les definitions des services à injecter dans le container
     */
    public static function providers(): array
    {
        if (! empty(static::$providers)) {
            return static::$providers;
        }

        $providers = [];

        $loader = Services::locator();

        // Stockez nos versions de providers système et d'application afin que nous puissions contrôler l'ordre de chargement.
        $systemProvider = null;
        $appProvider    = null;
        $localIncludes  = [];

        $paths = array_merge(
            $loader->search('Constants/providers'), // providers system
            $loader->search('Config/providers') // providers de l'application ou des fournisseurs
        );

        foreach ($paths as $path) {
            if (strpos($path, APP_PATH . 'Config' . DS) === 0) {
                $appProvider = $path;
            } elseif (strpos($path, SYST_PATH . 'Constants' . DS) === 0) {
                $systemProvider = $path;
            } else {
                $localIncludes[] = $path;
            }
        }

        // Les providers par défaut du système doivent être ajouté en premier pour que les autres puisse les surcharger
        if (! empty($systemProvider)) {
            $providers[] = $systemProvider;
        }

        // Tous les providers avec espace de noms sont ajoutés ensuite
        $providers = [...$providers, ...$localIncludes];

        // Enfin ceux de l'application doivent remplacer tous les autres
        if (! empty($appProvider)) {
            $providers[] = $appProvider;
        }

        return static::$providers = $providers;
    }
}
