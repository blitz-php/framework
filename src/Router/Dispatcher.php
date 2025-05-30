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
use BlitzPHP\Container\Container;
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Contracts\Http\ResponsableInterface;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Controllers\BaseController;
use BlitzPHP\Debug\Timer;
use BlitzPHP\Enums\Method;
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Exceptions\RedirectException;
use BlitzPHP\Exceptions\ValidationException;
use BlitzPHP\Http\MiddlewareQueue;
use BlitzPHP\Http\MiddlewareRunner;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Http\Uri;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\String\Text;
use BlitzPHP\Validation\ErrorBag;
use Closure;
use Exception;
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
     * @var Request
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

    private ?MiddlewareQueue $middleware = null;

    /**
     * Contrôleur à utiliser.
     *
     * @var (Closure(mixed...): ResponseInterface|string)|string
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
     * Niveau de mise en mémoire tampon de sortie de l'application
     */
    protected int $bufferLevel = 0;

    /**
     * Mise en cache des pages Web
     */
    protected ResponseCache $pageCache;

    /**
     * Constructeur.
     */
    public function __construct(protected EventManagerInterface $event, protected Container $container)
    {
        $this->startTime = microtime(true);
        $this->config    = (object) config('app');

        $this->pageCache = service('responsecache');
    }

    /**
     * Retourne la methode invoquee
     */
    public static function getMethod(): ?string
    {
        $method = Services::singleton(self::class)->method;
        if (empty($method)) {
            $method = service('routes')->getDefaultMethod();
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
        $routes = service('routes');

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

        $this->event->emit('pre_system');

        $this->timer->stop('bootstrap');

        $this->initMiddlewareQueue();

        try {
            $this->response = $this->handleRequest($routes, config('cache'));
        } catch (ResponsableInterface $e) {
            $this->outputBufferingEnd();
            $this->response = $e->getResponse();
        } catch (PageNotFoundException $e) {
            $this->response = $this->display404errors($e);
        } catch (Throwable $e) {
            $this->outputBufferingEnd();

            throw $e;
        }

        // Y a-t-il un événement post-système ?
        $this->event->emit('post_system');

        if ($returnResponse) {
            return $this->response;
        }

        return $this->sendResponse();
    }

    /**
     * Gère la logique de requête principale et déclenche le contrôleur.
     *
     * @throws PageNotFoundException
     * @throws RedirectException
     */
    protected function handleRequest(?RouteCollectionInterface $routes = null, ?array $cacheConfig = null): ResponseInterface
    {
        $routeMiddlewares = $this->dispatchRoutes($routes);

        /**
         * Ajouter des middlewares de routes
         */
        foreach ($routeMiddlewares as $middleware) {
            $this->middleware->append($middleware);
        }

        $this->middleware->append($this->bootApp());

        // Enregistrer notre URI actuel en tant qu'URI précédent dans la session
        // pour une utilisation plus sûre et plus précise avec la fonction d'assistance `previous_url()`.
        $this->storePreviousURL(current_url(true));

        return (new MiddlewareRunner())->run($this->middleware, $this->request);
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

        $this->timer = service('timer');
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

        $this->request = service('request')->withProtocolVersion($version);
    }

    /**
     * Obtenez notre objet Response et définissez des valeurs par défaut, notamment
     * la version du protocole HTTP et une réponse réussie par défaut.
     */
    protected function getResponseObject()
    {
        // Supposons le succès jusqu'à preuve du contraire.
        $this->response = service('response')->withStatus(200);

        if (! is_cli() || on_test()) {
        }

        $this->response = $this->response->withProtocolVersion($this->request->getProtocolVersion());
    }

    /**
     * Renvoie un tableau avec nos statistiques de performances de base collectées.
     */
    public function getPerformanceStats(): array
    {
        // Après le filtre, la barre d'outils de débogage nécessite 'total_execution'.
        $this->totalTime = $this->timer->getElapsedTime('total_execution');

        return [
            'startTime' => $this->startTime,
            'totalTime' => $this->totalTime,
        ];
    }

    /**
     * Fonctionne avec le routeur pour
     * faire correspondre une route à l'URI actuel. Si la route est une
     * "route de redirection", gérera également la redirection.
     *
     * @param RouteCollectionInterface|null $routes Une interface de collecte à utiliser à la place
     *                                              du fichier de configuration.
     *
     * @return list<string>
     *
     * @throws RedirectException
     */
    protected function dispatchRoutes(?RouteCollectionInterface $routes = null): array
    {
        $this->timer->start('routing');

        if ($routes === null) {
            $routes = service('routes')->loadRoutes();
        }

        // $routes est defini dans app/Config/routes.php
        $this->router = service('router', $routes, $this->request);

        $this->outputBufferingStart();

        $this->controller = $this->router->handle($this->request->getPath());
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
     * Maintenant que tout a été configuré, cette méthode tente d'exécuter le
     * méthode du contrôleur et lancez le script. S'il n'en est pas capable, le fera
     * afficher l'erreur Page introuvable appropriée.
     */
    protected function startController()
    {
        $this->timer->start('controller');
        $this->timer->start('controller_constructor');

        // Aucun contrôleur spécifié - nous ne savons pas quoi faire maintenant.
        if (empty($this->controller)) {
            throw PageNotFoundException::emptyController();
        }

        // Est-il acheminé vers une Closure ?
        if (is_object($this->controller) && ($this->controller::class === 'Closure')) {
            if (empty($returned = $this->container->call($this->controller, $this->router->params()))) {
                $returned = $this->outputBufferingEnd();
            }

            return $returned;
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
     * @return BaseController|mixed
     */
    private function createController(ServerRequestInterface $request, ResponseInterface $response)
    {
        /**
         * @var BaseController
         */
        $class = $this->container->get($this->controller);

        if (method_exists($class, 'initialize')) {
            $class->initialize($request, $response, service('logger'));
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
        $params = $this->router->params();
        $method = $this->method;

        if (method_exists($class, '_remap')) {
            $params = [$method, $params];
            $method = '_remap';
        }

        if (empty($output = $this->container->call([$class, $method], $params))) {
            $output = $this->outputBufferingEnd();
        }

        $this->timer->stop('controller');

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
                $returned   = $controller->{$this->method}($e->getMessage());

                $this->timer->stop('controller');
            }

            unset($override);

            $this->gatherOutput($returned);

            return $this->response;
        }

        // Affiche l'erreur 404
        $this->response = $this->response->withStatus($e->getCode());

        $this->outputBufferingEnd();

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
     * @param string|Uri $uri
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
        if (! str_contains($this->response->getHeaderLine('Content-Type'), 'text/html')) {
            return;
        }

        // Ceci est principalement nécessaire lors des tests ...
        if (is_string($uri)) {
            $uri = single_service('uri', $uri);
        }

        session()->setPreviousUrl(Uri::createURIString(
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
        if (! $this->isAjaxRequest()) {
            $this->response = service('toolbar')->process(
                $this->getPerformanceStats(),
                $this->request,
                $this->response
            );
        }

        service('emitter')->emit($this->response);
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

        if ($returned instanceof ResponsableInterface) {
            return $returned->toResponse($this->request);
        }

        if ($returned instanceof Arrayable) {
            $returned = $returned->toArray();
        }

        if (is_object($returned)) {
            if (method_exists($returned, 'toArray')) {
                $returned = $returned->toArray();
            } elseif (method_exists($returned, 'toJSON')) {
                $returned = $returned->toJSON();
            } elseif (method_exists($returned, '__toString')) {
                $returned = $returned->__toString();
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
        } catch (InvalidArgumentException) {
        }

        return $response;
    }

    /**
     * Initialise le gestionnaire de middleware
     */
    protected function initMiddlewareQueue(): void
    {
        $this->middleware = new MiddlewareQueue($this->container, [], $this->request, $this->response);

        $this->middleware->append($this->spoofRequestMethod());
        $this->middleware->register(/** @scrutinizer ignore-type */ config('middlewares'));
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
            // Accepte seulement PUT, PATCH, DELETE
            if ($request->getMethod() === Method::POST && ! empty($post['_method']) && in_array($post['_method'], [Method::PUT, Method::PATCH, Method::DELETE], true)) {
                $request = $request->withMethod($post['_method']);
            }

            return $next($request, $response);
        };
    }

    /**
     * Démarre l'application en configurant la requete et la réponse,
     * en exécutant le contrôleur et en gérant les exceptions de validation.
     *
     * Cette méthode renvoie un objet callable qui sert de middleware pour le cycle requête-réponse de l'application.
     */
    private function bootApp(): callable
    {
        return function (ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface {
            Services::set(Request::class, $request);
            Services::set(Response::class, $response);

            try {
                $returned = $this->startController();

                // Les controleur sous forme de Closure sont executes dans startController().
                if (! is_callable($this->controller)) {
                    $controller = $this->createController($request, $response);

                    if (! method_exists($controller, '_remap') && ! is_callable([$controller, $this->method], false)) {
                        throw PageNotFoundException::methodNotFound($this->method);
                    }

                    // Y'a t-il un evenement "post_controller_constructor"
                    $this->event->emit('post_controller_constructor');

                    $returned = $this->runController($controller);
                } else {
                    $this->timer->stop('controller_constructor');
                    $this->timer->stop('controller');
                }

                $this->event->emit('post_system');

                return $this->formatResponse($response, $returned);
            } catch (ValidationException $e) {
                return $this->formatValidationResponse($e, $request, $response);
            }
        };
    }

    /**
     * Formattage des erreurs de validation
     */
    private function formatValidationResponse(ValidationException $e, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $code = $e->getCode();
        if (null === $errors = $e->getErrors()) {
            $errors = [$e->getMessage()];
        }

        if (in_array($request->getMethod(), [Method::OPTIONS, Method::HEAD], true)) {
            throw $e;
        }

        if ($this->isAjaxRequest()) {
            return $this->formatResponse($response->withStatus($code), [
                'success' => false,
                'code'    => $code,
                'errors'  => $errors instanceof ErrorBag ? $errors->all() : $errors,
            ]);
        }

        return back()->withInput()->withErrors($errors)->withStatus($code);
    }

    /**
     * Verifie que la requete est xhr/fetch pour eviter d'afficher la toolbar dans la reponse
     */
    private function isAjaxRequest(): bool
    {
        return $this->request->expectsJson()
                || $this->request->isJson()
                || $this->request->is('ajax')
                || $this->request->hasHeader('Hx-Request')
                || Text::contains($this->response->getType(), ['/json', '+json'])
                || Text::contains($this->response->getType(), ['/xml', '+xml']);
    }
}
