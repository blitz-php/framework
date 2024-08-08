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

use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Enums\Method;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Utilities\String\Text;
use Closure;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

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
    protected bool $autoRoute = false;

    /**
     * Un appelable qui sera affiché
     * lorsque la route ne peut pas être matchée.
     *
     * @var (Closure(string): (ResponseInterface|string|void))|string
     */
    protected $override404;

    /**
     * Tableau de fichiers qui contiendrait les définitions de routes.
     */
    protected array $routeFiles = [];

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
        'uuid'     => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
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
        '*'             => [],
        Method::OPTIONS => [],
        Method::GET     => [],
        Method::HEAD    => [],
        Method::POST    => [],
        Method::PATCH   => [],
        Method::PUT     => [],
        Method::DELETE  => [],
        Method::TRACE   => [],
        Method::CONNECT => [],
        'CLI'           => [],
    ];

    /**
     * Tableau des noms des routes
     *
     * [
     *     verb => [
     *         routeName => routeKey(regex)
     *     ],
     * ]
     */
    protected array $routesNames = [
        '*'             => [],
        Method::OPTIONS => [],
        Method::GET     => [],
        Method::HEAD    => [],
        Method::POST    => [],
        Method::PATCH   => [],
        Method::PUT     => [],
        Method::DELETE  => [],
        Method::TRACE   => [],
        Method::CONNECT => [],
        'CLI'           => [],
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
    protected array $defaultHTTPMethods = Router::HTTP_METHODS;

    /**
     * Le nom du groupe de route courant
     */
    protected ?string $group = null;

    /**
     * Le sous domaine courant
     */
    protected ?string $currentSubdomain = null;

    /**
     * Stocke une copie des options actuelles en cours appliqué lors de la création.
     */
    protected ?array $currentOptions = null;

    /**
     * Un petit booster de performances.
     */
    protected bool $didDiscover = false;

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
     *
     * @param LocatorInterface $locator Descripteur du localisateur de fichiers à utiliser.
     */
    public function __construct(protected LocatorInterface $locator, object $routing)
    {
        $this->httpHost = env('HTTP_HOST');

        // Configuration basée sur le fichier de config. Laissez le fichier routes substituer.
        $this->defaultNamespace   = rtrim($routing->default_namespace ?: $this->defaultNamespace, '\\') . '\\';
        $this->defaultController  = $routing->default_controller ?: $this->defaultController;
        $this->defaultMethod      = $routing->default_method ?: $this->defaultMethod;
        $this->translateURIDashes = $routing->translate_uri_dashes ?: $this->translateURIDashes;
        $this->override404        = $routing->fallback ?: $this->override404;
        $this->autoRoute          = $routing->auto_route ?: $this->autoRoute;
        $this->routeFiles         = $routing->route_files ?: $this->routeFiles;
        $this->prioritize         = $routing->prioritize ?: $this->prioritize;

        // Normaliser la chaîne de path dans le tableau routeFiles.
        foreach ($this->routeFiles as $routeKey => $routesFile) {
            $this->routeFiles[$routeKey] = realpath($routesFile) ?: $routesFile;
        }
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

        // Normaliser la chaîne de chemin dans routesFile
        $routesFile = realpath($routesFile) ?: $routesFile;

        // Incluez le fichier routesFile s'il n'existe pas.
        // Ne conserver que pour les fins BC pour l'instant.
        $routeFiles = $this->routeFiles;
        if (! in_array($routesFile, $routeFiles, true)) {
            $routeFiles[] = $routesFile;
        }

        // Nous avons besoin de cette variable dans la portée locale pour que les fichiers de route puissent y accéder.
        $routes = $this;

        foreach ($routeFiles as $routesFile) {
            if (! is_file($routesFile)) {
                logger()->warning(sprintf('Fichier de route introuvable : "%s"', $routesFile));

                continue;
            }

            require_once $routesFile;
        }

        $this->discoverRoutes();

        return $this;
    }

    /**
     * Réinitialisez les routes, afin qu'un cas de test puisse fournir le
     * ceux explicites nécessaires pour cela.
     */
    public function resetRoutes()
    {
        $this->routes = $this->routesNames = ['*' => []];

        foreach ($this->defaultHTTPMethods as $verb) {
            $this->routes[$verb]      = [];
            $this->routesNames[$verb] = [];
        }

        $this->routesOptions = [];

        $this->prioritizeDetected = false;
        $this->didDiscover        = false;
    }

    /**
     * {@inheritDoc}
     *
     * Utilisez `placeholder` a la place
     */
    public function addPlaceholder($placeholder, ?string $pattern = null): self
    {
        return $this->placeholder($placeholder, $pattern);
    }

    /**
     * Enregistre une nouvelle contrainte auprès du système.
     * Les contraintes sont utilisées par les routes en tant qu'espaces réservés pour les expressions régulières afin de définir les parcours plus humains.
     */
    public function placeholder(array|string $placeholder, ?string $pattern = null): self
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
     *
     * Utilisez self::fallback()
     */
    public function set404Override($callable = null): self
    {
        return $this->fallback($callable);
    }

    /**
     * Définit la classe/méthode qui doit être appelée si le routage ne trouver pas une correspondance.
     *
     * @param callable|string|null $callable
     */
    public function fallback($callable = null): self
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

        $files = $this->locator->search('Config/routes.php');

        foreach ($files as $file) {
            // N'incluez plus notre fichier principal...
            if (in_array($file, $this->routeFiles, true)) {
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

    /**
     * Pour `klinge route:list`
     *
     * @return array<string, string>
     *
     * @internal
     */
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
     *
     * @internal
     */
    public function getRegisteredControllers(?string $verb = '*'): array
    {
        $controllers = [];

        if ($verb === '*') {
            foreach ($this->defaultHTTPMethods as $tmpVerb) {
                foreach ($this->routes[$tmpVerb] as $route) {
                    $controller = $this->getControllerName($route['handler']);
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
    public function getRoutes(?string $verb = null, bool $includeWildcard = true): array
    {
        if ($verb === null || $verb === '') {
            $verb = $this->getHTTPVerb();
        }

        // Puisqu'il s'agit du point d'entrée du routeur,
        // prenez un moment pour faire toute découverte de route
        // que nous pourrions avoir besoin de faire.
        $this->discoverRoutes();

        $routes = [];
        if (isset($this->routes[$verb])) {
            // Conserve les itinéraires du verbe actuel au début afin qu'ils soient
            // mis en correspondance avant l'un des itinéraires génériques "add".
            $collection = $includeWildcard ? $this->routes[$verb] + ($this->routes['*'] ?? []) : $this->routes[$verb];

            foreach ($collection as $routeKey => $r) {
                $routes[$routeKey] = $r['handler'];
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
        $this->HTTPVerb = strtoupper($verb);

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
     * Ajoute une redirection temporaire d'une route à une autre.
     * Utilisé pour rediriger le trafic des anciennes routes inexistantes vers les nouvelles routes déplacés.
     *
     * @param string $from   Le modèle à comparer
     * @param string $to     Soit un nom de route ou un URI vers lequel rediriger
     * @param int    $status Le code d'état HTTP qui doit être renvoyé avec cette redirection
     */
    public function redirect(string $from, string $to, int $status = 302): self
    {
        // Utilisez le modèle de la route nommée s'il s'agit d'une route nommée.
        if (array_key_exists($to, $this->routesNames['*'])) {
            $routeName  = $to;
            $routeKey   = $this->routesNames['*'][$routeName];
            $redirectTo = [$routeKey => $this->routes['*'][$routeKey]['handler']];
        } elseif (array_key_exists($to, $this->routesNames[Method::GET])) {
            $routeName  = $to;
            $routeKey   = $this->routesNames[Method::GET][$routeName];
            $redirectTo = [$routeKey => $this->routes[Method::GET][$routeKey]['handler']];
        } else {
            // La route nommee n'a pas ete trouvée
            $redirectTo = $to;
        }

        $this->create('*', $from, $redirectTo, ['redirect' => $status]);

        return $this;
    }

    /**
     * Ajoute une redirection permanente d'une route à une autre.
     * Utilisé pour rediriger le trafic des anciennes routes inexistantes vers les nouvelles routes déplacés.
     */
    public function permanentRedirect(string $from, string $to): self
    {
        return $this->redirect($from, $to, 301);
    }

    /**
     * @deprecated 0.9 Please use redirect() instead
     */
    public function addRedirect(string $from, string $to, int $status = 302): self
    {
        return $this->redirect($from, $to, $status);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $routeKey cle de route ou route nommee
     */
    public function isRedirect(string $routeKey): bool
    {
        return isset($this->routes['*'][$routeKey]['redirect']);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $routeKey cle de route ou route nommee
     */
    public function getRedirectCode(string $routeKey): int
    {
        return $this->routes['*'][$routeKey]['redirect'] ?? 0;
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
        $oldGroup   = $this->group ?: '';
        $oldOptions = $this->currentOptions;

        // Pour enregistrer une route, nous allons définir un indicateur afin que notre routeur
        // donc il verra le nom du groupe.
        // Si le nom du groupe est vide, nous continuons à utiliser le nom du groupe précédemment construit.
        $this->group = $name ? trim($oldGroup . '/' . $name, '/') : $oldGroup;

        $callback = array_pop($params);

        if ($params && is_array($params[0])) {
            $options = array_shift($params);

            if (isset($options['middlewares']) || isset($options['middleware'])) {
                $currentMiddlewares     = (array) ($this->currentOptions['middlewares'] ?? []);
                $options['middlewares'] = array_merge($currentMiddlewares, (array) ($options['middlewares'] ?? $options['middleware']));
            }

            // Fusionner les options autres que les middlewares.
            $this->currentOptions = array_merge(
                $this->currentOptions ?: [],
                $options ?: [],
            );
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
     * @param string $name    Le nom de la ressource/du contrôleur vers lequel router.
     * @param array  $options Une liste des façons possibles de personnaliser le routage.
     */
    public function resource(string $name, array $options = []): self
    {
        // Afin de permettre la personnalisation de la route, le
        // les ressources sont envoyées à, nous devons avoir un nouveau nom
        // pour stocker les valeurs.
        $newName = implode('\\', array_map('ucfirst', explode('/', $name)));

        // Si un nouveau contrôleur est spécifié, alors nous remplaçons le
        // valeur de $name avec le nom du nouveau contrôleur.
        if (isset($options['controller'])) {
            $newName = ucfirst(esc(strip_tags($options['controller'])));
            unset($options['controller']);
        }

        $newName = Text::convertTo($newName, 'pascalcase');

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

        $routeName = $name;
        if (isset($options['as']) || isset($options['name'])) {
            $routeName = trim($options['as'] ?? $options['name'], ' .');
            unset($options['name'], $options['as']);
        }

        if (in_array('index', $methods, true)) {
            $this->get($name, $newName . '::index', $options + [
                'as' => $routeName . '.index',
            ]);
        }
        if (in_array('new', $methods, true)) {
            $this->get($name . '/new', $newName . '::new', $options + [
                'as' => $routeName . '.new',
            ]);
        }
        if (in_array('edit', $methods, true)) {
            $this->get($name . '/' . $id . '/edit', $newName . '::edit/$1', $options + [
                'as' => $routeName . '.edit',
            ]);
        }
        if (in_array('show', $methods, true)) {
            $this->get($name . '/' . $id, $newName . '::show/$1', $options + [
                'as' => $routeName . '.show',
            ]);
        }
        if (in_array('create', $methods, true)) {
            $this->post($name, $newName . '::create', $options + [
                'as' => $routeName . '.create',
            ]);
        }
        if (in_array('update', $methods, true)) {
            $this->match(['put', 'patch'], $name . '/' . $id, $newName . '::update/$1', $options + [
                'as' => $routeName . '.update',
            ]);
        }
        if (in_array('delete', $methods, true)) {
            $this->delete($name . '/' . $id, $newName . '::delete/$1', $options + [
                'as' => $routeName . '.delete',
            ]);
        }

        // Websafe ? la suppression doit être vérifiée avant la mise à jour en raison du nom de la méthode
        if (isset($options['websafe'])) {
            if (in_array('delete', $methods, true)) {
                $this->post($name . '/' . $id . '/delete', $newName . '::delete/$1', $options + [
                    'as' => $routeName . '.delete',
                ]);
            }
            if (in_array('update', $methods, true)) {
                $this->post($name . '/' . $id, $newName . '::update/$1', $options + [
                    'as' => $routeName . '.update',
                ]);
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
     * @param string $name    Le nom du contrôleur vers lequel router.
     * @param array  $options Une liste des façons possibles de personnaliser le routage.
     */
    public function presenter(string $name, array $options = []): self
    {
        // Afin de permettre la personnalisation de la route, le
        // les ressources sont envoyées à, nous devons avoir un nouveau nom
        // pour stocker les valeurs.
        $newName = implode('\\', array_map('ucfirst', explode('/', $name)));

        // Si un nouveau contrôleur est spécifié, alors nous remplaçons le
        // valeur de $name avec le nom du nouveau contrôleur.
        if (isset($options['controller'])) {
            $newName = ucfirst(esc(strip_tags($options['controller'])));
            unset($options['controller']);
        }

        $newName = Text::convertTo($newName, 'pascalcase');

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

        $routeName = $name;
        if (isset($options['as']) || isset($options['name'])) {
            $routeName = trim($options['as'] ?? $options['name'], ' .');
            unset($options['name'], $options['as']);
        }

        if (in_array('index', $methods, true)) {
            $this->get($name, $newName . '::index', $options + [
                'as' => $routeName . '.index',
            ]);
        }
        if (in_array('new', $methods, true)) {
            $this->get($name . '/new', $newName . '::new', $options + [
                'as' => $routeName . '.new',
            ]);
        }
        if (in_array('edit', $methods, true)) {
            $this->get($name . '/edit/' . $id, $newName . '::edit/$1', $options + [
                'as' => $routeName . '.edit',
            ]);
        }
        if (in_array('update', $methods, true)) {
            $this->post($name . '/update/' . $id, $newName . '::update/$1', $options + [
                'as' => $routeName . '.update',
            ]);
        }
        if (in_array('remove', $methods, true)) {
            $this->get($name . '/remove/' . $id, $newName . '::remove/$1', $options + [
                'as' => $routeName . '.remove',
            ]);
        }
        if (in_array('delete', $methods, true)) {
            $this->post($name . '/delete/' . $id, $newName . '::delete/$1', $options + [
                'as' => $routeName . '.delete',
            ]);
        }
        if (in_array('create', $methods, true)) {
            $this->post($name . '/create', $newName . '::create', $options + [
                'as' => $routeName . '.create',
            ]);
            $this->post($name, $newName . '::create', $options + [
                'as' => $routeName . '.store',
            ]);
        }
        if (in_array('show', $methods, true)) {
            $this->get($name . '/show/' . $id, $newName . '::show/$1', $options + [
                'as' => $routeName . '.view',
            ]);
            $this->get($name . '/' . $id, $newName . '::show/$1', $options + [
                'as' => $routeName . '.show',
            ]);
        }

        return $this;
    }

    /**
     * Spécifie une seule route à faire correspondre pour plusieurs verbes HTTP.
     *
     * Exemple:
     *  $route->match( ['get', 'post'], 'users/(:num)', 'users/$1);
     *
     * @param array|(Closure(mixed...): (ResponseInterface|string|void))|string $to
     */
    public function match(array $verbs = [], string $from = '', $to = '', ?array $options = null): self
    {
        if ($from === '' || empty($to)) {
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
     * @param array|(Closure(mixed...): (ResponseInterface|string|void))|string $to
     */
    public function get(string $from, $to, ?array $options = null): self
    {
        $this->create(Method::GET, $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes POST.
     *
     * @param array|(Closure(mixed...): (ResponseInterface|string|void))|string $to
     */
    public function post(string $from, $to, ?array $options = null): self
    {
        $this->create(Method::POST, $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes PUT.
     *
     * @param array|(Closure(mixed...): (ResponseInterface|string|void))|string $to
     */
    public function put(string $from, $to, ?array $options = null): self
    {
        $this->create(Method::PUT, $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes DELETE.
     *
     * @param array|(Closure(mixed...): (ResponseInterface|string|void))|string $to
     */
    public function delete(string $from, $to, ?array $options = null): self
    {
        $this->create(Method::DELETE, $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes HEAD.
     *
     * @param array|(Closure(mixed...): (ResponseInterface|string|void))|string $to
     */
    public function head(string $from, $to, ?array $options = null): self
    {
        $this->create(Method::HEAD, $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes PATCH.
     *
     * @param array|(Closure(mixed...): (ResponseInterface|string|void))|string $to
     */
    public function patch(string $from, $to, ?array $options = null): self
    {
        $this->create(Method::PATCH, $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes OPTIONS.
     *
     * @param array|(Closure(mixed...): (ResponseInterface|string|void))|string $to
     */
    public function options(string $from, $to, ?array $options = null): self
    {
        $this->create(Method::OPTIONS, $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes GET et POST.
     *
     * @param array|(Closure(mixed...): (ResponseInterface|string|void))|string $to
     */
    public function form(string $from, $to, ?array $options = null): self
    {
        return $this->match([Method::GET, Method::POST], $from, $to, $options);
    }

    /**
     * Spécifie une route qui n'est disponible que pour les requêtes de ligne de commande.
     *
     * @param array|(Closure(mixed...): (ResponseInterface|string|void))|string $to
     */
    public function cli(string $from, $to, ?array $options = null): self
    {
        $this->create('CLI', $from, $to, $options);

        return $this;
    }

    /**
     * Spécifie une route qui n'affichera qu'une vue.
     * Ne fonctionne que pour les requêtes GET.
     */
    public function view(string $from, string $view, array $options = []): self
    {
        $to = static fn (...$data) => Services::viewer()
            ->setData(['segments' => $data], 'raw')
            ->display($view)
            ->options($options)
            ->render();

        $routeOptions = array_merge($options, ['view' => $view]);

        $this->create(Method::GET, $from, $to, $routeOptions);

        return $this;
    }

    /**
     * Limite les itinéraires à un ENVIRONNEMENT spécifié ou ils ne fonctionneront pas.
     */
    public function environment(string $env, Closure $callback): self
    {
        if (environment($env)) {
            $callback($this);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseRoute(string $search, ...$params)
    {
        if ($search === '') {
            return false;
        }

        $queries = [];

        if (is_array($last = array_pop($params))) {
            $queries = $last;
        } elseif (null !== $last) {
            $params[] = $last;
        }

        $name = $this->formatRouteName($search);

        // Les routes nommées ont une priorité plus élevée.
        foreach ($this->routesNames as $verb => $collection) {
            if (array_key_exists($name, $collection)) {
                $routeKey = $collection[$name];

                $from = $this->routes[$verb][$routeKey]['from'];

                return $this->buildReverseRoute($from, $params, $queries);
            }
        }

        // Ajoutez l'espace de noms par défaut si nécessaire.
        $namespace = trim($this->defaultNamespace, '\\') . '\\';
        if (
            ! str_starts_with($search, '\\')
            && ! str_starts_with($search, $namespace)
        ) {
            $search = $namespace . $search;
        }

        // Si ce n'est pas une route nommée, alors bouclez
        // toutes les routes pour trouver une correspondance.
        foreach ($this->routes as $collection) {
            foreach ($collection as $route) {
                $to   = $route['handler'];
                $from = $route['from'];

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
                if (! str_starts_with($to, $search)) {
                    continue;
                }

                // Assurez-vous que le nombre de $params donné ici
                // correspond au nombre de back-references dans la route
                if (substr_count($to, '$') !== count($params)) {
                    continue;
                }

                return $this->buildReverseRoute($from, $params, $queries);
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

        $middlewares = $options[$search]['middlewares'] ?? ($options[$search]['middleware'] ?? []);

        return (array) $middlewares;
    }

    /**
     * Construit une route inverse
     *
     * @param array $params Un ou plusieurs paramètres à transmettre à la route.
     *                      Le dernier paramètre vous permet de définir la locale.
     */
    protected function buildReverseRoute(string $from, array $params, array $queries = []): string
    {
        $locale = null;

        // Retrouvez l'ensemble de nos rétro-références dans le parcours d'origine.
        preg_match_all('/\(([^)]+)\)/', $from, $matches);

        if (empty($matches[0])) {
            if (str_contains($from, '{locale}')) {
                $locale = $params[0] ?? null;
            }

            $from = '/' . ltrim($this->replaceLocale($from, $locale), '/');

            if ($queries !== []) {
                $from .= '?' . http_build_query($queries);
            }

            return $from;
        }

        // Les paramètres régionaux sont passés ?
        $placeholderCount = count($matches[0]);
        if (count($params) > $placeholderCount) {
            $locale = $params[$placeholderCount];
        }

        // Construisez notre chaîne résultante, en insérant les $params aux endroits appropriés.
        foreach ($matches[0] as $index => $placeholder) {
            if (! isset($params[$index])) {
                throw new InvalidArgumentException(
                    'Argument manquant pour "' . $placeholder . '" dans la route "' . $from . '".'
                );
            }

            // Supprimez `(:` et `)` lorsque $placeholder est un espace réservé.
            $placeholderName = substr($placeholder, 2, -1);
            // ou peut-être que $placeholder n'est pas un espace réservé, mais une regex.
            $pattern = $this->placeholders[$placeholderName] ?? $placeholder;

            if (! preg_match('#^' . $pattern . '$#u', (string) $params[$index])) {
                throw RouterException::invalidParameterType();
            }

            // Assurez-vous que le paramètre que nous insérons correspond au type de paramètre attendu.
            $pos  = strpos($from, $placeholder);
            $from = substr_replace($from, $params[$index], $pos, strlen($placeholder));
        }

        $from = '/' . ltrim($this->replaceLocale($from, $locale), '/');

        if ($queries !== []) {
            $from .= '?' . http_build_query($queries);
        }

        return $from;
    }

    /**
     * Charger les options d'itinéraires en fonction du verbe
     */
    protected function loadRoutesOptions(?string $verb = null): array
    {
        $verb ??= $this->getHTTPVerb();

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

        $from = esc(strip_tags(rtrim($prefix, '/') . '/' . ltrim($from, '/')));

        // Alors que nous voulons ajouter une route dans un groupe de '/',
        // ça ne marche pas avec la correspondance, alors supprimez-les...
        if ($from !== '/') {
            $from = trim($from, '/');
        }

        if (is_string($to) && ! str_contains($to, '::') && class_exists($to) && method_exists($to, '__invoke')) {
            $to = [$to, '__invoke'];
        }

        // Lors de la redirection vers une route nommée, $to est un tableau tel que `['zombies' => '\Zombies::index']`.
        if (is_array($to) && isset($to[0])) {
            $to = $this->processArrayCallableSyntax($from, $to);
        }

        $options = array_merge($this->currentOptions ?? [], $options ?? []);

        if (isset($options['middleware'])) {
            $options['middleware'] = (array) $options['middleware'];

            if (! isset($options['middlewares'])) {
                $options['middlewares'] = $options['middleware'];
            } else {
                $options['middlewares'] = array_merge($options['middlewares'], $options['middleware']);
            }

            unset($options['middleware']);
        }

        if (isset($options['middlewares'])) {
            $options['middlewares'] = array_unique($options['middlewares']);
        }

        if (is_string($to) && isset($options['controller'])) {
            $to = str_replace($options['controller'] . '::', '', $to);
            $to = str_replace($this->defaultNamespace, '', $options['controller']) . '::' . $to;
        }

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

        $routeKey = $from;

        // Remplacez nos espaces réservés de regex par la chose réelle
        // pour que le routeur n'ait pas besoin de savoir quoi que ce soit.
        foreach (($this->placeholders + ($options['where'] ?? [])) as $tag => $pattern) {
            $routeKey = str_ireplace(':' . $tag, $pattern, $routeKey);
        }

        // S'il s'agit d'une redirection, aucun traitement
        if (! isset($options['redirect']) && is_string($to)) {
            // Si aucun espace de noms n'est trouvé, ajouter l'espace de noms par défaut
            if (! str_contains($to, '\\') || strpos($to, '\\') > 0) {
                $namespace = $options['namespace'] ?? $this->defaultNamespace;
                $to        = trim($namespace, '\\') . '\\' . $to;
            }
            // Assurez-vous toujours que nous échappons à notre espace de noms afin de ne pas pointer vers
            // \BlitzPHP\Routes\Controller::method.
            $to = '\\' . ltrim($to, '\\');
        }

        $name = $this->formatRouteName($options['as'] ?? $options['name'] ?? $routeKey);

        // Ne remplacez aucun 'from' existant afin que les routes découvertes automatiquement
        // n'écrase pas les paramètres app/Config/Routes.
        // les routes manuelement définies doivent toujours être la "source de vérité".
        // cela ne fonctionne que parce que les routes découvertes sont ajoutées juste avant
        // pour tenter de router la requête.
        $routeKeyExists = isset($this->routes[$verb][$routeKey]);
        if ((isset($this->routesNames[$verb][$name]) || $routeKeyExists) && ! $overwrite) {
            return;
        }

        $this->routes[$verb][$routeKey] = [
            'name'    => $name,
            'handler' => $to,
            'from'    => $from,
        ];
        $this->routesOptions[$verb][$routeKey] = $options;
        $this->routesNames[$verb][$name]       = $routeKey;

        // C'est une redirection ?
        if (isset($options['redirect']) && is_numeric($options['redirect'])) {
            $this->routes['*'][$routeKey]['redirect'] = $options['redirect'];
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
        if (! isset($this->httpHost)) {
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
        if (! isset($this->httpHost)) {
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
        if (! str_starts_with($url, 'http')) {
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

    /**
     * Formate le nom des routes
     */
    private function formatRouteName(string $name): string
    {
        $name = trim($name, '/');

        return str_replace(['/', '\\', '_', '.', ' '], '.', $name);
    }

    /**
     * @param (Closure(mixed...): (ResponseInterface|string|void))|string $handler
     */
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

            if (str_contains($callableName, '\\') && $callableName[0] !== '\\') {
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
        if (! str_contains($route, '{locale}')) {
            return $route;
        }

        // Vérifier les paramètres régionaux non valides
        if ($locale !== null && ! in_array($locale, config('app.supported_locales'), true)) {
            $locale = null;
        }

        if ($locale === null) {
            $locale = Services::request()->getLocale();
        }

        return strtr($route, ['{locale}' => $locale]);
    }
}
