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

use BlitzPHP\Cache\ResponseCache;
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Contracts\Http\ResponsableInterface;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Contracts\Support\Responsable;
use BlitzPHP\Controllers\BaseController;
use BlitzPHP\Controllers\RestController;
use BlitzPHP\Core\App;
use BlitzPHP\Debug\Timer;
use BlitzPHP\Event\EventDiscover;
use BlitzPHP\Exceptions\FrameworkException;
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Exceptions\RedirectException;
use BlitzPHP\Exceptions\ValidationException;
use BlitzPHP\Http\Middleware;
use BlitzPHP\Http\Response;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Http\Uri;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\View\View;
use Closure;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Throwable;

/**
 * Cette classe est la porte d'entree du framework. Elle analyse la requete,
 * recherche la route correspondante et invoque le bon controleurm puis renvoie la reponse.
 */
class Dispatcher
{
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
     * Chemin de requête à utiliser.
     *
     * @var string
	 * 
	 * @deprecated No longer used.
     */
    protected $path;

    /**
     * Application output buffering level
     */
    protected int $bufferLevel = 0;

	/**
     * Web Page Caching
     */
    protected ResponseCache $pageCache;

    /**
     * Constructor.
     */
    public function __construct(protected EventManagerInterface $event)
    {
        $this->startTime = microtime(true);
        $this->config    = (object) config('app');

		$this->pageCache = Services::factory(ResponseCache::class, [
			'cacheQueryString' => config('cache.cache_query_string')
		]);
    }

    /**
     * Retourne la methode invoquee
     */
    public static function getMethod(): ?string
    {
        $method = Services::singleton(self::class)->method;
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
    public static function getController(bool $fullName = true)
    {
        $routes = Services::routes();

        $controller = Services::singleton(self::class)->controller;
        if (empty($controller)) {
            $controller = $routes->getDefaultController();
        }

		if (! $fullName && is_string($controller)) {
            $controller = str_replace($routes->getDefaultNamespace(), '', $controller);
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
     * @return bool|mixed|ResponseInterface|ServerRequestInterface
     *
     * @throws Exception
     * @throws RedirectException
     */
    public function run(?RouteCollectionInterface $routes = null, bool $returnResponse = false)
    {
        $this->pageCache->setTtl(0);
        $this->bufferLevel = ob_get_level();

        $this->startBenchmark();

        $this->getRequestObject();
        $this->getResponseObject();

        $this->initMiddlewareQueue();

		try {
            $this->response = $this->handleRequest($routes, config('cache'));
        } catch (ResponsableInterface|RedirectException $e) {
            $this->outputBufferingEnd();
            if ($e instanceof RedirectException) {
                $e = new RedirectException($e->getMessage(), $e->getCode(), $e);
            }

            $this->response = $e->getResponse();
        } catch (PageNotFoundException $e) {
            $this->response = $this->display404errors($e);
        } catch (Throwable $e) {
            $this->outputBufferingEnd();

            throw $e;
        }

        if ($returnResponse) {
            return $this->response;
        }

        $this->sendResponse();
    }

    /**
     * Gère la logique de requête principale et déclenche le contrôleur.
     *
     * @throws PageNotFoundException
     * @throws RedirectException
     */
    protected function handleRequest(?RouteCollectionInterface $routes = null, ?array $cacheConfig = null): ResponseInterface
    {
		$this->forceSecureAccess();

		/**
         * Init event manager
         */
		Services::singleton(EventDiscover::class)->discove();

		$this->event->trigger('pre_system');

		// Check for a cached page. 
		// Execution will stop if the page has been cached.
        if (($response = $this->displayCache($cacheConfig)) instanceof ResponseInterface) {
            return $response;
        }

        $routeMiddlewares = (array) $this->dispatchRoutes($routes);

        // Le bootstrap dans un middleware
        $this->middleware->append($this->bootApp());

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

        // Y a-t-il un événement post-système ?
        $this->event->trigger('post_system');

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
     * @return bool|ResponseInterface
     *
     * @throws FrameworkException
     */
    public function displayCache(?array $config = null)
    {
		if ($cachedResponse = $this->pageCache->get($this->request, $this->response)) {
            $this->response = $cachedResponse;

            $this->totalTime = $this->timer->getElapsedTime('total_execution');
            $output          = $this->displayPerformanceMetrics($cachedResponse->getBody());
        
			return $this->response->withBody(to_stream($output));
        }

        return false;
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
     * @return string[]
     *
     * @throws RedirectException
     */
    protected function dispatchRoutes(?RouteCollectionInterface $routes = null): array
    {
        if ($routes === null) {
            $routes = Services::routes()->loadRoutes();
        }

        $this->router = Services::router($routes, $this->request, false);

        $path = $this->determinePath();

        $this->timer->stop('bootstrap');
        $this->timer->start('routing');

        $this->outputBufferingStart();

        $this->controller = $this->router->handle($path ?: '/');
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

        $path = method_exists($this->request, 'getPath')
            ? $this->request->getPath()
            : $this->request->getUri()->getPath();

        return $this->path = preg_replace('#^' . App::getUri()->getPath() . '#i', '', $path);
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
        if (! class_exists($this->controller, true) || ($this->method[0] === '_' && $this->method !== '__invoke')) {
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
        // Existe-t-il une dérogation 404 disponible ?
        if ($override = $this->router->get404Override()) {
            $returned = null;

            if ($override instanceof Closure) {
                echo $override($e->getMessage());
            } elseif (is_array($override)) {
                $this->timer->start('controller');
                $this->timer->start('controller_constructor');

                $this->controller = $override[0];
                $this->method     = $override[1];

                $controller = $this->createController($this->request, $this->response);
                $returned   = $this->runController($controller);
            }

            unset($override);

            $this->gatherOutput($returned);
           
			return $this->response;
        }

        // Affiche l'erreur 404
        $this->response = $this->response->withStatus($e->getCode());

        echo $this->outputBufferingEnd();
        flush();

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
        $this->output = $this->outputBufferingEnd();

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

        // Ignorer les requêtes AJAX
        if (method_exists($this->request, 'isAJAX') && $this->request->isAJAX()) {
            return;
        }

        // Ignorer les reponses non-HTML
        if (strpos($this->response->getHeaderLine('Content-Type'), 'text/html') === false) {
            return;
        }

        // Ceci est principalement nécessaire lors des tests ...
        if (is_string($uri)) {
            $uri = Services::uri($uri, false);
        }

		Services::session()->setPreviousUrl(Uri::createURIString(
			$uri->getScheme(),
			$uri->getAuthority(),
			$uri->getPath(),
			$uri->getQuery(),
			$uri->getFragment()
		));
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
     * Construit une reponse adequate en fonction du retour du controleur
     *
     * @param mixed $returned
     */
    protected function formatResponse(ResponseInterface $response, $returned): ResponseInterface
    {
        if ($returned instanceof ResponseInterface) {
            return $returned;
        }

		if ($returned instanceof Responsable) {
			return $returned->toResponse($this->request);
		}

        if (is_object($returned)) {
            if (method_exists($returned, '__toString')) {
                $returned = $returned->__toString();
            } elseif (method_exists($returned, 'toArray')) {
                $returned = $returned->toArray();
            } elseif (method_exists($returned, 'toJSON')) {
                $returned = $returned->toJSON();
            } else {
                $returned = (array) $returned;
            }
        }

        if (is_array($returned)) {
            $returned = Helpers::collect($returned);
            $response = $response->withHeader('Content-Type', 'application/json');
        }

        try {
            $response = $response->withBody(to_stream($returned));
        } catch (InvalidArgumentException $e) {
        }

        return $response;
    }

    /**
     * Initialise le gestionnaire de middleware
     */
    protected function initMiddlewareQueue(): void
    {
        $this->middleware = Services::injector()->make(Middleware::class, [
            'response' => $this->response,
            'path'     => $this->determinePath(),
        ]);

        $middlewaresFile = CONFIG_PATH . 'middlewares.php';
        if (file_exists($middlewaresFile) && ! in_array($middlewaresFile, get_included_files(), true)) {
            $middleware = require $middlewaresFile;
            if (is_callable($middleware)) {
                $middleware($this->middleware, $this->request);
            }
        }

        $this->middleware->prepend($this->spoofRequestMethod());
    }

	protected function outputBufferingStart(): void
    {
        $this->bufferLevel = ob_get_level();
       
		ob_start();
    }

    protected function outputBufferingEnd(): string
    {
        $buffer = '';

        while (ob_get_level() > $this->bufferLevel) {
            $buffer .= ob_get_contents();
            ob_end_clean();
        }

        return $buffer;
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

    private function bootApp(): callable
    {
        return function (ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface {
            try {
                $returned = $this->startController($request, $response);

                // Closure controller has run in startController().
                if (! is_callable($this->controller)) {
                    $controller = $this->createController($request, $response);

                    if (! method_exists($controller, '_remap') && ! is_callable([$controller, $this->method], false)) {
                        throw PageNotFoundException::methodNotFound($this->method);
                    }

                    // Y'a t-il un evenement "post_controller_constructor"
                    $this->event->trigger('post_controller_constructor');

                    $returned = $this->runController($controller);
                } else {
                    $this->timer->stop('controller_constructor');
                    $this->timer->stop('controller');
                }

                $this->event->trigger('post_system');

                return $this->formatResponse($response, $returned);
            } catch (ValidationException $e) {
                $code   = $e->getCode();
                $errors = $e->getErrors();
                if (empty($errors)) {
                    $errors = [$e->getMessage()];
                }

                if (is_string($this->controller)) {
					if (strtoupper($request->getMethod()) === 'POST') {
                        if (is_subclass_of($this->controller, RestController::class)) {
                            return $this->formatResponse($response->withStatus($code), [
                                'success' => false,
                                'code'    => $code,
                                'errors'  => $errors,
                            ]);
                        }
						if (is_subclass_of($this->controller, BaseController::class)) {
                            return Services::redirection()->back()->withInput()->withErrors($errors)->withStatus($code);
                        }
                    }
                } elseif (strtoupper($request->getMethod()) === 'POST') {
                    return Services::redirection()->back()->withInput()->withErrors($errors)->withStatus($code);
                }

                throw $e;
            }
        };
    }
}
