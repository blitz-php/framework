<?php

namespace BlitzPHP\Router;

use BadMethodCallException;
use BlitzPHP\Loader\Services;
use BlitzPHP\Utilities\Iterable\Arr;
use Closure;
use InvalidArgumentException;

/**
 * @method $this addPlaceholder($placeholder, ?string $pattern = null) Enregistre une nouvelle contrainte auprès du système.
 * @method $this setDefaultNamespace(string $value) Définit l'espace de noms par défaut à utiliser pour les contrôleurs lorsqu'aucun autre n'a été spécifié.
 * @method $this setDefaultController(string $value) Définit le contrôleur par défaut à utiliser lorsqu'aucun autre contrôleur n'a été spécifié.
 * @method $this setDefaultMethod(string $value) Définit la méthode par défaut pour appeler le contrôleur lorsqu'aucun autre méthode a été définie dans la route.
 * @method $this setTranslateURIDashes(bool $value) Indique au système s'il faut convertir les tirets des chaînes URI en traits de soulignement.
 * @method $this setAutoRoute(bool $value) Si TRUE, le système tentera de faire correspondre l'URI avec
     * Contrôleurs en faisant correspondre chaque segment avec des dossiers/fichiers
     * dans CONTROLLER_PATH, lorsqu'aucune correspondance n'a été trouvée pour les routes définies.
 * @method $this set404Override($callable = null) Définit la classe/méthode qui doit être appelée si le routage ne trouver pas une correspondance.
 * @method $this setDefaultConstraint(string $placeholder) Définit la contrainte par défaut à utiliser dans le système. Typiquement à utiliser avec la méthode 'ressource'.
 * @method $this setPrioritize(bool $enabled = true) Activer ou désactiver le tri des routes par priorité
 * @method $this addRedirect(string $from, string $to, int $status = 302) Ajoute une redirection temporaire d'une route à une autre.
 * @method $this as(string $name)
 * @method $this controller(string $controller)
 * @method $this domain(string $domain)
 * @method $this hostname(string $hostname)
 * @method $this middleware(array|string $middleware)
 * @method $this name(string $name)
 * @method $this namespace(string $namespace)
 * @method $this placeholder(string $placeholder)
 * @method $this prefix(string $prefix)
 * @method $this priority(int $priority)
 * @method $this subdomain(string $subdomain)
 * @method void add(string $from, array|callable|string $to, array $options = []) Enregistre une seule route à la collection.
 * @method void cli(string $from, array|callable|string $to, array $options = []) Enregistre une route qui ne sera disponible que pour les requêtes de ligne de commande.
 * @method void delete(string $from, array|callable|string $to, array $options = []) Enregistre une route qui ne sera disponible que pour les requêtes DELETE.
 * @method void get(string $from, array|callable|string $to, array $options = []) Enregistre une route qui ne sera disponible que pour les requêtes GET.
 * @method void head(string $from, array|callable|string $to, array $options = []) Enregistre une route qui ne sera disponible que pour les requêtes HEAD.
 * @method void options(string $from, array|callable|string $to, array $options = []) Enregistre une route qui ne sera disponible que pour les requêtes OPTIONS.
 * @method void patch(string $from, array|callable|string $to, array $options = []) Enregistre une route qui ne sera disponible que pour les requêtes PATCH.
 * @method void post(string $from, array|callable|string $to, array $options = []) Enregistre une route qui ne sera disponible que pour les requêtes POST.
 * @method void put(string $from, array|callable|string $to, array $options = []) Enregistre une route qui ne sera disponible que pour les requêtes PUT.
 */
final class RouteBuilder 
{
    /**
     * Les attributs à transmettre au routeur.
     */
    private array $attributes = [];

    /**
     * Les méthodes à transmettre dynamiquement au routeur.
     */
    private array $passthru = [
        'add', 'cli', 'delete', 'get', 'head', 'options', 'post', 'put', 'patch',
    ];

    /**
     * Les attributs qui peuvent être définis via cette classe.
     */
    private array $allowedAttributes = [
        'as', 'controller', 'domain', 'hostname', 'middleware', 'name', 
        'namespace', 'placeholder', 'prefix', 'priority', 'subdomain',
    ];

    /**
     * Les attributs qui sont des alias.
     */
    private array $aliases = [
        'name' => 'as',
    ];

    private array $allowedMethods = [
        'addPlaceholder', 'addRedirect',       
        'set404Override', 'setAutoRoute',
        'setDefaultConstraint', 'setDefaultController', 'setDefaultMethod', 'setDefaultNamespace',
        'setTranslateURIDashes', 'setPrioritize',
    ];

    
    /**
     * Constructeur
     *
     * @param RouteCollection $collection
     */
    public function __construct(private RouteCollection $collection)
    {

    }

    /**
     * Gérez dynamiquement les appels dans le registraire de routage.
     *
     * @return self
     *
     * @throws BadMethodCallException
     */
    public function __call(string $method, array $parameters = [])
    {
        if (in_array($method, $this->passthru)) {
            return $this->registerRoute($method, ...$parameters);
        }

        if (in_array($method, $this->allowedAttributes)) {
            if ($method === 'middleware') {
                return $this->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
            }

            return $this->attribute($method, $parameters[0]);
        }

        if (in_array($method, $this->allowedMethods)) {
            $collection = $this->collection->{$method}(...$parameters);

            if ($collection instanceof RouteCollection) {
                Services::set(RouteCollection::class, $collection);
                $this->collection = $collection;
            }

            return $this;
        }

        throw new BadMethodCallException(sprintf('La méthode %s::%s n\'existe pas.', static::class, $method));
    }

    public function configure(callable $callback)
    {
        $callback($this->collection);    
    }

    /**
     * Limite les routes à un ENVIRONNEMENT spécifié ou ils ne fonctionneront pas.
     */
    public function environment(string $env, Closure $callback): void
    {
        if ($env === config('app.environment')) {
            $callback($this);
        }
    }

    public function form(string $from, $to, array $options = []): void
    {
        $this->match(['get', 'post'], $from, $to, $options);
    }

    /**
     * Create a route group with shared attributes.
     */
    public function group(callable $callback): void
    {
        $this->collection->group($this->attributes['prefix'] ?? '', $this->attributes, $callback);
    }

    /**
     * Ajoute une seule route à faire correspondre pour plusieurs verbes HTTP.
     *
     * Exemple:
     *  $route->match( ['get', 'post'], 'users/(:num)', 'users/$1);
     *
     * @param array|Closure|string $to
     */
    public function match(array $verbs = [], string $from = '', $to = '', array $options = []): void
    {
        $this->collection->match($verbs, $from, $to, $this->attributes + $options);
    }

    /**
     * Crée une collection de routes basées sur les verbes HTTP pour un contrôleur de présentation.
     *
     * Options possibles :
     * 'controller' - Personnalisez le nom du contrôleur utilisé dans la route 'to'
     * 'placeholder' - L'expression régulière utilisée par le routeur. La valeur par défaut est '(:any)'
     *
     * Example:
     *
     *      $route->presenter('photos');
     *
     *      // Génère les routes suivantes
     *      HTTP Verb | Path        | Action        | Used for...
     *      ----------+-------------+---------------+-----------------
     *      GET         /photos             index           affiche le tableau des tous les objets photo
     *      GET         /photos/show/{id}   show            affiche un objet photo spécifique, toutes les propriétés
     *      GET         /photos/new         new             affiche un formulaire pour un objet photo vide, avec les propriétés par défaut
     *      POST        /photos/create      create          traitement du formulaire pour une nouvelle photo
     *      GET         /photos/edit/{id}   edit            affiche un formulaire d'édition pour un objet photo spécifique, propriétés modifiables
     *      POST        /photos/update/{id} update          traitement des données du formulaire d'édition
     *      GET         /photos/remove/{id} remove          affiche un formulaire pour confirmer la suppression d'un objet photo spécifique
     *      POST        /photos/delete/{id} delete          suppression de l'objet photo spécifié
     *
     * @param string     $name    Le nom du contrôleur vers lequel router.
     * @param array|null $options Une liste des façons possibles de personnaliser le routage.
     */
    public function presenter(string $name, array $options = []): void
    {
        $this->collection->presenter($name, $this->attributes + $options);
    }

    /**
     * Crée une collection de routes basés sur HTTP-verb pour un contrôleur.
     *
     * Options possibles :
     * 'controller' - Personnalisez le nom du contrôleur utilisé dans la route 'to'
     * 'placeholder' - L'expression régulière utilisée par le routeur. La valeur par défaut est '(:any)'
     * 'websafe' - - '1' si seuls les verbes HTTP GET et POST sont pris en charge
     *
     * Exemple:
     *
     *      $route->resource('photos');
     *
     *      // Genère les routes suivantes:
     *      HTTP Verb | Path        | Action        | Used for...
     *      ----------+-------------+---------------+-----------------
     *      GET         /photos             index           un tableau d'objets photo
     *      GET         /photos/new         new             un objet photo vide, avec des propriétés par défaut
     *      GET         /photos/{id}/edit   edit            un objet photo spécifique, propriétés modifiables
     *      GET         /photos/{id}        show            un objet photo spécifique, toutes les propriétés
     *      POST        /photos             create          un nouvel objet photo, à ajouter à la ressource
     *      DELETE      /photos/{id}        delete          supprime l'objet photo spécifié
     *      PUT/PATCH   /photos/{id}        update          propriétés de remplacement pour la photo existante
     *
     *  Si l'option 'websafe' est présente, les chemins suivants sont également disponibles :
     *
     *      POST		/photos/{id}/delete delete
     *      POST        /photos/{id}        update
     *
     * @param string     $name    Le nom de la ressource/du contrôleur vers lequel router.
     * @param array|null $options Une liste des façons possibles de personnaliser le routage.
     */
    public function resource(string $name, array $options = []): void
    {
        $this->collection->resource($name, $this->attributes + $options);
    }

    /**
     * Une méthode de raccourci pour ajouter un certain nombre de routes en une seule fois.
     * Il ne permet pas de définir des options sur la route, ou de définir la méthode utilisée.
     */
    public function map(array $routes = [], array $options = []): void
    {
        $this->collection->map($routes, $this->attributes + $options);
    }

    /**
     * Spécifie une route qui n'affichera qu'une vue.
     * Ne fonctionne que pour les requêtes GET.
     */
    public function view(string $from, string $view, array $options = []): void
    {
        $this->collection->view($from, $view, $this->attributes + $options);
    }

    /**
     * Defini une valeur pour l'attribut donné
     *
     * @throws InvalidArgumentException
     */
    private function attribute(string $key, $value): self
    {
        if (! in_array($key, $this->allowedAttributes)) {
            throw new InvalidArgumentException("L'attribute [{$key}] n'existe pas.");
        }

        $this->attributes[Arr::get($this->aliases, $key, $key)] = $value;

        return $this;
    }

    /**
     * Enregistre une nouvelle route avec le routeur.
     */
    private function registerRoute(string $method, string $from, $to, array $options = []): self
    {
        $this->collection->{$method}($from, $to, $this->attributes + $options);

        return $this;
    }
}