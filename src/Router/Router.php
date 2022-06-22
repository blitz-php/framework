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

use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Contracts\Router\RouterInterface;
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Exceptions\RedirectException;
use BlitzPHP\Exceptions\RouterException;
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
     * @var string
     */
    protected $controller;

    /**
     * Le nom de la méthode à utiliser
     *
     * @var string
     */
    protected $method;

    /**
     * An array of binds that were collected
     * so they can be sent to closure routes.
     *
     * @var array
     */
    protected $params = [];

    /**
     * Le nom du du front-controller.
     *
     * @var string
     */
    protected $indexPage = 'index.php';

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
    protected $middlewaresInfo = [];

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

        return $this;
    }

    /**
     * @throws PageNotFoundException
     * @throws RedirectException
     *
     * @return mixed|string
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
            throw new PageNotFoundException("Can't find a route for '{$uri}'.");
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
     */
    public function controllerName(): string
    {
        return str_replace('-', '_', trim($this->controller, '/\\'));
    }

    /**
     * Retourne le nom de la méthode à exécuter
     */
    public function methodName(): string
    {
        return str_replace('-', '_', $this->method);
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
        return ! empty($this->directory) ? $this->directory : '';
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
     *
     * @return array|null
     */
    public function getMatchedRouteOptions()
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
     * @throws RedirectException
     *
     * @return bool Si la route a été mis en correspondance ou non.
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
            : trim($uri, '/');

        // Boucle dans le tableau de routes à la recherche de caractères génériques
        foreach ($routes as $key => $val) {
            $localeSegment = null;

            $key = $key === '/'
                ? $key
                : ltrim($key, '/ ');

            $matchedKey = $key;

            // A-t-on affaire à une locale ?
            if (strpos($key, '{locale}') !== false) {
                $localeSegment = array_search('{locale}', preg_split('/[\/]*((^[a-zA-Z0-9])|\(([^()]*)\))*[\/]+/m', $key), true);

                // Remplacez-la par une regex pour qu'elle correspondra réellement.
                $key = str_replace('/', '\/', $key);
                $key = str_replace('{locale}', '[^\/]+', $key);
            }

            // Does the RegEx match?
            if (preg_match('#^' . $key . '$#u', $uri, $matches)) {
                // Is this route supposed to redirect to another?
                if ($this->collection->isRedirect($key)) {
                    throw new RedirectException(is_array($val) ? key($val) : $val, $this->collection->getRedirectCode($key));
                }
                // Store our locale so CodeIgniter object can
                // assign it to the Request.
                if (isset($localeSegment)) {
                    // The following may be inefficient, but doesn't upset NetBeans :-/
                    $temp                 = (explode('/', $uri));
                    $this->detectedLocale = $temp[$localeSegment];
                }

                // Are we using Closures? If so, then we need
                // to collect the params into an array
                // so it can be passed to the controller method later.
                if (! is_string($val) && is_callable($val)) {
                    $this->controller = $val;

                    // Remove the original string from the matches array
                    array_shift($matches);

                    $this->params = $matches;

                    $this->matchedRoute = [
                        $matchedKey,
                        $val,
                    ];

                    $this->matchedRouteOptions = $this->collection->getRoutesOptions($matchedKey);

                    return true;
                }
                // Are we using the default method for back-references?

                // Support resource route when function with subdirectory
                // ex: $routes->resource('Admin/Admins');
                if (strpos($val, '$') !== false && strpos($key, '(') !== false && strpos($key, '/') !== false) {
                    $replacekey = str_replace('/(.*)', '', $key);
                    $val        = preg_replace('#^' . $key . '$#u', $val, $uri);
                    $val        = str_replace($replacekey, str_replace('/', '\\', $replacekey), $val);
                } elseif (strpos($val, '$') !== false && strpos($key, '(') !== false) {
                    $val = preg_replace('#^' . $key . '$#u', $val, $uri);
                } elseif (strpos($val, '/') !== false) {
                    [
                        $controller,
                        $method,
                    ] = explode('::', $val);

                    // Only replace slashes in the controller, not in the method.
                    $controller = str_replace('/', '\\', $controller);

                    $val = $controller . '::' . $method;
                }

                $this->setRequest(explode('/', $val));

                $this->matchedRoute = [
                    $matchedKey,
                    $val,
                ];

                $this->matchedRouteOptions = $this->collection->getRoutesOptions($matchedKey);

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
        $segments = explode('/', $uri);

        $segments = $this->scanControllers($segments);

        // Si nous n'avons plus de segments - essayez le contrôleur par défaut ;
        // AVERTISSEMENT : les répertoires sont déplacés hors du tableau de segments.
        if (empty($segments)) {
            $this->setDefaultController();
        }
        // S'il n'est pas vide, le premier segment doit être le contrôleur
        else {
            $this->setController(array_shift($segments));
        }

        $controllerName = $this->controllerName();
        if (! $this->isValidSegment($controllerName)) {
            throw new PageNotFoundException($this->controller . ' is not a valid controller name');
        }

        // Utilise le nom de la méthode s'il existe.
        // Si ce n'est pas le cas, ce n'est pas grave - le nom de la méthode par défaut
        // a déjà été défini.
        if (! empty($segments)) {
            $this->setMethod(array_shift($segments) ?: $this->method);
        }

        if (! empty($segments)) {
            $this->params = $segments;
        }

        $defaultNamespace = $this->collection->getDefaultNamespace();
        if ($this->collection->getHTTPVerb() !== 'cli') {
            $controller = '\\' . $defaultNamespace;

            $controller .= $this->directory ? str_replace('/', '\\', $this->directory) : '';
            $controller .= $controllerName;

            $controller = strtolower($controller);
            $methodName = strtolower($this->methodName());

            foreach ($this->collection->getRoutes('cli') as $route) {
                if (is_string($route)) {
                    $route = strtolower($route);
                    if (strpos($route, $controller . '::' . $methodName) === 0) {
                        throw new PageNotFoundException();
                    }

                    if ($route === $controller) {
                        throw new PageNotFoundException();
                    }
                }
            }
        }

        // Charge le fichier afin qu'il soit disponible.
        $file = CONTROLLER_PATH . $this->directory . $controllerName . '.php';
        if (is_file($file)) {
            include_once $file;
        }

        // Assurez-vous que le contrôleur stocke le nom de classe complet
        // Nous devons vérifier une longueur supérieure à 1, puisque par défaut ce sera '\'
        if (strpos($this->controller, '\\') === false && strlen($defaultNamespace) > 1) {
            $this->setController('\\' . ltrim(str_replace('/', '\\', $defaultNamespace . $this->directory . $controllerName), '\\'));
        }
    }

    /**
     * Scans the controller directory, attempting to locate a controller matching the supplied uri $segments
     *
     * @param array $segments URI segments
     *
     * @return array returns an array of remaining uri segments that don't map onto a directory
     *
     * @deprecated this function name does not properly describe its behavior so it has been deprecated
     *
     * @codeCoverageIgnore
     */
    protected function validateRequest(array $segments): array
    {
        return $this->scanControllers($segments);
    }

    /**
     * Scanne le répertoire du contrôleur, essayant de localiser un contrôleur correspondant aux segments d'URI fournis
     *
     * @param array $segments segments d'URI
     *
     * @return array renvoie un tableau des segments uri restants qui ne correspondent pas à un répertoire
     */
    protected function scanControllers(array $segments): array
    {
        $segments = array_filter($segments, static fn ($segment) => $segment !== '');
        // réindexe numériquement le tableau, supprimant les lacunes
        $segments = array_values($segments);

        // si une valeur de répertoire précédente a été définie, retournez simplement les segments et sortez d'ici
        if (isset($this->directory)) {
            return $segments;
        }

        // Parcourez nos segments et revenez dès qu'un contrôleur
        // est trouvé ou lorsqu'un tel répertoire n'existe pas
        $c = count($segments);

        while ($c-- > 0) {
            $segmentConvert = ucfirst(str_replace('-', '_', $segments[0]));
            // dès que nous rencontrons un segment non conforme à PSR-4, arrêtons la recherche
            if (! $this->isValidSegment($segmentConvert)) {
                return $segments;
            }

            $test = CONTROLLER_PATH . $this->directory . $segmentConvert;

            // tant que chaque segment n'est *pas* un fichier de contrôleur mais correspond à un répertoire, ajoutez-le à $this->répertoire
            if (! is_file($test . '.php') && is_dir($test)) {
                $this->setDirectory($segmentConvert, true, false);
                array_shift($segments);

                continue;
            }

            return $segments;
        }

        // Cela signifie que tous les segments étaient en fait des répertoires
        return $segments;
    }

    /**
     * Définit le sous-répertoire dans lequel se trouve le contrôleur.
     *
     * @param bool $validate si vrai, vérifie que $dir se compose uniquement de segments conformes à PSR4
     */
    public function setDirectory(?string $dir = null, bool $append = false, bool $validate = true)
    {
        if (empty($dir)) {
            $this->directory = null;

            return;
        }

        if ($validate) {
            $segments = explode('/', trim($dir, '/'));

            foreach ($segments as $segment) {
                if (! $this->isValidSegment($segment)) {
                    return;
                }
            }
        }

        if ($append !== true || empty($this->directory)) {
            $this->directory = trim($dir, '/') . '/';
        } else {
            $this->directory .= trim($dir, '/') . '/';
        }
    }

    /**
     * Renvoie true si la chaîne $segment fournie représente un segment d'espace de noms/répertoire valide conforme à PSR-4
     *
     * regex comes from https://www.php.net/manual/en/language.variables.basics.php
     */
    private function isValidSegment(string $segment): bool
    {
        return (bool) preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $segment);
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
            $this->method = 'index';
        }

        if (! is_file(CONTROLLER_PATH . $this->directory . $this->makeController($class) . '.php')) {
            return;
        }

        $this->setController($class);

        // log_message('info', 'Used the default controller.');
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
        return preg_replace(
            ['#Controller$#', '#' . config('app.url_suffix') . '$#i'],
            '',
            ucfirst($name)
        ) . 'Controller';
    }

    /**
     * Modifie le nom de la méthode
     */
    private function setMethod(string $name): void
    {
        $this->method = preg_replace('#' . config('app.url_suffix') . '$#i', '', $name);
    }
}
