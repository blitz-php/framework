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
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\Container\ContainerInterface;
use Closure;
use DI\Container as DIContainer;
use DI\ContainerBuilder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Conteneur d'injection de dépendances basé sur PHP-DI.
 *
 * Supporte discovery automatique de providers, lazy init, et delegation.
 *
 * @method string debugEntry(string $name)   Obtient les informations de débogage de l'entrée.
 * @method array  getKnownEntryNames()       Obtient des entrées de conteneur définies.
 * @method object injectOn(object $instance) Injecte toutes les dépendances sur une instance existante.
 */
class Container implements ContainerInterface
{
    /**
     * Instance DI sous-jacente.
     */
    protected DIContainer $container;

    /**
     * Drapeau pour determiner si le conteneur est deja initialiser
     */
    private bool $initialized = false;

    /**
     * Noms des providers deja chargés (cache)
     *
     * @var list<class-string<AbstractProvider>>
     */
    private static array $providerNames = [];

    /**
     * Découverte déjà effectuée ?
     */
    private static bool $discovered = false;

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
        $builder->useAttributes(true); // Support annotations
        $builder->useAutowiring(true);

        // cache activé uniquement en production
        if (on_prod(true)) {
            if (extension_loaded('apcu')) {
                $builder->enableDefinitionCache(str_replace([' ', '/', '\\', '.'], '', APP_PATH));
            }

            $builder->enableCompilation(FRAMEWORK_STORAGE_PATH . 'cache');
        }

        $this->discoverProviders();

        foreach (self::$providerNames as $provider) {
            $builder->addDefinitions($provider::definitions());
        }

        $this->container = $builder->build();

        $this->registerProviders();

        $this->initialized = true;
    }

    /**
     * Résout une entrée.
     *
     * @template T
     *
     * @param string $name Nom de l’entrée ou nom de classe.
     *
     * @return T
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get(string $name): mixed
    {
        return $this->container->get($name);
    }

    /**
     * Vérifie si entrée existe dans le conteneur.
     */
    public function has(string $name): bool
    {
        return $this->container->has($name);
    }

    /**
     * Définit un objet ou une valeur dans le conteneur.
     */
    public function set(string $name, mixed $value): void
    {
        $keys = BaseServices::resolveServiceAliases($name);

        foreach ($keys as $key) {
            $this->container->set($key, $value);
        }
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
     * Delegation magique vers PHP-DI.
     *
     * @throws BadMethodCallException Si méthode inconnue.
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (! method_exists($this->container, $method)) {
            throw new BadMethodCallException("Méthode '{$method}' inconnue sur DIContainer.");
        }

        return $this->container->{$method}(...$parameters);
    }

    /**
     * Découvre les providers (vendor > system > app).
     */
    private function discoverProviders(): void
    {
        if (self::$discovered) {
            return;
        }

        $autoDiscovered  = $this->getAutoDiscoveredProviders(service('locator'));
        $manualProviders = $this->getManualProviders();
        $providers       = $this->orderProviders($autoDiscovered, $manualProviders);

        foreach ($providers as $classname) {
            if (! in_array($classname, self::$providerNames, true) && is_subclass_of($classname, AbstractProvider::class, true)) {
                self::$providerNames[] = $classname;
            }
        }

        self::$discovered = true;
    }

    /**
     * Récupère les providers découverts automatiquement via le locator.
     *
     * @return array<string, class-string> [chemin_fichier => classe_provider]
     */
    private function getAutoDiscoveredProviders(LocatorInterface $locator): array
    {
        $files = array_merge(
            $locator->search('Config/Providers'), // Providers systemes
            $locator->listFiles('Providers/'), // Autres providers (vendors, app)
        );

        $providers = [];

        foreach ($files as $file) {
            if ('' !== $classname = $locator->getClassname($file)) {
                $providers[$file] = $classname;
            }
        }

        return $providers;
    }

    /**
     * Récupère les providers définis manuellement dans la configuration.
     *
     * @return list<class-string>
     */
    private function getManualProviders(): array
    {
        $providers = config('app.providers', []);

        return array_values(array_filter($providers, static fn ($provider) => is_string($provider) && class_exists($provider)));
    }

    /**
     * Ordonne les providers selon la priorité :
     * 1. Vendors (Providers auto-découverts hors system et app)
     * 2. Providers manuels (config)
     * 3. System (Providers du framework)
     * 4. App (Providers de l'application)
     *
     * @param array<string, class-string> $autoDiscovered  Providers auto-découverts [fichier => classe]
     * @param list<class-string>          $manualProviders Providers manuels
     *
     * @return list<class-string> Liste ordonnée des classes providers
     */
    private function orderProviders(array $autoDiscovered, array $manualProviders): array
    {
        $vendors = [];
        $system  = [];
        $app     = [];

        // Catégoriser les providers auto-découverts
        foreach ($autoDiscovered as $file => $class) {
            if (str_starts_with($file, APP_PATH)) {
                $app[] = $class;
            } elseif (str_starts_with($file, SYST_PATH)) {
                $system[] = $class;
            } else {
                $vendors[] = $class;
            }
        }

        // Ordre de fusion: vendor > manuel > system > app (last wins)
        return array_merge(
            $vendors,  // Les founisseurs des vendors sont les premier a etre remplacer si besoin
            $manualProviders,  // Providers manuels (même niveau que vendors)
            $system,  // Les founisseurs du systeme viennent ensuite pour eventuelement remplacer pour les vendors
            $app,  // Ceux de l'application ont peu de chance de modifier quelque chose mais peuvent le faire
        );
    }

    /**
     * Enregistre les providers.
     */
    private function registerProviders(): void
    {
        foreach (self::$providerNames as $classname) {
            /** @var AbstractProvider $provider */
            $provider = $this->container->make($classname, ['container' => $this]);

            $provider->register();
        }

        $this->set(self::class, $this);
        $this->set(ContainerInterface::class, $this);
    }
}
