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

use BlitzPHP\Contracts\Router\AutoRouterInterface;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Contracts\Router\RouterInterface;
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Exceptions\RedirectException;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Utilities\String\Str;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Analyse l'URL de la requête dans le contrôleur, action et paramètres. Utilise les routes connectées
 * pour faire correspondre la chaîne d'URL entrante aux paramètres qui permettront à la requête d'être envoyée. Aussi
 * gère la conversion des listes de paramètres en chaînes d'URL, en utilisant les routes connectées. Le routage vous permet de découpler
 * la façon dont le monde interagit avec votre application (URL) et l'implémentation (contrôleurs et actions).
 */
class Router implements RouterInterface
{
    /**
     * Une instance de la classe RouteCollection.
     *
     * @var RouteCollection
     */
    protected $collection;

    /**
     * Sous-répertoire contenant la classe de contrôleur demandée.
     * Principalement utilisé par 'autoRoute'.
     *
     * @var string|null
     */
    protected $directory;

    /**
     * Le nom de la classe contrôleur
     *
     * @var Closure|string
     */
    protected $controller;

    /**
     * Le nom de la méthode à utiliser
     */
    protected string $method = '';

    /**
     * Un tableau de liens qui ont été collectés afin
     * qu'ils puissent être envoyés aux routes de fermeture.
     */
    protected array $params = [];

    /**
     * Le nom du du front-controller.
     */
    protected string $indexPage = 'index.php';

    /**
     * Si les tirets dans les URI doivent être convertis
     * pour les traits de soulignement lors de la détermination des noms de méthode.
     */
    protected bool $translateURIDashes = true;

    /**
     * Les routes trouvées pour la requête courrante
     *
     * @var array|null
     */
    protected $matchedRoute;

    /**
     * Les options de la route matchée.
     *
     * @var array|null
     */
    protected $matchedRouteOptions;

    /**
     * Le locale (langue) qui a été detectée dans la route.
     *
     * @var string
     */
    protected $detectedLocale;

    /**
     * Les informations des middlewares à executer
     * Si la route matchée necessite des filtres.
     *
     * @var string[]
     */
    protected array $middlewaresInfo = [];

    protected ?AutoRouterInterface $autoRouter = null;

    /**
     * @param Request $request
     *
     * @return self
     */
    public function init(RouteCollectionInterface $routes, ServerRequestInterface $request)
    {
        $this->collection = $routes;

        $this->setController($this->collection->getDefaultController());
        $this->setMethod($this->collection->getDefaultMethod());

        $this->collection->setHTTPVerb($request->getMethod() ?? strtolower($_SERVER['REQUEST_METHOD']));

        $this->translateURIDashes = $this->collection->shouldTranslateURIDashes();

        $this->autoRouter = new AutoRouter(
            $this->collection->getRegisteredControllers('cli'),
            $this->collection->getDefaultNamespace(),
            $this->collection->getDefaultController(),
            $this->collection->getDefaultMethod(),
            $this->translateURIDashes,
            $this->collection->getHTTPVerb()
        );

        return $this;
    }

    /**
     * @return Closure|string Controller classname or Closure
     *
     * @throws PageNotFoundException
     * @throws RedirectException
     */
    public function handle(?string $uri = null)
    {
        // Si nous ne trouvons pas d'URI à comparer, alors
        // tout fonctionne à partir de ses paramètres par défaut.
        if ($uri === null || $uri === '') {
            return strpos($this->controller, '\\') === false
                ? $this->collection->getDefaultNamespace() . $this->controller
                : $this->controller;
        }

        $uri = urldecode($uri);

        if ($this->checkRoutes($uri)) {
            if ($this->collection->isFiltered($this->matchedRoute[0])) {
                $this->middlewaresInfo = $this->collection->getFiltersForRoute($this->matchedRoute[0]);
            }

            return $this->controller;
        }

        // Toujours là ? Ensuite, nous pouvons essayer de faire correspondre l'URI avec
        // Contrôleurs/répertoires, mais l'application peut ne pas
        // vouloir ceci, comme dans le cas des API.
        if (! $this->collection->shouldAutoRoute()) {
            $verb = strtolower($this->collection->getHTTPVerb());

            throw new PageNotFoundException("Can't find a route for '{$verb}: {$uri}'.");
        }

        $this->autoRoute($uri);

        return $this->controllerName();
    }

    /**
     * Renvoie les informations des middlewares de la routes matchée
     *
     * @return string[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewaresInfo;
    }

    /**
     * Renvoie le nom du contrôleur matché
     *
     * @return closure|string
     */
    public function controllerName()
    {
        if (! is_string($this->controller)) {
            return $this->controller;
        }

        return $this->translateURIDashes
            ? str_replace('-', '_', trim($this->controller, '/\\'))
            : Str::toPascalCase($this->controller);
    }

    /**
     * Retourne le nom de la méthode à exécuter
     */
    public function methodName(): string
    {
        return $this->translateURIDashes
            ? str_replace('-', '_', $this->method)
            : $this->method;
    }

    /**
     * Renvoie les paramètres de remplacement 404 de la collection.
     * Si le remplacement est une chaîne, sera divisé en tableau contrôleur/index.
     *
     * @return array|callable|null
     */
    public function get404Override()
    {
        $route = $this->collection->get404Override();

        if (is_string($route)) {
            $routeArray = explode('::', $route);

            return [
                $routeArray[0], // Controller
                $routeArray[1] ?? 'index',   // Method
            ];
        }

        if (is_callable($route)) {
            return $route;
        }

        return null;
    }

    /**
     * Renvoie les liaisons qui ont été mises en correspondance et collectées
     * pendant le processus d'analyse sous forme de tableau, prêt à être envoyé à
     * instance->method(...$params).
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * Renvoie le nom du sous-répertoire dans lequel se trouve le contrôleur.
     * Relatif à APPPATH.'Controllers'.
     *
     * Uniquement utilisé lorsque le routage automatique est activé.
     */
    public function directory(): string
    {
        if ($this->autoRouter instanceof AutoRouter) {
            return $this->autoRouter->directory();
        }

        return '';
    }

    /**
     * Renvoie les informations de routage qui correspondaient à ce
     * requête, si une route a été définie.
     */
    public function getMatchedRoute(): ?array
    {
        return $this->matchedRoute;
    }

    /**
     * Renvoie toutes les options définies pour la route correspondante
     */
    public function getMatchedRouteOptions(): ?array
    {
        return $this->matchedRouteOptions;
    }

    /**
     * Définit la valeur qui doit être utilisée pour correspondre au fichier index.php. Valeurs par défaut
     * à index.php mais cela vous permet de le modifier au cas où vous utilisez
     * quelque chose comme mod_rewrite pour supprimer la page. Vous pourriez alors le définir comme une chaine vide=
     */
    public function setIndexPage(string $page): self
    {
        $this->indexPage = $page;

        return $this;
    }

    /**
     * Renvoie vrai/faux selon que la route actuelle contient ou non
     * un placeholder {locale}.
     */
    public function hasLocale(): bool
    {
        return (bool) $this->detectedLocale;
    }

    /**
     * Renvoie la locale (langue) détectée, le cas échéant, ou null.
     */
    public function getLocale(): ?string
    {
        return $this->detectedLocale;
    }

    /**
     * Compare la chaîne uri aux routes que la
     * classe RouteCollection a définie pour nous, essayant de trouver une correspondance.
     * Cette méthode modifiera $this->controller, si nécessaire.
     *
     * @param string $uri Le chemin URI à comparer aux routes
     *
     * @return bool Si la route a été mis en correspondance ou non.
     *
     * @throws RedirectException
     */
    protected function checkRoutes(string $uri): bool
    {
        $routes = $this->collection->getRoutes($this->collection->getHTTPVerb());

        // S'il n'y a pas de routes definies pour la methode HTTP, c'est pas la peine d'aller plus loin
        if (empty($routes)) {
            return false;
        }

        $uri = $uri === '/'
            ? $uri
            : trim($uri, '/ ');

        // Boucle dans le tableau de routes à la recherche de caractères génériques
        foreach ($routes as $routeKey => $handler) {
            $routeKey = $routeKey === '/'
                ? $routeKey
                : ltrim($routeKey, '/ ');

            $matchedKey = $routeKey;

            // A-t-on affaire à une locale ?
            if (strpos($routeKey, '{locale}') !== false) {
                $routeKey = str_replace('{locale}', '[^/]+', $routeKey);
            }

            // Est-ce que RegEx correspond ?
            if (preg_match('#^' . $routeKey . '$#u', $uri, $matches)) {
                // Cette route est-elle censée rediriger vers une autre ?
                if ($this->collection->isRedirect($routeKey)) {
                    // remplacement des groupes de routes correspondants par des références : post/([0-9]+) -> post/$1
                    $redirectTo = preg_replace_callback('/(\([^\(]+\))/', static function () {
                        static $i = 1;

                        return '$' . $i++;
                    }, is_array($handler) ? key($handler) : $handler);

                    throw new RedirectException(
                        preg_replace('#^' . $routeKey . '$#u', $redirectTo, $uri),
                        $this->collection->getRedirectCode($routeKey)
                    );
                }
                // Stocke nos paramètres régionaux afin que l'objet CodeIgniter puisse l'affecter à la requête.
                if (strpos($matchedKey, '{locale}') !== false) {
                    preg_match(
                        '#^' . str_replace('{locale}', '(?<locale>[^/]+)', $matchedKey) . '$#u',
                        $uri,
                        $matched
                    );

                    $this->detectedLocale = $matched['locale'];
                    unset($matched);
                }

                // Utilisons-nous Closures ? Si tel est le cas, nous devons collecter les paramètres dans un tableau
                // afin qu'ils puissent être transmis ultérieurement à la méthode du contrôleur.
                if (! is_string($handler) && is_callable($handler)) {
                    $this->controller = $handler;

                    // Supprime la chaîne d'origine du tableau matches
                    array_shift($matches);

                    $this->params = $matches;

                    $this->setMatchedRoute($matchedKey, $handler);

                    return true;
                }

                [$controller] = explode('::', $handler);

                // Vérifie `/` dans le nom du contrôleur
                if (strpos($controller, '/') !== false) {
                    throw RouterException::invalidControllerName($handler);
                }

                if (strpos($handler, '$') !== false && strpos($routeKey, '(') !== false) {
                    // Vérifie le contrôleur dynamique
                    if (strpos($controller, '$') !== false) {
                        throw RouterException::dynamicController($handler);
                    }

                    // Utilisation de back-references
                    $handler = preg_replace('#^' . $routeKey . '$#u', $handler, $uri);
                }

                $this->setRequest(explode('/', $handler));

                $this->setMatchedRoute($matchedKey, $handler);

                return true;
            }
        }

        return false;
    }

    /**
     * Tente de faire correspondre un chemin d'URI avec des contrôleurs et des répertoires
     * trouvé dans CONTROLLER_PATH, pour trouver une route correspondante.
     */
    public function autoRoute(string $uri)
    {
        [$this->directory, $this->controller, $this->method, $this->params]
            = $this->autoRouter->getRoute($uri);
    }

    /**
     * Définit le sous-répertoire dans lequel se trouve le contrôleur.
     *
     * @param bool $validate si vrai, vérifie que $dir se compose uniquement de segments conformes à PSR4
     *
     * @deprecated Cette méthode sera retirée
     */
    public function setDirectory(?string $dir = null, bool $append = false, bool $validate = true)
    {
        if (empty($dir)) {
            $this->directory = null;

            return;
        }

        if ($this->autoRouter instanceof AutoRouter) {
            $this->autoRouter->setDirectory($dir, $append, $validate);
        }
    }

    /**
     * Définir la route de la requête
     *
     * Prend un tableau de segments URI en entrée et définit la classe/méthode
     * être appelé.
     *
     * @param array $segments segments d'URI
     */
    protected function setRequest(array $segments = [])
    {
        // Si nous n'avons aucun segment - essayez le contrôleur par défaut ;
        if (empty($segments)) {
            $this->setDefaultController();

            return;
        }

        [$controller, $method] = array_pad(explode('::', $segments[0]), 2, null);

        $this->setController($controller);

        // $this->method contient déjà le nom de la méthode par défaut,
        // donc ne l'écrasez pas avec le vide.
        if (! empty($method)) {
            $this->setMethod($method);
        }

        array_shift($segments);

        $this->params = $segments;
    }

    /**
     * Définit le contrôleur par défaut en fonction des informations définies dans RouteCollection.
     */
    protected function setDefaultController()
    {
        if (empty($this->controller)) {
            throw RouterException::missingDefaultRoute();
        }

        // La méthode est-elle spécifiée ?
        if (sscanf($this->controller, '%[^/]/%s', $class, $this->method) !== 2) {
            $this->method = $this->collection->getDefaultMethod();
        }

        if (! is_file(CONTROLLER_PATH . $this->directory . $this->makeController($class) . '.php')) {
            return;
        }

        $this->setController($class);

        logger()->info('Used the default controller.');
    }

    /**
     * Modifie le nom du controleur
     */
    private function setController(string $name): void
    {
        $this->controller = $this->makeController($name);
    }

    /**
     * Construit un nom de contrôleur valide
     */
    private function makeController(string $name): string
    {
        if ($this->autoRouter instanceof AutoRouter) {
            return $this->autoRouter->makeController($name);
        }

        return $name;
    }

    /**
     * Modifie le nom de la méthode
     */
    private function setMethod(string $name): void
    {
        $this->method = preg_replace('#' . config('app.url_suffix') . '$#i', '', $name);
    }

    /**
     * @param callable|string $handler
     */
    protected function setMatchedRoute(string $route, $handler): void
    {
        $this->matchedRoute = [$route, $handler];

        $this->matchedRouteOptions = $this->collection->getRoutesOptions($route);
    }
}
