<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Middlewares;

use BlitzPHP\Cache\Handlers\BaseHandler;
use BlitzPHP\Contracts\Cache\CacheInterface;
use BlitzPHP\Contracts\RateLimiter\ResultInterface;
use BlitzPHP\Exceptions\RateLimitExceededException;
use BlitzPHP\Http\Request;
use BlitzPHP\RateLimiter\Strategies\BaseStrategy;
use BlitzPHP\RateLimiter\Throttler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware de rate limiting (throttling) pour les requêtes HTTP.
 *
 * Ce middleware applique une limitation de débit aux requêtes entrantes
 * en utilisant le service Throttler. Il prend en charge :
 *
 * - **Stratégies multiples** : TokenBucket, SlidingWindow, FixedWindow
 * - **Identification flexible** : IP, utilisateur, clé API, route, combiné
 * - **Coût variable** : Selon la méthode HTTP ou via callback
 * - **Blocage après dépassement** : Bloque temporairement les clients abusifs
 * - **Limiteurs nommés** : Réutilisation de configurations via le Throttler
 * - **Headers standards** : X-RateLimit-Limit, X-RateLimit-Remaining, etc.
 * - **Réponse intelligente** : JSON ou texte selon l'en-tête Accept
 *
 * Utilisation dans les routes :
 * ```
 * // Syntaxe positionnelle (ordre fixe)
 * Route::middleware(ThrottleRequests::class . ':60,1')
 *      ->get('/login', [LoginController::class, 'show']);
 *
 * // Syntaxe nommée (recommandée)
 * Route::middleware(ThrottleRequests::with(
 *     maxAttempts: 5,
 *     decayMinutes: 15,
 *     strategy: 'token_bucket',
 *     identifier: 'user',
 *     blockDuration: 30
 * ))->post('/login', [LoginController::class, 'login']);
 * ```
 */
class ThrottleRequests extends BaseMiddleware implements MiddlewareInterface
{
    /**
     * Nombre maximum de requêtes autorisées dans la fenêtre.
     */
    protected int $maxAttempts = 60;

    /**
     * Durée de la fenêtre en minutes.
     */
    protected int $decayMinutes = 1;

    /**
     * Préfixe pour les clés de cache.
     * Permet d'isoler différents ensembles de limites.
     */
    protected string $prefix = '';

    /**
     * Utiliser l'ID utilisateur au lieu de l'IP comme identifiant.
     * Si l'utilisateur n'est pas connecté, fallback sur l'IP.
     */
    protected bool $userBased = false;

    /**
     * Durée de blocage en minutes après dépassement de la limite.
     * 0 = pas de blocage supplémentaire (juste le délai normal de reset).
     */
    protected int $blockDuration = 0;

    /**
     * Stratégie de rate limiting à utiliser.
     * Valeurs acceptées : "token_bucket", "sliding_window", "fixed_window".
     */
    protected string $strategy = 'token_bucket';

    /**
     * Type d'identifiant pour reconnaître les clients.
     * Valeurs : "ip", "user", "api_key", "route", "combined".
     */
    protected string $identifier = 'ip';

    /**
     * Coût par défaut d'une requête en tokens.
     * Peut être "auto" pour un coût basé sur la méthode HTTP.
     */
    protected int|string $cost = 1;

    /**
     * Nom du limiteur nommé à utiliser (enregistré via Throttler::for()).
     * Null = utiliser la configuration directe du middleware.
     */
    protected ?string $limiter = null;

    /**
     * Callback personnalisé pour générer l'identifiant du client.
     * Reçoit la Request, doit retourner une string.
     *
     * @var (callable(Request): string)|null
     */
    protected $identifierCallback = null;

    /**
     * Callback personnalisé pour calculer le coût d'une requête.
     * Reçoit la Request, doit retourner un int.
     *
     * @var (callable(Request): int)|null
     */
    protected $costCallback = null;

    /**
     * Callback pour déterminer si le rate limiting doit être ignoré.
     * Reçoit la Request, doit retourner un bool.
     *
     * @var (callable(Request): bool)|null
     */
    protected $skipCallback = null;

    /**
     * Callback exécuté lorsqu'un client est bloqué.
     * Reçoit la clé de blocage (string) et la durée (int).
     * Utile pour logger ou notifier.
     *
     * @var (callable(string, int): void)|null
     */
    protected $blockCallback = null;

    /**
     * Liste des arguments acceptés en mode positionnel.
     * L'ordre dans ce tableau définit l'ordre des paramètres
     * dans la syntaxe `Middleware::class . ':60,1'`.
     *
     * @var array<int, string>
     */
    protected array $fillable = [
        'maxAttempts',
        'decayMinutes',
        'prefix',
        'userBased',
        'blockDuration',
        'strategy',
        'identifier',
        'cost',
        'limiter',
    ];

    /**
     * Messages d'erreur personnalisables.
     *
     * @var array{too_many_requests: string, blocked: string}
     */
    protected array $errorMessages = [
        'too_many_requests' => 'Too Many Requests.',
        'blocked'           => 'Your access is temporarily blocked due to excessive requests.',
    ];

    /**
     * Constructeur.
     *
     * @param CacheInterface  $cache     Instance du cache
     * @param Throttler|null  $throttler Service de rate limiting (auto-créé si null)
     */
    public function __construct(protected CacheInterface $cache, protected ?Throttler $throttler = null)
	{
		if ($cache instanceof BaseHandler) {
			$cache->setReservedCharacters('{}()/\\@');
		}

        if ($this->throttler === null) {
            $this->throttler = service('throttler', ['cache' => $cache], ! on_test());
        }
    }

    /**
     * Crée une chaîne de middleware avec paramètres nommés pour le routing.
     *
     * @param int         $maxAttempts  Nombre maximum de requêtes autorisées (défaut: 60)
     * @param int         $decayMinutes Durée de la fenêtre en minutes (défaut: 1)
     * @param string      $prefix       Préfixe pour les clés de cache (défaut: '')
     * @param bool        $userBased    Utiliser l'ID utilisateur au lieu de l'IP (défaut: false)
     * @param int         $blockDuration Durée de blocage en minutes après dépassement (0 = désactivé)
     * @param string      $strategy     Stratégie : "token_bucket", "sliding_window", "fixed_window"
     * @param string      $identifier   Type : "ip", "user", "api_key", "route", "combined"
     * @param int         $cost         Coût par défaut (1) ou "auto" pour coût basé sur la méthode HTTP
     * @param string|null $limiter      Nom du limiteur nommé à utiliser (prioritaire sur les autres options)
     *
     * @return string Chaîne au format "Classe:param1,param2,..." pour le routing
     */
    public static function with(
        int $maxAttempts = 60,
        int $decayMinutes = 1,
        string $prefix = '',
        bool $userBased = false,
        int $blockDuration = 0,
        string $strategy = 'token_bucket',
        string $identifier = 'ip',
        int $cost = 1,
        ?string $limiter = null,
    ): string {
        return static::class . ':' . implode(',', func_get_args());
    }

    /**
     * Définit un callback personnalisé pour générer l'identifiant du client.
     *
     * @param callable(Request):string $callback Fonction recevant la Request et retournant l'identifiant
     */
    public function withIdentifier(callable $callback): static
    {
        $this->identifierCallback = $callback;

        return $this;
    }

    /**
     * Définit un callback personnalisé pour calculer le coût d'une requête.
     *
     * @param callable(Request):int $callback Fonction recevant la Request et retournant le coût
     */
    public function withCost(callable $callback): static
    {
        $this->costCallback = $callback;

        return $this;
    }

    /**
     * Définit une condition pour ignorer le rate limiting.
     *
     * @param callable(Request):bool $callback Fonction retournant true pour skipper
     *
     * @example
     * $middleware->skipWhen(fn($req) => $req->getIpAddress() === '10.0.0.1');
     */
    public function skipWhen(callable $callback): static
    {
        $this->skipCallback = $callback;

        return $this;
    }

    /**
     * Définit un callback exécuté lorsqu'un client est bloqué.
     *
     * @param callable(string, int):void $callback Reçoit la clé de blocage et la durée en minutes
     *
     * @example
     * $middleware->onBlocked(function($key, $duration) {
     *     logger()->warning("Rate limit block", ['key' => $key, 'duration' => $duration]);
     * });
     */
    public function onBlocked(callable $callback): static
    {
        $this->blockCallback = $callback;

        return $this;
    }

    /**
     * Définit les messages d'erreur personnalisés.
     *
     * @param array{too_many_requests?: string, blocked?: string} $messages
     *
     * @example
     * $middleware->setErrorMessages([
     *     'too_many_requests' => 'Trop de requêtes.',
     *     'blocked'           => 'Vous êtes temporairement bloqué.',
     * ]);
     */
    public function setErrorMessages(array $messages): static
    {
        $this->errorMessages = array_merge($this->errorMessages, $messages);

        return $this;
    }

    /**
     * Traite la requête entrante en appliquant le rate limiting.
     *
     * Flux de traitement :
     * 1. Vérifie si la requête doit être ignorée (skip)
     * 2. Résout la configuration (limiteur nommé ou direct)
     * 3. Configure la stratégie si nécessaire
     * 4. Détermine l'identifiant unique du client
     * 5. Construit la clé de cache
     * 6. Vérifie si le client est bloqué
     * 7. Détermine la limite, la fenêtre et le coût
     * 8. Tente la requête via la stratégie
     * 9. Si limité, bloque éventuellement et lève une exception
     * 10. Si autorisé, traite la requête et ajoute les headers
     *
     * @param Request  $request La requête entrante
     *
     * @throws RateLimitExceededException Si la limite est dépassée
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->shouldSkip($request)) {
            return $handler->handle($request);
        }

        $config = $this->resolveConfig($request);

        $this->resolveStrategy($config);

        $identifier = $this->resolveIdentifier($request);
        $key        = $this->buildKey($request, $identifier);
        $blockKey   = $key . ':block';

        // Vérifier le blocage
        if ($this->isBlocked($blockKey)) {
            return $this->createBlockedResponse($blockKey);
        }

        // Déterminer la limite et la fenêtre
        $maxAttempts  = $config['maxAttempts'] ?? $this->maxAttempts;
        $decayMinutes = $config['decayMinutes'] ?? $this->decayMinutes;
        $decaySeconds = $decayMinutes * 60;

        // Déterminer le coût de cette requête
        $cost = $this->resolveCost($request, $config);

        // Tenter la requête via le Throttler
        try {
            $strategy = $this->throttler->getStrategy();
            $result   = $strategy->attempt($key, $maxAttempts, $decaySeconds, $cost);

            // Si limite dépassée → réponse 429 et éventuellement bloquer
            if (!$result->isAllowed()) {
                if ($this->blockDuration > 0) {
                    $this->blockUser($blockKey);
                }

                throw new RateLimitExceededException(
                    retryAfter: $result->retryAfter ?: $decaySeconds,
                    headers: $result->toHeaders(),
                    message: $this->errorMessages['too_many_requests']
                );
            }

            // Traitement normal de la requête
            $response = $handler->handle($request);

            // Ajout des headers standards
            return $this->addRateLimitHeaders($response, $result);
        } catch (RateLimitExceededException $e) {
            return $this->handleRateLimitException($e);
        }
    }

    /**
     * Vérifie si le rate limiting doit être ignoré pour cette requête.
     *
     * Vérifications :
     * 1. IP whitelistée (config whitelist_ips)
     * 2. Callback skip personnalisé
     *
     * @return bool True si la requête doit être ignorée
     */
    protected function shouldSkip(Request $request): bool
    {
        // IPs whitelistées
        // $whitelistedIps = $this->config['whitelist_ips'] ?? ['127.0.0.1', '::1'];
        $whitelistedIps = ['127.0.0.1', '::1'];
        if (in_array($request->clientIp(), $whitelistedIps)) {
            return true;
        }

        // Callback personnalisé
        if ($this->skipCallback && is_callable($this->skipCallback)) {
            return (bool) call_user_func($this->skipCallback, $request);
        }

        return false;
    }

    /**
     * Résout la configuration de rate limiting pour cette requête.
     *
     * Priorités :
     * 1. Limiteur nommé défini dans les attributs de route
     * 2. Limiteur nommé défini dans la propriété $limiter
     * 3. Configuration directe du middleware
     *
     * @return array{decayMinutes: int, maxAttempts: int}
     */
    protected function resolveConfig(Request $request): array
    {
        // Priorité 1 : limiter nommé dans les attributs de route
        if (null !== $config = $this->retrieveLimit($request->getAttribute('throttler'), $request)) {
            return $config;
        }

        // Priorité 2 : limiter nommé dans la config du middleware
        if (null !== $config = $this->retrieveLimit($this->limiter, $request)) {
            return $config;
        }

        // Priorité 3 : configuration directe du middleware
        return [
            'maxAttempts'  => $this->maxAttempts,
            'decayMinutes' => $this->decayMinutes,
        ];
    }

    /**
     * Récupère la configuration d'un limiteur nommé.
     *
     * @param string|null $name    Nom du limiteur
     * @param Request     $request La requête (passée au callable du limiteur)
     *
     * @return array{decayMinutes: int, maxAttempts: int}|null
     */
    protected function retrieveLimit(?string $name, Request $request): ?array
    {
        if ($name && $limiter = $this->throttler->limiter($name)) {
            $limits = $limiter($request);
            if (!empty($limits)) {
                $limit = is_array($limits) ? $limits[0] : $limits;
                return [
                    'maxAttempts'  => $limit->maxAttempts,
                    'decayMinutes' => (int) ceil($limit->decaySeconds / 60),
                ];
            }
        }

        return null;
    }

    /**
     * Configure la stratégie si elle diffère de l'actuelle.
     *
     * @param array $config Configuration résolue
     */
    protected function resolveStrategy(array $config): void
    {
        $strategy = BaseStrategy::named($config['strategy'] ?? $this->strategy);

        if (class_exists($strategy) && ! is_a($this->throttler->getStrategy(), $strategy)) {
            $this->throttler->setStrategy($strategy);
        }
    }

    /**
     * Résout l'identifiant du client selon le type configuré.
     *
     * @return string Identifiant unique du client
     */
    protected function resolveIdentifier(Request $request): string
    {
        // Callback personnalisé prioritaire
        if ($this->identifierCallback && is_callable($this->identifierCallback)) {
            return (string) call_user_func($this->identifierCallback, $request);
        }

        $type = $this->identifier;

        // Si userBased est true, forcer le type 'user'
        if ($this->userBased) {
            $type = 'user';
        }

        return match ($type) {
            'user'     => $this->getUserIdentifier($request),
            'api_key'  => $this->getApiKeyIdentifier($request),
            'route'    => $this->getRouteIdentifier($request),
            'combined' => $this->getCombinedIdentifier($request),
            default    => $this->getIpIdentifier($request),
        };
    }

    /**
     * Résout le coût de la requête.
     *
     * @param array $config Configuration résolue
     *
     * @return int Coût en tokens
     */
    protected function resolveCost(ServerRequestInterface $request, array $config): int
    {
        // Callback personnalisé prioritaire
        if ($this->costCallback && is_callable($this->costCallback)) {
            return (int) call_user_func($this->costCallback, $request);
        }

        $cost = $config['cost'] ?? $this->cost;

        // Coût automatique selon la méthode HTTP
        if ($cost === 'auto') {
            return match (strtoupper($request->getMethod())) {
                'GET', 'HEAD', 'OPTIONS' => 1,
                'POST', 'PUT', 'PATCH'   => 2,
                'DELETE'                 => 3,
                default                  => 1,
            };
        }

        return (int) $cost;
    }

    /**
     * Identifie le client par son adresse IP.
     *
     * @return string Identifiant au format "ip:xxx.xxx.xxx.xxx"
     */
    protected function getIpIdentifier(Request $request): string
    {
        return 'ip:' . $request->clientIp();
    }

    /**
     * Identifie le client par l'ID de l'utilisateur connecté.
     * Fallback sur l'IP si l'utilisateur n'est pas connecté.
     *
     * @return string Identifiant au format "user:123" ou "ip:xxx"
     */
    protected function getUserIdentifier(Request $request): string
    {
        if (function_exists('auth') && null !== $userId = auth()->id()) {
            return 'user:' . $userId;
        }

        return $this->getIpIdentifier($request);
    }

    /**
     * Identifie le client par sa clé API.
     * Sources vérifiées (dans l'ordre) : X-API-Key, Bearer token, IP.
     *
     * @return string Identifiant au format "api_key:hash"
     */
    protected function getApiKeyIdentifier(Request $request): string
    {
        $apiKey = $request->getHeaderLine('X-API-Key')
            ?: $request->bearerToken()
            ?: $request->clientIp();

        return 'api_key:' . hash('xxh3', $apiKey);
    }

    /**
     * Identifie le client par la route et l'IP combinées.
     *
     * @return string Identifiant au format "route:hash"
     */
    protected function getRouteIdentifier(Request $request): string
    {
        $route = $request->getUri()->getPath();
        $ip    = $request->clientIp();

        return 'route:' . hash('xxh3', $route . '|' . $ip);
    }

    /**
     * Identifie le client par une combinaison de plusieurs facteurs.
     * Combine : utilisateur (si connecté), IP et route.
     *
     * @return string Identifiant au format "combined:hash"
     */
    protected function getCombinedIdentifier(Request $request): string
    {
        $parts = [];

        if (function_exists('auth') && $user = auth()->user()) {
            $parts[] = 'user_' . $user->id;
        }
        $parts[] = 'ip_' . $request->clientIp();
        $parts[] = 'route_' . $request->getUri()->getPath();

        return 'combined:' . hash('xxh3', implode('|', $parts));
    }

    /**
     * Construit la clé de cache finale.
     *
     * Combine l'identifiant et le chemin pour créer une clé unique et relativement lisible.
     * Utilise un hash partiel (16 caractères) pour limiter la longueur tout en évitant les collisions.
     *
     * @param string $identifier Identifiant du client
     *
     * @return string Clé de cache au format "throttle:[prefix:]hash"
     */
    protected function buildKey(ServerRequestInterface $request, string $identifier): string
    {
        $path       = $request->getUri()->getPath();
        $prefixPart = $this->prefix ? $this->prefix . ':' : '';

        return 'throttle:' . $prefixPart . substr(hash('sha256', $identifier . '|' . $path), 0, 16);
    }

    /**
     * Vérifie si un client est actuellement bloqué.
     *
     * @param string $blockKey Clé de blocage dans le cache
     *
     * @return bool True si le client est bloqué
     */
    protected function isBlocked(string $blockKey): bool
    {
        return $this->cache->has($blockKey);
    }

    /**
     * Bloque un client pour la durée configurée.
     *
     * Stocke le timestamp d'expiration du blocage dans le cache
     * et exécute le callback onBlocked si défini.
     *
     * @param string $blockKey Clé de blocage
     */
    protected function blockUser(string $blockKey): void
    {
        $blockSeconds = $this->blockDuration * 60;
        $this->cache->set($blockKey, time() + $blockSeconds, $blockSeconds);

        // Callback de blocage (ex: logger, notification)
        if ($this->blockCallback && is_callable($this->blockCallback)) {
            call_user_func($this->blockCallback, $blockKey, $this->blockDuration);
        }
    }

    /**
     * Crée une réponse HTTP pour un client bloqué.
     *
     * @param string $blockKey Clé de blocage
     *
     * @return ResponseInterface Réponse 429 avec headers de blocage
     */
    protected function createBlockedResponse(string $blockKey): ResponseInterface
    {
        $blockExpiry = $this->cache->get($blockKey, time());
        $retryAfter  = max(1, $blockExpiry - time());

        $response = service('response')
            ->withStatus(429)
            ->withHeader('Retry-After', (string) $retryAfter)
            ->withHeader('X-RateLimit-Blocked', 'true')
            ->withHeader('X-RateLimit-Block-Reset', (string) $blockExpiry);

        return $this->formatErrorResponse($response, $this->errorMessages['blocked']);
    }

    /**
     * Gère une exception de rate limiting en la convertissant en réponse HTTP.
     *
     * @param RateLimitExceededException $e L'exception à convertir
     *
     * @return ResponseInterface Réponse 429 formatée
     */
    protected function handleRateLimitException(RateLimitExceededException $e): ResponseInterface
    {
        $response = service('response')
            ->withStatus($e->getCode())
            ->withHeader('X-RateLimit-Exceeded', 'true');

        // Ajouter tous les headers de l'exception
        foreach ($e->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, (string) $value);
        }

        return $this->formatErrorResponse($response, $e->getMessage());
    }

    /**
     * Formate la réponse d'erreur selon l'en-tête Accept du client.
     *
     * Si le client accepte application/json → réponse JSON
     * Sinon → réponse texte brut
     *
     * @param ResponseInterface $response La réponse en cours de construction
     * @param string            $message  Le message d'erreur
     *
     * @return ResponseInterface La réponse formatée
     */
    protected function formatErrorResponse(ResponseInterface $response, string $message): ResponseInterface
    {
        $acceptHeader = $response->getHeaderLine('Accept') ?:
                        (isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '');

        if (str_contains($acceptHeader, 'application/json')) {
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withBody(to_stream(json_encode([
                    'error'   => true,
                    'message' => $message,
                ])));
        }

        return $response
            ->withHeader('Content-Type', 'text/plain')
            ->withBody(to_stream($message));
    }

    /**
     * Ajoute les headers de rate limiting à la réponse.
     *
     * @param ResponseInterface $response La réponse
     * @param ResultInterface   $result   Le résultat de la tentative
     *
     * @return ResponseInterface La réponse avec les headers ajoutés
     */
    protected function addRateLimitHeaders(ResponseInterface $response, ResultInterface $result): ResponseInterface
    {
        foreach ($result->toHeaders() as $name => $value) {
            $response = $response->withHeader($name, (string) $value);
        }

        return $response;
    }
}
