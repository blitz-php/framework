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

use BlitzPHP\Contracts\Cache\CacheInterface;
use BlitzPHP\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware de rate limiting (throttling)
 *
 * Limite le nombre de requêtes par IP (et par endpoint) sur une période donnée.
 * Utilise le cache du framework pour stocker le compteur.
 *
 * Exemple d'utilisation dans les routes :
 *   - Route::get('/login', [Controller::class, 'showLogin'])->middleware(ThrottleRequests::class . ':60,1');
 *   - Route::get('/api/data', [Controller::class, 'getData'])->middleware(ThrottleRequests::with(100, 5));
 *
 * Paramètres (positionnels ou nommés grâce à BaseMiddleware) :
 *   - maxAttempts : nombre maximum de requêtes autorisées (défaut : 60)
 *   - decayMinutes : durée de la fenêtre en minutes (défaut : 1)
 *   - prefix : préfixe pour les clés de cache (défaut : '')
 *   - userBased : utiliser l'ID utilisateur au lieu de l'IP quand disponible (défaut : false)
 *   - blockDuration : durée de blocage en minutes après dépassement (0 = pas de blocage supplémentaire)
 *
 * Le middleware ajoute automatiquement les headers standards :
 *   - X-RateLimit-Limit
 *   - X-RateLimit-Remaining
 *   - X-RateLimit-Reset (timestamp Unix du reset)
 *   - Retry-After (en cas de dépassement)
 *
 * En cas de dépassement, renvoie une réponse 429 Too Many Requests.
 */
class ThrottleRequests extends BaseMiddleware implements MiddlewareInterface
{
    /**
     * Valeurs par défaut
     */
    protected int $maxAttempts = 60;
    protected int $decayMinutes = 1;
    protected string $prefix = '';
    protected bool $userBased = false;
    protected int $blockDuration = 0;

    /**
     * Liste des arguments acceptés
     */
    protected array $fillable = [
        'maxAttempts',
        'decayMinutes',
        'prefix',
        'userBased',
        'blockDuration'
    ];

    /**
     * @var array Configuration des messages d'erreur
     */
    protected array $errorMessages = [
        'too_many_requests' => 'Too Many Requests.',
        'blocked' => 'Your access is temporarily blocked due to excessive requests.',
    ];

    /**
     * Constructeur
     */
    public function __construct(protected CacheInterface $cache)
    {
    }

    /**
     * Méthode fluide pour générer la chaîne middleware avec paramètres
     */
    public static function with(
        int $maxAttempts = 60,
        int $decayMinutes = 1,
        string $prefix = '',
        bool $userBased = false,
        int $blockDuration = 0
    ): string {
        return static::class . ':' . implode(',', func_get_args());
    }

    /**
     * {@inheritDoc}
	 *
	 * @param Request $request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $this->generateKey($request);
        $blockKey = $key . ':block';

        // Vérifier si l'utilisateur/IP est bloqué
        if ($this->isBlocked($blockKey)) {
			return $this->createBlockedResponse($blockKey);
        }

        $timerKey = $key . ':timer';
        $decaySeconds = $this->decayMinutes * 60;
        $hits = $this->getOrInitializeCounter($key, $timerKey, $decaySeconds);

        $remaining = max(0, $this->maxAttempts - $hits);
        $resetIn   = $this->getResetTime($timerKey, $decaySeconds);

        // Si limite dépassée → réponse 429 et éventuellement bloquer
        if ($hits > $this->maxAttempts) {
            if ($this->blockDuration > 0) {
                $this->blockUser($blockKey);
            }

            return $this->createRateLimitExceededResponse($resetIn);
        }

        // Traitement normal de la requête
        $response = $handler->handle($request);

        // Ajout des headers standards
        return $this->addRateLimitHeaders($response, $remaining, $resetIn);
    }

    /**
     * Génère une clé unique pour la limitation
     */
    protected function generateKey(Request $request): string
    {
		$identifier = $this->getIdentifier($request);
        $path       = $request->getUri()->getPath();
        $prefixPart = $this->prefix ? $this->prefix . ':' : '';

        return 'throttle:' . $prefixPart . sha1($identifier . '|' . $path);
    }

    /**
     * Récupère l'identifiant (IP ou ID utilisateur)
     */
    protected function getIdentifier(Request $request): string
    {
        if ($this->userBased && function_exists('auth')) {
            if (null !== $userId = auth()->id()) {
                return 'user:' . $userId;
            }
        }

        return 'ip:' . $request->clientIp();
    }

    /**
     * Récupère ou initialise le compteur
     */
    protected function getOrInitializeCounter(string $key, string $timerKey, int $decaySeconds): int
    {
        if (! $this->cache->has($timerKey)) {
            $resetTime = time() + $decaySeconds;
            $this->cache->set($timerKey, $resetTime, $decaySeconds);
            $this->cache->set($key, 1, $decaySeconds);

            return 1;
        }

        $hits = $this->cache->increment($key);

        // Rafraîchir le TTL du compteur à chaque hit (évite expiration prématurée)
        if (method_exists($this->cache, 'touch')) {
            $this->cache->touch($key, $decaySeconds);
            $this->cache->touch($timerKey, $decaySeconds);
        }

        return $hits ?: 1;
    }

	/**
     * Calcule le temps restant avant reset
     */
    protected function getResetTime(string $timerKey, int $decaySeconds): int
    {
        if (method_exists($this->cache, 'ttl')) {
            $ttl = $this->cache->ttl($timerKey);
            if ($ttl !== false && $ttl > 0) {
                return $ttl;
            }
        }

        // Fallback : utiliser le timestamp stocké
        $resetTime = $this->cache->get($timerKey);
        if ($resetTime) {
            return max(1, $resetTime - time());
        }

        return $decaySeconds;
    }

    /**
     * Vérifie si l'utilisateur/IP est bloqué
     */
    protected function isBlocked(string $blockKey): bool
    {
        return $this->cache->has($blockKey);
    }

    /**
     * Bloque l'utilisateur/IP pour une durée spécifiée
     */
    protected function blockUser(string $blockKey): void
    {
        $blockSeconds = $this->blockDuration * 60;
        $this->cache->set($blockKey, time(), $blockSeconds);
    }

    /**
     * Crée une réponse pour un utilisateur/IP bloqué
     */
    protected function createBlockedResponse(string $blockKey): ResponseInterface
    {
        // $blockExpiry = $this->cache->getMetadata($blockKey)['expire'] ?? time();
        $blockExpiry = time();
        $retryAfter = max(1, $blockExpiry - time());

        $response = service('response')
            ->withStatus(429)
            ->withHeader('Retry-After', (string) $retryAfter)
            ->withHeader('X-RateLimit-Blocked', 'true')
            ->withHeader('X-RateLimit-Block-Reset', (string) $blockExpiry);

		return $this->formatErrorResponse($response, $this->errorMessages['blocked']);
    }

    /**
     * Réponse en cas de dépassement de limite
     */
    protected function createRateLimitExceededResponse(int $retryAfter): ResponseInterface
    {
        $response = service('response')
            ->withStatus(429)
            ->withHeader('Retry-After', (string) $retryAfter)
            ->withHeader('X-RateLimit-Exceeded', 'true');

        return $this->formatErrorResponse($response, $this->errorMessages['too_many_requests']);
    }

	/**
     * Formatage intelligent de la réponse d'erreur (JSON ou texte selon Accept)
     */
    protected function formatErrorResponse(ResponseInterface $response, string $message): ResponseInterface
    {
        if (str_contains($response->getHeaderLine('Accept'), 'application/json')) {
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withBody(to_stream(json_encode([
                    'message' => $message,
                ])));
        }

        return $response
            ->withHeader('Content-Type', 'text/plain')
            ->withBody(to_stream($message));
    }

    /**
     * Ajoute les headers de rate limiting à la réponse
     */
    protected function addRateLimitHeaders(ResponseInterface $response, int $remaining, int $resetIn): ResponseInterface {
        $headers = [
            'X-RateLimit-Limit'     => (string) $this->maxAttempts,
            'X-RateLimit-Remaining' => (string) $remaining,
            'X-RateLimit-Reset'     => (string) (time() + $resetIn),
        ];

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * Définit les messages d'erreur personnalisés
     */
    public function setErrorMessages(array $messages): self
    {
        $this->errorMessages = array_merge($this->errorMessages, $messages);

        return $this;
    }
}
