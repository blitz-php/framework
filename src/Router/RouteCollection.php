<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Router;

use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Loader\Services;
use Closure;
use InvalidArgumentException;

class RouteCollection implements RouteCollectionInterface
{
    /**
     * L'espace de noms à ajouter à tous les contrôleurs.
     * Par défaut, les espaces de noms globaux (\)
     */
    protected string $defaultNamespace = '\\';

    /**
     * Le nom du contrôleur par défaut à utiliser
     * lorsqu'aucun autre contrôleur n'est spécifié.
     *
     * Non utilisé ici. Valeur d'intercommunication pour la classe Routeur.
     */
    protected string $defaultController = 'Home';

    /**
     * Le nom de la méthode par défaut à utiliser
     * lorsqu'aucune autre méthode n'a été spécifiée.
     *
     * Non utilisé ici. Valeur d'intercommunication pour la classe Routeur.
     */
    protected string $defaultMethod = 'index';

    /**
     * L'espace réservé utilisé lors du routage des "ressources"
     * lorsqu'aucun autre espace réservé n'a été spécifié.
     */
    protected string $defaultPlaceholder = 'any';

    /**
     * S'il faut convertir les tirets en traits de soulignement dans l'URI.
     *
     * Non utilisé ici. Valeur d'intercommunication pour la classe Routeur.
     */
    protected bool $translateURIDashes = true;

    /**
     * S'il faut faire correspondre l'URI aux contrôleurs
     * lorsqu'il ne correspond pas aux itinéraires définis.
     *
     * Non utilisé ici. Valeur d'intercommunication pour la classe Routeur.
     */
    protected bool $autoRoute = true;

    /**
     * Un appelable qui sera affiché
     * lorsque la route ne peut pas être matchée.
     *
     * @var Closure|string
     */
    protected $override404;

    /**
     * Espaces réservés définis pouvant être utilisés.
     */
    protected array $placeholders = [
        'any'      => '.*',
        'segment'  => '[^/]+',
        'alphanum' => '[a-zA-Z0-9]+',
        'num'      => '[0-9]+',
        'alpha'    => '[a-zA-Z]+',
        'hash'     => '[^/]+',
        'slug'     => '[a-z0-9-]+',
    ];

    /**
     * Tableau de toutes les routes et leurs mappages.
     *
     * @example
     * ```php
     * [
     *     verb => [
     *         routeName => [
     *             'route' => [
     *                 routeKey(regex) => handler,
     *             ],
     *             'redirect' => statusCode,
     *         ]
     *     ],
     * ]
     * ```
     */
    protected array $routes = [
        '*'       => [],
        'options' => [],
        'get'     => [],
        'head'    => [],
        'post'    => [],
        'put'     => [],
        'delete'  => [],
        'trace'   => [],
        'connect' => [],
        'cli'     => [],
    ];

    /**
     * Tableaux des options des routes.
     *
     * @example
     * ```php
     * [
     *     verb => [
     *         routeKey(regex) => [
     *             key => value,
     *         ]
     *     ],
     * ]
     * ```
     */
    protected array $routesOptions = [];

    /**
     * La méthode actuelle par laquelle le script est appelé.
     */
    protected string $HTTPVerb = '*';

    /**
     * La liste par défaut des méthodes HTTP (et CLI pour l'utilisation de la ligne de commande)
     * qui est autorisé si aucune autre méthode n'est fournie.
     */
    protected array $defaultHTTPMethods = [
        'options',
        'get',
        'head',
        'post',
        'put',
        'delete',
        'trace',
        'connect',
        'cli',
    ];

    /**
     * Le nom du groupe de route courant
     *
     * @var string|null
     */
    protected $group;

    /**
     * Le sous domaine courant
     *
     * @var string|null
     */
    protected $currentSubdomain;

    /**
     * Stocke une copie des options actuelles en cours appliqué lors de la création.
     *
     * @var array|null
     */
    protected $currentOptions;

    /**
     * Un petit booster de performances.
     */
    protected bool $didDiscover = false;

    /**
     * Descripteur du localisateur de fichiers à utiliser.
     *
     * @var Locator
     */
    protected $locator;

    /**
     * Drapeau pour trier les routes par priorité.
     */
    protected bool $prioritize = false;

    /**
     * Indicateur de détection de priorité de route.
     */
    protected bool $prioritizeDetected = false;

    /**
     * Drapeau pour limiter ou non les routes avec l'espace réservé {locale} vers App::$supportedLocales
     */
    protected bool $useSupportedLocalesOnly = false;

    /**
     * Le nom d'hôte actuel de $_SERVER['HTTP_HOST']
     */
    private ?string $httpHost = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->locator  = Services::locator();
        $this->httpHost = Services::request()->getEnv('HTTP_HOST');
    }

    /**
     * Charge le fichier des routes principales et découvre les routes.
     *
     * Charge une seule fois sauf réinitialisation.
     */
    public function loadRoutes(string $routesFile = CONFIG_PATH . 'routes.php'): self
    {
        if ($this->didDiscover) {
            return $this;
        }

        // Nous avons besoin de cette variable dans la portée locale pour que les fichiers de route puissent y accéder.
        $routes = $this;

        require $routesFile;

        $this->discoverRoutes();

        return $this;
    }

    /**
     * Réinitialisez les routes, afin qu'un cas de test puisse fournir le
     * ceux explicites nécessaires pour cela.
     */
    public function resetRoutes()
    {
        $this->routes = ['*' => []];

        foreach ($this->defaultHTTPMethods as $verb) {
            $this->routes[$verb] = [];
        }

        $this->prioritizeDetected = false;
        $this->didDiscover        = false;
    }

    /**
     * {@inheritDoc}
     */
    public function addPlaceholder($placeholder, ?string $pattern = null): self
    {
        if (! is_array($placeholder)) {
            $placeholder = [$placeholder => $pattern];
        }

        $this->placeholders = array_merge($this->placeholders, $placeholder);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultNamespace(string $value): self
    {
        $this->defaultNamespace = esc(strip_tags($value));
        $this->defaultNamespace = rtrim($this->defaultNamespace, '\\') . '\\';

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultController(string $value): self
    {
        $this->defaultController = esc(strip_tags($value));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultMethod(string $value): self
    {
        $this->defaultMethod = esc(strip_tags($value));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTranslateURIDashes(bool $value): self
    {
        $this->translateURIDashes = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setAutoRoute(bool $value): self
    {
        $this->autoRoute = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function set404Override($callable = null): self
    {
        $this->override404 = $callable;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get404Override()
    {
        return $this->override404;
    }

    /**
     * Tentera de découvrir d'éventuelles routes supplémentaires, soit par
     * les espaces de noms PSR4 locaux ou via des packages Composer sélectionnés.
     */
    protected function discoverRoutes()
    {
        if ($this->didDiscover) {
            return;
        }

        // Nous avons besoin de cette variable dans la portée locale pour que les fichiers de route puissent y accéder.
        $routes = $this;

        $files    = $this->locator->search('Config/routes.php');
        $excludes = [
            APP_PATH . 'Config' . DS . 'routes.php',
            SYST_PATH . 'Config' . DS . 'routes.php',
        ];

        foreach ($files as $file) {
            // N'incluez plus notre fichier principal...
            if (in_array($file, $excludes, true)) {
                continue;
            }

            include_once $file;
        }

        $this->didDiscover = true;
    }

    /**
     * Définit la contrainte par défaut à utiliser dans le système. Typiquement
     * à utiliser avec la méthode 'ressource'.
     */
    public function setDefaultConstraint(string $placeholder): self
    {
        if (array_key_exists($placeholder, $this->placeholders)) {
            $this->defaultPlaceholder = $placeholder;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultController(): string
    {
        return preg_replace('#Controller$#i', '', $this->defaultController) . 'Controller';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultMethod(): string
    {
        return $this->defaultMethod;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }

    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    /**
     *{@inheritDoc}
     */
    public function shouldTranslateURIDashes(): bool
    {
        return $this->translateURIDashes;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAutoRoute(): bool
    {
        return $this->autoRoute;
    }

    /**
     * Activer ou désactiver le tri des routes par priorité
     */
    public function setPrioritize(bool $enabled = true): self
    {
        $this->prioritize = $enabled;

        return $this;
    }

    /**
     * Définissez le drapeau qui limite ou non les routes avec l'espace réservé {locale} à App::$supportedLocales
     */
    public function useSupportedLocalesOnly(bool $useOnly): self
    {
        $this->useSupportedLocalesOnly = $useOnly;

        return $this;
    }

    /**
     * Obtenez le drapeau qui limite ou non les routes avec l'espace réservé {locale} vers App::$supportedLocales
     */
    public function shouldUseSupportedLocalesOnly(): bool
    {
        return $this->useSupportedLocalesOnly;
    }

    /**
     * {@inheritDoc}
     */
    public function getRegisteredControllers(?string $verb = '*'): array
    {
        $controllers = [];

        if ($verb === '*') {
            foreach ($this->defaultHTTPMethods as $tmpVerb) {
                foreach ($this->routes[$tmpVerb] as $route) {
                    $routeKey   = key($route['route']);
                    $controller = $this->getControllerName($route['route'][$routeKey]);
                    if ($controller !== null) {
                        $controllers[] = $controller;
                    }
                }
            }
        } else {
            $routes = $this->getRoutes($verb);

            foreach ($routes as $handler) {
                $controller = $this->getControllerName($handler);
                if ($controller !== null) {
                    $controllers[] = $controller;
                }
            }
        }

        return array_unique($controllers);
    }

    /**
     * {@inheritDoc}
     */
    public function getRoutes(?string $verb = null, bool $withName = false): array
    {
        if (empty($verb)) {
            $verb = $this->getHTTPVerb();
        }
        $verb = strtolower($verb);

        // Puisqu'il s'agit du point d'entrée du routeur,
        // prenez un moment pour faire toute découverte de route
        // que nous pourrions avoir besoin de faire.
        $this->discoverRoutes();

        $routes     = [];
        $collection = [];

        if (isset($this->routes[$verb])) {
            // Conserve les itinéraires du verbe actuel au début afin qu'ils soient
            // mis en correspondance avant l'un des itinéraires génériques "add".
            $collection = $this->routes[$verb] + ($this->routes['*'] ?? []);

            foreach ($collection as $name => $r) {
                $key = key($r['route']);

                if (! $withName) {
                    $routes[$key] = $r['route'][$key];
                } else {
                    $routes[$key] = [
                        'name'    => $name,
                        'handler' => $r['route'][$key],
                    ];
                }
            }
        }

        // tri des routes par priorité
        if ($this->prioritizeDetected && $this->prioritize && $routes !== []) {
            $order = [];

            foreach ($routes as $key => $value) {
                $key                    = $key === '/' ? $key : ltrim($key, '/ ');
                $priority               = $this->getRoutesOptions($key, $verb)['priority'] ?? 0;
                $order[$priority][$key] = $value;
            }

            ksort($order);
            $routes = array_merge(...$order);
        }

        return $routes;
    }

    /**
     * Renvoie une ou toutes les options d'itinéraire
     */
    public function getRoutesOptions(?string $from = null, ?string $verb = null): array
    {
        $options = $this->loadRoutesOptions($verb);

        return $from ? $options[$from] ?? [] : $options;
    }

    /**
     * {@inheritDoc}
     */
    public function getHTTPVerb(): string
    {
        return $this->HTTPVerb;
    }

    /**
     * {@inheritDoc}
     */
    public function setHTTPVerb(string $verb): self
    {
        $this->HTTPVerb = $verb;

        return $this;
    }

    /**
     * Une méthode de raccourci pour ajouter un certain nombre d'itinéraires en une seule fois.
     * Il ne permet pas de définir des options sur l'itinéraire, ou de
     * définir la méthode utilisée.
     */
    public function map(array $routes = [], ?array $options = null): self
    {
        foreach ($routes as $from => $to) {
            $this->add($from, $to, $options);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function add(string $from, $to, ?array $options = null): self
    {
        $this->create('*', $from, $to, $options);

        return $this;
    }

    /**
     * Ajoute une redirection temporaire d'une route à une autre. Utilisé pour
     * rediriger le trafic des anciennes routes inexistantes vers les nouvelles
     * itinéraires déplacés.
     *
     * @param string $from   Le modèle à comparer
     * @param string $to     Soit un nom de route ou un URI vers lequel rediriger
     * @param int    $status Le code d'état HTTP qui doit être renvoyé avec cette redirection
     */
    public function addRedirect(string $from, string $to, int $status = 302): self
    {
        // Utilisez le modèle de la route nommée s'il s'agit d'une route nommée.
        if (array_key_exists($to, $this->routes['*'])) {
            $to = $this->routes['*'][$to]['route'];
        } elseif (array_key_exists($to, $this->routes['get'])) {
            $to = $this->routes['get'][$to]['route'];
        }

        $this->create('*', $from, $to, ['redirect' => $status]);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isRedirect(string $from): bool
    {
        foreach ($this->routes['*'] as $name => $route) {
            // Est-ce une route nommée ?
            if ($name === $from || key($route['route']) === $from) {
                return isset($route['redirect']) && is_numeric($route['redirect']);
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getRedirectCode(string $from): int
    {
        foreach ($this->routes['*'] as $name => $route) {
            // Est-ce une route nommée ?
            if ($name === $from || key($route['route']) === $from) {
                return $route['redirect'] ?? 0;
            }
        }

        return 0;
    }

    /**
     * Regroupez une série de routes sous un seul segment d'URL. C'est pratique
     * pour regrouper des éléments dans une zone d'administration, comme :
     *
     * Example:
     *     // Creates route: admin/users
     *     $route->group('admin', function() {
     *            $route->resource('users');
     *     });
     *
     * @param string         $name      Le nom avec lequel grouper/préfixer les routes.
     * @param array|callable ...$params
     */
    public function group(string $name, ...$params)
    {
        $oldGroup   = $this->group;
        $oldOptions = $this->currentOptions;

        // Pour enregistrer une route, nous allons définir un indicateur afin que notre routeur
        // donc il verra le nom du groupe.
        // Si le nom du groupe est vide, nous continuons à utiliser le nom du groupe précédemment construit.
        $this->group = implode('/', array_unique(explode('/', $name ? ltrim($oldGroup . '/' . $name, '/') : $oldGroup)));

        $callback = array_pop($params);

        if ($params && is_array($params[0])) {
            $this->currentOptions = array_shift($params);
        }

        if (is_callable($callback)) {
            $callback($this);
        }

        $this->group          = $oldGroup;
        $this->currentOptions = $oldOptions;
    }

    /*
     * ------------------------------------------------- -------------------
     * Routage basé sur les verbes HTTP
     * ------------------------------------------------- -------------------
     * Le routage fonctionne ici car, comme le fichier de configuration des routes est lu,
     * les différentes routes basées sur le verbe HTTP ne seront ajoutées qu'à la mémoire en mémoire
     * routes s'il s'agit d'un appel qui doit répondre à ce verbe.
     *
     * Le tableau d'options est généralement utilisé pour transmettre un 'as' ou var, mais peut
     * être étendu à l'avenir. Voir le docblock pour la méthode 'add' ci-dessus pour
     * liste actuelle des options disponibles dans le monde.*/

    /**
     * Crée une collection d'itinéraires basés sur HTTP-verb pour un contrôleur.
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
    public function resource(string $name, ?array $options = null): self
    {
        // Afin de permettre la personnalisation de la route, le
        // les ressources sont envoyées à, nous devons avoir un nouveau nom
        // pour stocker les valeurs.
        $newName = implode('\\', array_map('ucfirst', explode('/', $name)));

        // Si un nouveau contrôleur est spécifié, alors nous remplaçons le
        // valeur de $name avec le nom du nouveau contrôleur.
        if (isset($options['controller'])) {
            $newName = ucfirst(esc(strip_tags($options['controller'])));
        }

        // Afin de permettre la personnalisation des valeurs d'identifiant autorisées
        // nous avons besoin d'un endroit pour les stocker.
        $id = $options['placeholder'] ?? $this->placeholders[$this->defaultPlaceholder] ?? '(:segment)';

        // On s'assure de capturer les références arrière
        $id = '(' . trim($id, '()') . ')';

        $methods = isset($options['only']) ? (is_string($options['only']) ? explode(',', $options['only']) : $options['only']) : ['index', 'show', 'create', 'update', 'delete', 'new', 'edit'];

        if (isset($options['except'])) {
            $options['except'] = is_array($options['except']) ? $options['except'] : explode(',', $options['except']);

            foreach ($methods as $i => $method) {
                if (in_array($method, $options['except'], true)) {
                    unset($methods[$i]);
                }
            }
        }

        if (in_array('index', $methods, true)) {
            $this->get($name, $newName . '::index', $options);
        }
        if (in_array('new', $methods, true)) {
            $this->get($name . '/new', $newName . '::new', $options);
        }
        if (in_array('edit', $methods, true)) {
            $this->get($name . '/' . $id . '/edit', $newName . '::edit/$1', $options);
        }
        if (in_array('show', $methods, true)) {
            $this->get($name . '/' . $id, $newName . '::show/$1', $options);
        }
        if (in_array('create', $methods, true)) {
            $this->post($name, $newName . '::create', $options);
        }
        if (in_array('update', $methods, true)) {
            $this->put($name . '/' . $id, $newName . '::update/$1', $options);
            $this->patch($name . '/' . $id, $newName . '::update/$1', $options);
        }
        if (in_array('delete', $methods, true)) {
            $this->delete($name . '/' . $id, $newName . '::delete/$1', $options);
        }

        // Websafe ? la suppression doit être vérifiée avant la mise à jour en raison du nom de la méthode
        if (isset($options['websafe'])) {
            if (in_array('delete', $methods, true)) {
                $this->post($name . '/' . $id . '/delete', $newName . '::delete/$1', $options);
            }
            if (in_array('update', $methods, true)) {
                $this->post($name . '/' . $id, $newName . '::update/$1', $options);
            }
        }

        return $this;
    }

    /**
     * Crée une collection de routes basées sur les verbes HTTP pour un contrôleur de présentateur.
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
    public function presenter(string $name, ?array $options = null): self
    {
        // Afin de permettre la personnalisation de la route, le
        // les ressources sont envoyées à, nous devons avoir un nouveau nom
        // pour stocker les valeurs.
        $newName = implode('\\', array_map('ucfirst', explode('/', $name)));

        // Si un nouveau contrôleur est spécifié, alors nous remplaçons le
        // valeur de $name avec le nom du nouveau contrôleur.
        if (isset($options['controller'])) {
            $newName = ucfirst(esc(strip_tags($options['controller'])));
        }

        // Afin de permettre la personnalisation des valeurs d'identifiant autorisées
        // nous avons besoin d'un endroit pour les stocker.
        $id = $options['placeholder'] ?? $this->placeholders[$this->defaultPlaceholder] ?? '(:segment)';

        // On s'assure de capturer les références arrière
        $id = '(' . trim($id, '()') . ')';

        $methods = isset($options['only']) ? (is_string($options['only']) ? explode(',', $options['only']) : $options['only']) : ['index', 'show', 'new', 'create', 'edit', 'update', 'remove', 'delete'];

        if (isset($options['except'])) {
            $options['except'] = is_array($options['except']) ? $options['except'] : explode(',', $options['except']);

            foreach ($methods as $i => $method) {
                if (in_array($method, $options['except'], true)) {
                    unset($methods[$i]);
                }
            }
        }

        if (in_array('index', $methods, true)) {
            $this->get($name, $newName . '::index', $options);
        }
        if (in_array('show', $methods, true)) {
            $this->get($name . '/show/' . $id, $newName . '::show/$1', $options);
        }
        if (in_array('new', $methods, true)) {
            $this->get($name . '/new', $newName . '::new', $options);
        }
        if (in_array('create', $methods, true)) {
            $this->post($name . '/create', $newName . '::create', $options);
        }
        if (in_array('edit', $methods, true)) {
            $this->get($name . '/edit/' . $id, $newName . '::edit/$1', $options);
        }
        if (in_array('update', $methods, true)) {
            $this->post($name . '/update/' . $id, $newName . '::update/$1', $options);
        }
        if (in_array('remove', $methods, true)) {
            $this->get($name . '/remove/' . $id, $newName . '::remove/$1', $options);
        }
        if (in_array('delete', $methods, true)) {
            $this->post($name . '/delete/' . $id, $newName . '::delete/$1', $options);
        }
        if (in_array('show', $methods, true)) {
            $this->get($name . '/' . $id, $newName . '::show/$1', $options);
        }
        if (in_array('create', $methods, true)) {
            $this->post($name, $newName . '::create', $options);
        }

        return $this;
    }

    /**
     * Spécifie une seule route à faire correspondre pour plusieurs verbes HTTP.
     *
     * Exemple:
     *  $route->match( ['get', 'post'], 'users/(:num)', 'users/$1);
     *
     * @param array|Closure|string $to
     */
    public function match(array $verbs = [], string $from = '', $to = '', ?array $options = null): self
    {
        if (empty($from) || empty($to)) {
            throw new InvalidArgumentException('Vous devez fournir les paramètres : $from, $to.');
        }

        foreach ($verbs as $verb) {
            $verb = strtolower($verb);

            $this->{$verb}($from, $to, $options);
        }

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes GET.
     *
     * @param array|Closure|string $to
     */
    public function get(string $from, $to, ?array $options = null): self
    {
        $this->create('get', $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes POST.
     *
     * @param array|Closure|string $to
     */
    public function post(string $from, $to, ?array $options = null): self
    {
        $this->create('post', $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes PUT.
     *
     * @param array|Closure|string $to
     */
    public function put(string $from, $to, ?array $options = null): self
    {
        $this->create('put', $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes DELETE.
     *
     * @param array|Closure|string $to
     */
    public function delete(string $from, $to, ?array $options = null): self
    {
        $this->create('delete', $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes HEAD.
     *
     * @param array|Closure|string $to
     */
    public function head(string $from, $to, ?array $options = null): self
    {
        $this->create('head', $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes PATCH.
     *
     * @param array|Closure|string $to
     */
    public function patch(string $from, $to, ?array $options = null): self
    {
        $this->create('patch', $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes OPTIONS.
     *
     * @param array|Closure|string $to
     */
    public function options(string $from, $to, ?array $options = null): self
    {
        $this->create('options', $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes de ligne de commande.
     *
     * @param array|Closure|string $to
     */
    public function cli(string $from, $to, ?array $options = null): self
    {
        $this->create('cli', $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'affichera qu'une vue.
     * Ne fonctionne que pour les requêtes GET.
     */
    public function view(string $from, string $view, ?array $options = null): self
    {
        $to = static fn (...$data) => Services::viewer()
            ->setData(['segments' => $data], 'raw')
            ->display($view)
            ->setOptions($options)
            ->render();

        $this->create('get', $from, $to, $options);

        return $this;
    }

    /**
     * Limite les itinéraires à un ENVIRONNEMENT spécifié ou ils ne fonctionneront pas.
     */
    public function environment(string $env, Closure $callback): self
    {
        if ($env === config('app.environment')) {
            $callback($this);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseRoute(string $search, ...$params)
    {
        // Les routes nommées ont une priorité plus élevée.
        foreach ($this->routes as $collection) {
            if (array_key_exists($search, $collection)) {
                return $this->buildReverseRoute(key($collection[$search]['route']), $params);
            }
        }

        // Ajoutez l'espace de noms par défaut si nécessaire.
        $namespace = trim($this->defaultNamespace, '\\') . '\\';
        if (
            substr($search, 0, 1) !== '\\'
            && substr($search, 0, strlen($namespace)) !== $namespace
        ) {
            $search = $namespace . $search;
        }

        // Si ce n'est pas une route nommée, alors bouclez
        // toutes les routes pour trouver une correspondance.
        foreach ($this->routes as $collection) {
            foreach ($collection as $route) {
                $from = key($route['route']);
                $to   = $route['route'][$from];

                // on ignore les closures
                if (! is_string($to)) {
                    continue;
                }

                // Perd toute barre oblique d'espace de noms au début des chaînes
                // pour assurer une correspondance plus cohérente.$to     = ltrim($to, '\\');
                $to     = ltrim($to, '\\');
                $search = ltrim($search, '\\');

                // S'il y a une chance de correspondance, alors ce sera
                // soit avec $search au début de la chaîne $to.
                if (strpos($to, $search) !== 0) {
                    continue;
                }

                // Assurez-vous que le nombre de $params donné ici
                // correspond au nombre de back-references dans la route
                if (substr_count($to, '$') !== count($params)) {
                    continue;
                }

                return $this->buildReverseRoute($from, $params);
            }
        }

        // Si nous sommes toujours là, alors nous n'avons pas trouvé de correspondance.
        return false;
    }

    /**
     * Vérifie une route (en utilisant le "from") pour voir si elle est filtrée ou non.
     */
    public function isFiltered(string $search, ?string $verb = null): bool
    {
        return $this->getFiltersForRoute($search, $verb) !== [];
    }

    /**
     * Renvoie les filtres qui doivent être appliqués pour un seul itinéraire, ainsi que
     * avec tous les paramètres qu'il pourrait avoir. Les paramètres sont trouvés en divisant
     * le nom du paramètre entre deux points pour séparer le nom du filtre de la liste des paramètres,
     * et le fractionnement du résultat sur des virgules. Alors:
     *
     *    'role:admin,manager'
     *
     * a un filtre de "rôle", avec des paramètres de ['admin', 'manager'].
     */
    public function getFiltersForRoute(string $search, ?string $verb = null): array
    {
        $options = $this->loadRoutesOptions($verb);

        $middlewares = $options[$search]['middlewares'] ?? (
            $options[$search]['middleware'] ?? ($options[$search]['filter'] ?? [])
        );

        return (array) $middlewares;
    }

    /**
     * Construit une route inverse
     *
     * @param array $params Un ou plusieurs paramètres à transmettre à la route.
     *                      Le dernier paramètre vous permet de définir la locale.
     */
    protected function buildReverseRoute(string $from, array $params): string
    {
        $locale = null;

        // Retrouvez l'ensemble de nos rétro-références dans le parcours d'origine.
        preg_match_all('/\(([^)]+)\)/', $from, $matches);

        if (empty($matches[0])) {
            if (strpos($from, '{locale}') !== false) {
                $locale = $params[0] ?? null;
            }

            $from = $this->replaceLocale($from, $locale);

            return '/' . ltrim($from, '/');
        }

        // Les paramètres régionaux sont passés ?
        $placeholderCount = count($matches[0]);
        if (count($params) > $placeholderCount) {
            $locale = $params[$placeholderCount];
        }

        // Construisez notre chaîne résultante, en insérant les $params aux endroits appropriés.
        foreach ($matches[0] as $index => $pattern) {
            if (! preg_match('#^' . $pattern . '$#u', $params[$index])) {
                throw RouterException::invalidParameterType();
            }

            // Assurez-vous que le paramètre que nous insérons correspond au type de paramètre attendu.
            $pos  = strpos($from, $pattern);
            $from = substr_replace($from, $params[$index], $pos, strlen($pattern));
        }

        $from = $this->replaceLocale($from, $locale);

        return '/' . ltrim($from, '/');
    }

    /**
     * Charger les options d'itinéraires en fonction du verbe
     */
    protected function loadRoutesOptions(?string $verb = null): array
    {
        $verb = $verb ?: $this->getHTTPVerb();

        $options = $this->routesOptions[$verb] ?? [];

        if (isset($this->routesOptions['*'])) {
            foreach ($this->routesOptions['*'] as $key => $val) {
                if (isset($options[$key])) {
                    $extraOptions  = array_diff_key($val, $options[$key]);
                    $options[$key] = array_merge($options[$key], $extraOptions);
                } else {
                    $options[$key] = $val;
                }
            }
        }

        return $options;
    }

    /**
     * Fait le gros du travail de création d'un itinéraire réel. Vous devez spécifier
     * la ou les méthodes de demande pour lesquelles cette route fonctionnera. Ils peuvent être séparés
     * par un caractère pipe "|" s'il y en a plusieurs.
     *
     * @param array|Closure|string $to
     */
    protected function create(string $verb, string $from, $to, ?array $options = null)
    {
        $overwrite = false;
        $prefix    = $this->group === null ? '' : $this->group . '/';

        $from = esc(strip_tags($prefix . $from));

        // Alors que nous voulons ajouter une route dans un groupe de '/',
        // ça ne marche pas avec la correspondance, alors supprimez-les...
        if ($from !== '/') {
            $from = trim($from, '/');
        }

        if (is_string($to) && strpos($to, '::') === false && class_exists($to) && method_exists($to, '__invoke')) {
            $to = [$to, '__invoke'];
        }

        // Lors de la redirection vers une route nommée, $to est un tableau tel que `['zombies' => '\Zombies::index']`.
        if (is_array($to) && count($to) === 2) {
            $to = $this->processArrayCallableSyntax($from, $to);
        }

        $options = array_merge($this->currentOptions ?? [], $options ?? []);

        // Détection de priorité de routage
        if (isset($options['priority'])) {
            $options['priority'] = abs((int) $options['priority']);

            if ($options['priority'] > 0) {
                $this->prioritizeDetected = true;
            }
        }

        // Limitation du nom d'hôte ?
        if (! empty($options['hostname'])) {
            // @todo déterminer s'il existe un moyen de mettre les hôtes sur liste blanche ?
            if (! $this->checkHostname($options['hostname'])) {
                return;
            }

            $overwrite = true;
        }

        // Limitation du nom sous-domaine ?
        elseif (! empty($options['subdomain'])) {
            // Si nous ne correspondons pas au sous-domaine actuel, alors
            // nous n'avons pas besoin d'ajouter la route.
            if (! $this->checkSubdomains($options['subdomain'])) {
                return;
            }

            $overwrite = true;
        }

        // Sommes-nous en train de compenser les liaisons ?
        // Si oui, occupez-vous d'eux ici en un
        // abattre en plein vol.
        if (isset($options['offset']) && is_string($to)) {
            // Récupère une chaîne constante avec laquelle travailler.
            $to = preg_replace('/(\$\d+)/', '$X', $to);

            for ($i = (int) $options['offset'] + 1; $i < (int) $options['offset'] + 7; $i++) {
                $to = preg_replace_callback(
                    '/\$X/',
                    static fn ($m) => '$' . $i,
                    $to,
                    1
                );
            }
        }

        // Remplacez nos espaces réservés de regex par la chose réelle
        // pour que le routeur n'ait pas besoin de savoir quoi que ce soit.
        foreach ($this->placeholders as $tag => $pattern) {
            $from = str_ireplace(':' . $tag, $pattern, $from);
        }

        // S'il s'agit d'une redirection, aucun traitement
        if (! isset($options['redirect']) && is_string($to)) {
            // Si aucun espace de noms n'est trouvé, ajouter l'espace de noms par défaut
            if (strpos($to, '\\') === false || strpos($to, '\\') > 0) {
                $namespace = $options['namespace'] ?? $this->defaultNamespace;
                $to        = trim($namespace, '\\') . '\\' . $to;
            }
            // Assurez-vous toujours que nous échappons à notre espace de noms afin de ne pas pointer vers
            // \BlitzPHP\Routes\Controller::method.
            $to = '\\' . ltrim($to, '\\');
        }

        $name = $options['as'] ?? $from;

        // Ne remplacez aucun 'from' existant afin que les routes découvertes automatiquement
        // n'écrase pas les paramètres app/Config/Routes.
        // les routes manuelement définies doivent toujours être la "source de vérité".
        // cela ne fonctionne que parce que les routes découvertes sont ajoutées juste avant
        // pour tenter de router la requête.
        if (isset($this->routes[$verb][$name]) && ! $overwrite) {
            return;
        }

        $this->routes[$verb][$name] = [
            'route' => [$from => $to],
        ];

        $this->routesOptions[$verb][$from] = $options;

        // C'est une redirection ?
        if (isset($options['redirect']) && is_numeric($options['redirect'])) {
            $this->routes['*'][$name]['redirect'] = $options['redirect'];
        }
    }

    /**
     * Compare le nom d'hôte transmis avec le nom d'hôte actuel sur cette demande de page.
     *
     * @param string $hostname Nom d'hôte dans les options d'itinéraire
     */
    private function checkHostname(string $hostname): bool
    {
        // Les appels CLI ne peuvent pas être sur le nom d'hôte.
        if (! isset($this->httpHost) || is_cli()) {
            return false;
        }

        return strtolower($this->httpHost) === strtolower($hostname);
    }

    /**
     * Compare le ou les sous-domaines transmis avec le sous-domaine actuel
     * sur cette page demande.
     *
     * @param mixed $subdomains
     */
    private function checkSubdomains($subdomains): bool
    {
        // Les appels CLI ne peuvent pas être sur le sous-domaine.
        if (! isset($_SERVER['HTTP_HOST'])) {
            return false;
        }

        if ($this->currentSubdomain === null) {
            $this->currentSubdomain = $this->determineCurrentSubdomain();
        }

        if (! is_array($subdomains)) {
            $subdomains = [$subdomains];
        }

        // Les routes peuvent être limitées à n'importe quel sous-domaine. Dans ce cas, cependant,
        // il nécessite la présence d'un sous-domaine.
        if (! empty($this->currentSubdomain) && in_array('*', $subdomains, true)) {
            return true;
        }

        return in_array($this->currentSubdomain, $subdomains, true);
    }

    /**
     * Examine le HTTP_HOST pour obtenir une meilleure correspondance pour le sous-domaine. Ce
     * ne sera pas parfait, mais devrait répondre à nos besoins.
     *
     * Ce n'est surtout pas parfait puisqu'il est possible d'enregistrer un domaine
     * avec un point (.) dans le cadre du nom de domaine.
     *
     * @return mixed
     */
    private function determineCurrentSubdomain()
    {
        // Nous devons nous assurer qu'un schéma existe
        // sur l'URL sinon parse_url sera mal interprété
        // 'hôte' comme 'chemin'.
        $url = $this->httpHost;
        if (strpos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }

        $parsedUrl = parse_url($url);

        $host = explode('.', $parsedUrl['host']);

        if ($host[0] === 'www') {
            unset($host[0]);
        }

        // Débarrassez-vous de tous les domaines, qui seront les derniers
        unset($host[count($host) - 1]);

        // Compte pour les domaines .co.uk, .co.nz, etc.
        if (end($host) === 'co') {
            $host = array_slice($host, 0, -1);
        }

        // S'il ne nous reste qu'une partie, alors nous n'avons pas de sous-domaine.
        if (count($host) === 1) {
            // Définissez-le sur false pour ne pas revenir ici.
            return false;
        }

        return array_shift($host);
    }

    private function getControllerName(Closure|string $handler): ?string
    {
        if (! is_string($handler)) {
            return null;
        }

        [$controller] = explode('::', $handler, 2);

        return $controller;
    }

    /**
     * Renvoie la chaîne de paramètres de méthode comme `/$1/$2` pour les espaces réservés
     */
    private function getMethodParams(string $from): string
    {
        preg_match_all('/\(.+?\)/', $from, $matches);
        $count = is_countable($matches[0]) ? count($matches[0]) : 0;

        $params = '';

        for ($i = 1; $i <= $count; $i++) {
            $params .= '/$' . $i;
        }

        return $params;
    }

    private function processArrayCallableSyntax(string $from, array $to): string
    {
        // [classname, method]
        // eg, [Home::class, 'index']
        if (is_callable($to, true, $callableName)) {
            // Si la route a des espaces réservés, ajoutez des paramètres automatiquement.
            $params = $this->getMethodParams($from);

            if (strpos($callableName, '\\') !== false && $callableName[0] !== '\\') {
                $callableName = '\\' . $callableName;
            }

            return $callableName . $params;
        }

        // [[classname, method], params]
        // eg, [[Home::class, 'index'], '$1/$2']
        if (
            isset($to[0], $to[1])
            && is_callable($to[0], true, $callableName)
            && is_string($to[1])
        ) {
            $to = '\\' . $callableName . '/' . $to[1];
        }

        return $to;
    }

    /**
     * Remplace la balise {locale} par la locale.
     */
    private function replaceLocale(string $route, ?string $locale = null): string
    {
        if (strpos($route, '{locale}') === false) {
            return $route;
        }

        // Vérifier les paramètres régionaux non valides
        if ($locale !== null) {
            if (! in_array($locale, config('app.supported_locales'), true)) {
                $locale = null;
            }
        }

        if ($locale === null) {
            $locale = Services::request()->getLocale();
        }

        return strtr($route, ['{locale}' => $locale]);
    }
}
