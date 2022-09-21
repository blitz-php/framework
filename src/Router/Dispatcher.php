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
use BlitzPHP\Debug\Timer;
use BlitzPHP\Exceptions\FrameworkException;
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Exceptions\RedirectException;
use BlitzPHP\Http\Middleware;
use BlitzPHP\Http\Response;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Http\Uri;
use BlitzPHP\Loader\Services;
use BlitzPHP\Traits\SingletonTrait;
use BlitzPHP\View\View;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

class Dispatcher
{
    use SingletonTrait;

    /**
     * Heure de démarrage de l'application.
     *
     * @var mixed
     */
    protected $startTime;

    /**
     * Durée totale d'exécution de l'application
     *
     * @var float
     */
    protected $totalTime;

    /**
     * Main application configuration
     *
     * @var stdClass
     */
    protected $config;

    /**
     * instance Timer.
     *
     * @var Timer
     */
    protected $timer;

    /**
     * requête courrante.
     *
     * @var ServerRequest
     */
    protected $request;

    /**
     * Reponse courrante.
     *
     * @var Response
     */
    protected $response;

    /**
     * Router à utiliser.
     *
     * @var Router
     */
    protected $router;

    /**
     * @var Middleware
     */
    private $middleware;

    /**
     * Contrôleur à utiliser.
     *
     * @var Closure|string
     */
    protected $controller;

    /**
     * Méthode du ontrôleur à exécuter.
     *
     * @var string
     */
    protected $method;

    /**
     * Gestionnaire de sortie à utiliser.
     *
     * @var string
     */
    protected $output;

    /**
     * Délai d'expiration du cache
     *
     * @var int
     */
    protected static $cacheTTL = 0;

    /**
     * Chemin de requête à utiliser.
     *
     * @var string
     */
    protected $path;

    /**
     * L'instance Response doit-elle "faire semblant"
     * pour éviter de définir des en-têtes/cookies/etc
     *
     * @var bool
     */
    protected $useSafeOutput = false;

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->startTime = microtime(true);

        $this->config = (object) config('app');
        $this->initMiddlewareQueue();
    }

    public static function init(bool $returnResponse = false)
    {
        return self::instance()->run(null, $returnResponse);
    }

    /**
     * Retourne la methode invoquee
     */
    public static function getMethod(): ?string
    {
        $method = self::instance()->method;
        if (empty($method)) {
            $method = Services::routes()->getDefaultMethod();
        }

        return $method;
    }

    /**
     * Retourne le contrôleur utilisé
     *
     * @return Closure|string
     */
    public static function getController()
    {
        $controller = self::instance()->controller;
        if (empty($controller)) {
            $controller = Services::routes()->getDefaultController();
        }

        return $controller;
    }

    /**
     * Lancez l'application !
     *
     * C'est "la boucle" si vous voulez. Le principal point d'entrée dans le script
     * qui obtient les instances de classe requises, déclenche les filtres,
     * essaie d'acheminer la réponse, charge le contrôleur et généralement
     * fait fonctionner toutes les pièces ensemble.
     *
     * @throws Exception
     * @throws RedirectException
     *
     * @return bool|mixed|ResponseInterface|ServerRequestInterface
     */
    public function run(?RouteCollectionInterface $routes = null, bool $returnResponse = false)
    {
        $this->startBenchmark();

        $this->getRequestObject();
        $this->getResponseObject();

        $this->forceSecureAccess();

        /**
         * Init event manager
         */
        $events_file = CONFIG_PATH . 'events.php';
        if (file_exists($events_file)) {
            require_once $events_file;
        }

        Services::event()->trigger('pre_system');

        // Recherche une page en cache. L'exécution s'arrêtera
        // si la page a été mise en cache.
        $response = $this->displayCache();
        if ($response instanceof ResponseInterface) {
            if ($returnResponse) {
                return $response;
            }

            return $this->emitResponse($response);
        }

        try {
            return $this->handleRequest($routes, $returnResponse);
        } catch (RedirectException $e) {
            Services::logger()->info('REDIRECTED ROUTE at ' . $e->getMessage());

            // Si la route est une route de "redirection", elle lance
            // l'exception avec le $to comme message
            // $this->response->redirect(base_url($e->getMessage()), 'auto', $e->getCode());
            $this->response = $this->response->withHeader('Location', base_url($e->getMessage()), 'auto', $e->getCode());

            $this->sendResponse();

            $this->callExit(EXIT_SUCCESS);

            return;
        } catch (PageNotFoundException $e) {
            $this->display404errors($e);
        }
    }

    /**
     * Définissez notre instance Response sur le mode "faire semblant" afin que des choses comme
     * les cookies et les en-têtes ne sont pas réellement envoyés, permettant à PHP 7.2+ de
     * ne pas se plaindre lorsque la fonction ini_set() est utilisée.
     */
    public function useSafeOutput(bool $safe = true): self
    {
        $this->useSafeOutput = $safe;

        return $this;
    }

    /**
     * Handles the main request logic and fires the controller.
     *
     * @throws PageNotFoundException
     * @throws RedirectException
     *
     * @return mixed|RequestInterface|ResponseInterface
     */
    protected function handleRequest(?RouteCollectionInterface $routes = null, bool $returnResponse = false)
    {
        if (empty($routes)) {
            $routes_file = CONFIG_PATH . 'routes.php';

            if (file_exists($routes_file)) {
                require_once $routes_file;
            }
        }
        if (empty($routes) || ! ($routes instanceof RouteCollection)) {
            $routes = Services::routes();
        }

        /**
         * Route middlewares
         */
        $routeMiddlewares = (array) $this->dispatchRoutes($routes);

        // The bootstrapping in a middleware
        $this->middleware->append(function (ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface {
            $returned = $this->startController($request, $response);

            // Closure controller has run in startController().
            if (! is_callable($this->controller)) {
                $controller = $this->createController($request, $response);

                if (! method_exists($controller, '_remap') && ! is_callable([$controller, $this->method], false)) {
                    throw PageNotFoundException::methodNotFound($this->method);
                }

                // Is there a "post_controller_constructor" event?
                Services::event()->trigger('post_controller_constructor');

                $returned = $this->runController($controller);
            } else {
                $this->timer->stop('controller_constructor');
                $this->timer->stop('controller');
            }

            Services::event()->trigger('post_system');

            if ($returned instanceof ResponseInterface) {
                $response = $returned;
            }

            return $response;
        });

        /**
         * Ajouter des middlewares de routes
         */
        foreach ($routeMiddlewares as $middleware) {
            $this->middleware->prepend($middleware);
        }

        // Enregistrer notre URI actuel en tant qu'URI précédent dans la session
        // pour une utilisation plus sûre et plus précise avec la fonction d'assistance `previous_url()`.
        $this->storePreviousURL(current_url(true));

        /**
         * Emission de la reponse
         */
        $this->gatherOutput($this->middleware->handle($this->request));

        if (! $returnResponse) {
            $this->sendResponse();
        }

        // Y a-t-il un événement post-système ?
        Services::event()->trigger('post_system');

        return $this->response;
    }

    /**
     * Démarrer le benchmark
     *
     * La minuterie est utilisée pour afficher l'exécution totale du script à la fois dans la
     * barre d'outils de débogage, et éventuellement sur la page affichée.
     */
    protected function startBenchmark()
    {
        if ($this->startTime === null) {
            $this->startTime = microtime(true);
        }

        $this->timer = Services::timer();
        $this->timer->start('total_execution', $this->startTime);
        $this->timer->start('bootstrap');
    }

    /**
     * Définit un objet Request à utiliser pour cette requête.
     * Utilisé lors de l'exécution de certains tests.
     */
    public function setRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Obtenez notre objet Request et définissez le protocole du serveur en fonction des informations fournies
     * par le serveur.
     */
    protected function getRequestObject()
    {
        if ($this->request instanceof ServerRequestInterface) {
            return;
        }

        if (is_cli() && ! on_test()) {
            // @codeCoverageIgnoreStart
            // $this->request = Services::clirequest($this->config);
        // @codeCoverageIgnoreEnd
        }

        $version = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        if (! is_numeric($version)) {
            $version = substr($version, strpos($version, '/') + 1);
        }

        // Assurez-vous que la version est au bon format
        $version = number_format((float) $version, 1);

        $this->request = Services::request()->withProtocolVersion($version);
    }

    /**
     * Obtenez notre objet Response et définissez des valeurs par défaut, notamment
     * la version du protocole HTTP et une réponse réussie par défaut.
     */
    protected function getResponseObject()
    {
        // Supposons le succès jusqu'à preuve du contraire.
        $this->response = Services::response()->withStatus(200);

        if (! is_cli() || on_test()) {
        }

        $this->response = $this->response->withProtocolVersion($this->request->getProtocolVersion());
    }

    /**
     * Forcer l'accès au site sécurisé ? Si la valeur de configuration 'forceGlobalSecureRequests'
     * est vrai, imposera que toutes les demandes adressées à ce site soient effectuées via
     * HTTPS. Redirigera également l'utilisateur vers la page actuelle avec HTTPS
     * comme défini l'en-tête HTTP Strict Transport Security pour ces navigateurs
     * qui le supportent.
     *
     * @param int $duration Combien de temps la sécurité stricte des transports
     *                      doit être appliqué pour cette URL.
     */
    protected function forceSecureAccess($duration = 31536000)
    {
        if ($this->config->force_global_secure_requests !== true) {
            return;
        }

        force_https($duration, $this->request, $this->response);
    }

    /**
     * Détermine si une réponse a été mise en cache pour l'URI donné.
     *
     * @throws FrameworkException
     *
     * @return bool|ResponseInterface
     */
    public function displayCache()
    {
        if ($cachedResponse = Services::cache()->read($this->generateCacheName())) {
            $cachedResponse = unserialize($cachedResponse);
            if (! is_array($cachedResponse) || ! isset($cachedResponse['output']) || ! isset($cachedResponse['headers'])) {
                throw new FrameworkException('Error unserializing page cache');
            }

            $headers = $cachedResponse['headers'];
            $output  = $cachedResponse['output'];

            // Effacer tous les en-têtes par défaut
            foreach (array_keys($this->response->getHeaders()) as $key) {
                $this->response = $this->response->withoutHeader($key);
            }

            // Définir les en-têtes mis en cache
            foreach ($headers as $name => $value) {
                $this->response = $this->response->withHeader($name, $value);
            }

            $output = $this->displayPerformanceMetrics($output);

            return $this->response->withBody(to_stream($output));
        }

        return false;
    }

    /**
     * Indique à l'application que la sortie finale doit être mise en cache.
     */
    public static function cache(int $time)
    {
        static::$cacheTTL = $time;
    }

    /**
     * Met en cache la réponse complète de la requête actuelle. Pour utiliser
     * la mise en cache pleine page pour des performances très élevées.
     *
     * @return mixed
     */
    protected function cachePage()
    {
        $headers = [];

        foreach (array_keys($this->response->getHeaders()) as $header) {
            $headers[$header] = $this->response->getHeaderLine($header);
        }

        return Services::cache()->write(
            $this->generateCacheName(),
            serialize(['headers' => $headers, 'output' => $this->output]),
            static::$cacheTTL
        );
    }

    /**
     * Renvoie un tableau avec nos statistiques de performances de base collectées.
     */
    public function getPerformanceStats(): array
    {
        return [
            'startTime' => $this->startTime,
            'totalTime' => $this->totalTime,
        ];
    }

    /**
     * Génère le nom du cache à utiliser pour notre mise en cache pleine page.
     */
    protected function generateCacheName(): string
    {
        $uri = $this->request->getUri();

        $name = Uri::createURIString($uri->getScheme(), $uri->getAuthority(), $uri->getPath());

        return md5($name);
    }

    /**
     * Remplace les balises memory_usage et elapsed_time.
     */
    public function displayPerformanceMetrics(string $output): string
    {
        $this->totalTime = $this->timer->getElapsedTime('total_execution');

        return str_replace('{elapsed_time}', (string) $this->totalTime, $output);
    }

    /**
     * Fonctionne avec le routeur pour
     * faire correspondre une route à l'URI actuel. Si la route est une
     * "route de redirection", gérera également la redirection.
     *
     * @param RouteCollectionInterface|null $routes Une interface de collecte à utiliser à la place
     *                                              du fichier de configuration.
     *
     * @throws RedirectException
     *
     * @return string|string[]|null
     */
    protected function dispatchRoutes(RouteCollectionInterface $routes)
    {
        $this->router = Services::router($routes, $this->request, false);

        $path = $this->determinePath();

        $this->timer->stop('bootstrap');
        $this->timer->start('routing');

        ob_start();
        $this->controller = $this->router->handle($path);
        $this->method     = $this->router->methodName();

        // Si un segment {locale} correspondait dans la route finale,
        // alors nous devons définir les paramètres régionaux corrects sur notre requête.
        if ($this->router->hasLocale()) {
            $this->request = $this->request->withLocale($this->router->getLocale());
        }

        $this->timer->stop('routing');

        return $this->router->getMiddlewares();
    }

    /**
     * Détermine le chemin à utiliser pour que nous essayions d'acheminer vers, en fonction
     * de l'entrée de l'utilisateur (setPath), ou le chemin CLI/IncomingRequest.
     */
    protected function determinePath(): string
    {
        if (! empty($this->path)) {
            return $this->path;
        }

        return method_exists($this->request, 'getPath')
            ? $this->request->getPath()
            : $this->request->getUri()->getPath();
    }

    /**
     * Permet de définir le chemin de la requête depuis l'extérieur de la classe,
     * au lieu de compter sur CLIRequest ou IncomingRequest pour le chemin.
     *
     * Ceci est principalement utilisé par la console.
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Maintenant que tout a été configuré, cette méthode tente d'exécuter le
     * méthode du contrôleur et lancez le script. S'il n'en est pas capable, le fera
     * afficher l'erreur Page introuvable appropriée.
     */
    protected function startController(ServerRequest $request, Response $response)
    {
        // Aucun contrôleur spécifié - nous ne savons pas quoi faire maintenant.
        if (empty($this->controller)) {
            throw PageNotFoundException::emptyController();
        }

        $this->timer->start('controller');
        $this->timer->start('controller_constructor');

        // Est-il acheminé vers une Closure ?
        if (is_object($this->controller) && (get_class($this->controller) === 'Closure')) {
            $controller = $this->controller;

            $sendParameters = [];

            foreach ($this->router->params() as $parameter) {
                $sendParameters[] = $parameter;
            }
            array_push($sendParameters, $request, $response);

            return Services::injector()->call($controller, $sendParameters);
        }

        // Essayez de charger automatiquement la classe
        if (! class_exists($this->controller, true) || $this->method[0] === '_') {
            throw PageNotFoundException::controllerNotFound($this->controller, $this->method);
        }

        return null;
    }

    /**
     * Instancie la classe contrôleur.
     *
     * @return \BlitzPHP\Controllers\BaseController|mixed
     */
    private function createController(ServerRequestInterface $request, ResponseInterface $response)
    {
        /**
         * @var \BlitzPHP\Controllers\BaseController
         */
        $class = Services::injector()->get($this->controller);

        if (method_exists($class, 'initialize')) {
            $class->initialize($request, $response, Services::logger());
        }

        $this->timer->stop('controller_constructor');

        return $class;
    }

    /**
     * Exécute le contrôleur, permettant aux méthodes _remap de fonctionner.
     *
     * @param mixed $class
     *
     * @return mixed
     */
    protected function runController($class)
    {
        // S'il s'agit d'une demande de console, utilisez les segments d'entrée comme paramètres
        $params = defined('KLINGED') ? $this->request->getSegments() : $this->router->params();
        $method = $this->method;

        if (method_exists($class, '_remap')) {
            $params = [$method, $params];
            $method = '_remap';
        }

        $output = Services::injector()->call([$class, $method], (array) $params);

        $this->timer->stop('controller');

        if ($output instanceof View) {
            $output = $this->response->withBody(to_stream($output->get()));
        }

        return $output;
    }

    /**
     * Affiche une page d'erreur 404 introuvable. S'il est défini, essaiera de
     * appelez le contrôleur/méthode 404Override qui a été défini dans la configuration de routage.
     */
    protected function display404errors(PageNotFoundException $e)
    {
        // Is there a 404 Override available?
        if ($override = $this->router->get404Override()) {
            if ($override instanceof Closure) {
                echo $override($e->getMessage());
            } elseif (is_array($override)) {
                $this->timer->start('controller');
                $this->timer->start('controller_constructor');

                $this->controller = $override[0];
                $this->method     = $override[1];

                $controller = $this->createController($this->request, $this->response);
                $this->runController($controller);
            }

            unset($override);

            $this->emitResponse();

            return;
        }

        // Affiche l'erreur 404
        $this->response = $this->response->withStatus($e->getCode());

        if (! on_test()) {
            // @codeCoverageIgnoreStart
            if (ob_get_level() > 0) {
                ob_end_flush();
            }
            // @codeCoverageIgnoreEnd
        }
        // Lors des tests, l'un est pour phpunit, l'autre pour le cas de test.
        elseif (ob_get_level() > 2) {
            ob_end_flush(); // @codeCoverageIgnore
        }

        throw PageNotFoundException::pageNotFound(! on_prod() || is_cli() ? $e->getMessage() : '');
    }

    /**
     * Rassemble la sortie du script à partir du tampon, remplace certaines balises d'exécutions
     * d'horodatage dans la sortie et affiche la barre d'outils de débogage, si nécessaire.
     *
     * @param mixed|null $returned
     */
    protected function gatherOutput($returned = null)
    {
        $this->output = ob_get_contents();
        // Si la mise en mémoire tampon n'est pas nulle.
        // Nettoyer (effacer) le tampon de sortie et désactiver le tampon de sortie
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Si le contrôleur a renvoyé un objet de réponse,
        // nous devons en saisir le corps pour qu'il puisse
        // être ajouté à tout ce qui aurait déjà pu être ajouté avant de faire le écho.
        // Nous devons également enregistrer l'instance localement
        // afin que tout changement de code d'état, etc., ait lieu.
        if ($returned instanceof ResponseInterface) {
            $this->response = $returned;
            $returned       = $returned->getBody()->getContents();
        }

        if (is_string($returned)) {
            $this->output .= $returned;
        }

        // Mettez-le en cache sans remplacer les mesures de performances
        // afin que nous puissions avoir des mises à jour de vitesse en direct en cours de route.
        if (static::$cacheTTL > 0) {
            $this->cachePage();
        }

        $this->output = $this->displayPerformanceMetrics($this->output);

        $this->response = $this->response->withBody(to_stream($this->output));
    }

    /**
     * Si nous avons un objet de session à utiliser, stockez l'URI actuel
     * comme l'URI précédent. Ceci est appelé juste avant d'envoyer la
     * réponse au client, et le rendra disponible à la prochaine demande.
     *
     * Cela permet au fournisseur une détection plus sûre et plus fiable de la fonction previous_url().
     *
     * @param \BlitzPHP\Http\URI|string $uri
     */
    public function storePreviousURL($uri)
    {
        // Ignorer les requêtes CLI
        if (is_cli() && ! on_test()) {
            return; // @codeCoverageIgnore
        }

        // IIgnorer les requêtes AJAX
        if (method_exists($this->request, 'isAJAX') && $this->request->isAJAX()) {
            return;
        }

        // Ceci est principalement nécessaire lors des tests ...
        if (is_string($uri)) {
            $uri = Services::uri($uri, false);
        }

        if (isset($_SESSION)) {
            $_SESSION['_blitz_previous_url'] = Uri::createURIString(
                $uri->getScheme(),
                $uri->getAuthority(),
                $uri->getPath(),
                $uri->getQuery(),
                $uri->getFragment()
            );
        }
    }

    /**
     * Renvoie la sortie de cette requête au client.
     * C'est ce qu'il attendait !
     */
    protected function sendResponse()
    {
        $this->totalTime = $this->timer->getElapsedTime('total_execution');
        Services::emitter()->emit(
            Services::toolbar()->prepare($this->getPerformanceStats(), $this->request, $this->response)
        );
    }

    protected function emitResponse()
    {
        $this->gatherOutput();
        $this->sendResponse();
    }

    /**
     * Quitte l'application en définissant le code de sortie pour les applications basées sur CLI
     * qui pourrait regarder.
     *
     * Fabriqué dans une méthode distincte afin qu'il puisse être simulé pendant les tests
     * sans réellement arrêter l'exécution du script.
     */
    protected function callExit(int $code)
    {
        exit($code); // @codeCoverageIgnore
    }

    /**
     * Initialise le gestionnaire de middleware
     */
    protected function initMiddlewareQueue(): void
    {
        $this->middleware = Services::injector()->make(Middleware::class, [$this->response]);
        $this->middleware->prepend($this->spoofRequestMethod());

        $middlewaresFile = CONFIG_PATH . 'middlewares.php';
        if (file_exists($middlewaresFile) && ! in_array($middlewaresFile, get_included_files(), true)) {
            $middleware = require $middlewaresFile;
            if (is_callable($middleware)) {
                $middlewareQueue = $middleware($this->middleware, $this->request);
                if ($middlewareQueue instanceof Middleware) {
                    $this->middleware = $middlewareQueue;
                }
            }
        }
    }

    /**
     * Modifie l'objet de requête pour utiliser une méthode différente
     * si une variable POST appelée _method est trouvée.
     */
    private function spoofRequestMethod(): callable
    {
        return static function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
            $post = $request->getParsedBody();

            // Ne fonctionne qu'avec les formulaires POST
            if (strtoupper($request->getMethod()) === 'POST' && ! empty($post['_method'])) {
                // Accepte seulement PUT, PATCH, DELETE
                if (in_array(strtoupper($post['_method']), ['PUT', 'PATCH', 'DELETE'], true)) {
                    $request = $request->withMethod($post['_method']);
                }
            }

            return $next($request, $response);
        };
    }
}
