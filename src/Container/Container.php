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
use BlitzPHP\Contracts\Container\ContainerInterface;
use Closure;
use DI\Container as DIContainer;
use DI\ContainerBuilder;

/**
 * Conteneur d’injection de dépendances.
 *
 * @method string debugEntry(string $name)        Obtenir les informations de débogage de l'entrée.
 * @method array  getKnownEntryNames()            Obtenez des entrées de conteneur définies.
 * @method object injectOn(object $instance)      Injectez toutes les dépendances sur une instance existante.
 * @method void   set(string $name, mixed $value) Définissez un objet ou une valeur dans le conteneur.
 */
class Container implements ContainerInterface
{
    protected DIContainer $container;

    /**
     * Providers deja charges (cache)
     *
     * @var list<AbstractProvider>
     */
    private static array $providers = [];

    /**
     * Noms des providers deja charges (cache)
     *
     * @var list<class-string<AbstractProvider>>
     */
    private static array $providerNames = [];

    /**
     * Avons-nous déjà découvert les fournisseurs ?
     */
    private static bool $discovered = false;

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

    /**
     * Construire une entrée du conteneur par son nom.
     *
     * Cette méthode se comporte comme get() sauf qu'elle résout l'entrée à chaque fois.
     * Par exemple, si l'entrée est une classe, une nouvelle instance sera créée à chaque fois.
     *
     * Cette méthode permet au conteneur de se comporter comme une usine.
     *
     * @template T
     *
     * @param class-string<T>|string $name       Nom de l'entrée ou nom de la classe.
     * @param array                  $parameters Paramètres optionnels à utiliser pour construire l'entrée.
     *                                           Utilisez ceci pour forcer des paramètres spécifiques à des valeurs spécifiques.
     *                                           Les paramètres non définis dans ce tableau seront résolus à l'aide du conteneur.
     *
     * @return mixed|T
     */
    public function make(string $name, array $parameters = []): mixed
    {
        return $this->container->make($name, $parameters);
    }

    /**
     * Appelle la fonction donnée en utilisant les paramètres donnés.
     * Les paramètres manquants seront résolus à partir du conteneur.
     *
     * @param array|callable|string $callback   Fonction à appeler.
     * @param array                 $parameters Paramètres facultatifs à utiliser pour construire l'entrée.
     *                                          Utilisez ceci pour forcer des paramètres spécifiques à des valeurs spécifiques.
     *                                          Les paramètres non définis dans ce tableau seront résolus en utilisant le conteneur.
     *                                          Peut être indexé par les noms de paramètre ou non indexé (même ordre que les paramètres).
     *                                          Le tableau peut également contenir des définitions DI, par ex. DI\get().
     */
    public function call(array|callable|string $callback, array $parameters = []): mixed
    {
        return $this->container->call($callback, $parameters);
    }

    /**
     * Defini un element au conteneur sous forme de factory
     * Si l'element existe déjà, il sera remplacé
     */
    public function add(string $key, Closure $callback): void
    {
        $this->container->set($key, $callback);

        $this->container->set(self::class, $this);
    }

    /**
     * Defini un element au conteneur sous forme de factory
     * Si l'element existe déjà, il sera ignoré
     */
    public function addIf(string $key, Closure $callback): void
    {
        if (! $this->has($key)) {
            $this->add($key, $callback);
        }
    }

    /**
     * Defini plusieurs elements au conteneur sous forme de factory
     * L'element qui existera déjà sera remplacé par la correspondance du tableau
     *
     * @param array<string, Closure> $keys
     */
    public function merge(array $keys): void
    {
        foreach ($keys as $key => $callback) {
            if ($callback instanceof Closure) {
                $this->add($key, $callback);
            }
        }
    }

    /**
     * Defini plusieurs elements au conteneur sous forme de factory
     * L'element qui existera déjà sera ignoré
     *
     * @param array<string, Closure> $keys
     */
    public function mergeIf(array $keys): void
    {
        foreach ($keys as $key => $callback) {
            if ($callback instanceof Closure) {
                $this->addIf($key, $callback);
            }
        }
    }

    /**
     * Verifie qu'une entree a été explicitement définie dans le conteneur
     */
    public function bound(string $key): bool
    {
        return in_array($key, $this->getKnownEntryNames(), true);
    }

    /**
     * Methode magique pour acceder aux methodes de php-di
     *
     * @param mixed $name
     * @param mixed $arguments
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->container, $name)) {
            return call_user_func_array([$this->container, $name], $arguments);
        }

        throw new BadMethodCallException('Methode "' . $name . '" non definie');
    }

    /**
     * Initialise le conteneur et injecte les services providers.
     *
     * @internal
     */
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

        $this->discoveProviders();

        foreach (self::$providerNames as $provider) {
            $builder->addDefinitions($provider::definitions());
        }

        $this->container = $builder->build();

        $this->registryProviders();

        $this->initialized = true;
    }

    /**
     * Listes des providers chargés par le framework
     *
     * @return array<class-string<AbstractProvider>, AbstractProvider>
     */
    public static function providers(): array
    {
        return array_combine(self::$providerNames, self::$providers);
    }

    /**
     * Enregistre les provider dans le conteneur
     */
    private function registryProviders(): void
    {
        foreach (self::$providerNames as $classname) {
            $provider = $this->container->make($classname, [
                'container' => $this,
            ]);
            $this->container->call([$provider, 'register']);
            self::$providers[] = $provider;
        }

        $this->set(self::class, $this);
        $this->set(ContainerInterface::class, $this);
    }

    /**
     * Recherche tous les fournisseurs disponibles et les charge en cache
     */
    private function discoveProviders(): void
    {
        if (! self::$discovered) {
            $locator = service('locator');
            $files   = array_merge(
                $locator->search('Config/Providers'),
                $locator->listFiles('Providers/'),
            );

            $appProviders  = array_filter($files, static fn ($name) => str_starts_with($name, APP_PATH));
            $systProviders = array_filter($files, static fn ($name) => str_starts_with($name, SYST_PATH));
            $files         = array_diff($files, $appProviders, $systProviders);

            $files = [
                ...$files, // Les founisseurs des vendors sont les premier a etre remplacer si besoin
                ...$systProviders, // Les founisseurs du systeme viennent ensuite pour eventuelement remplacer pour les vendors sont les
                ...$appProviders, // Ceux de l'application ont peu de chance de modifier quelque chose mais peuvent le faire
            ];

            // Obtenez des instances de toutes les classes de providers et mettez-les en cache localement.
            foreach ($files as $file) {
                if (is_a($classname = $locator->getClassname($file), AbstractProvider::class, true)) {
                    self::$providerNames[] = $classname;
                }
            }

            self::$discovered = true;
        }
    }
}
