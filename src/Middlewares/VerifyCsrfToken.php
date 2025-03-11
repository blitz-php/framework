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

use BlitzPHP\Contracts\Http\ResponsableInterface;
use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Exceptions\EncryptionException;
use BlitzPHP\Exceptions\TokenMismatchException;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Session\Cookie\Cookie;
use BlitzPHP\Session\Cookie\CookieValuePrefix;
use BlitzPHP\Traits\Support\InteractsWithTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerifyCsrfToken implements MiddlewareInterface
{
    use InteractsWithTime;

    /**
     * Les URI qui doivent être exclus de la vérification CSRF.
     */
    protected array $except = [];

    /**
     * Indique si le cookie XSRF-TOKEN doit être défini dans la réponse.
     */
    protected bool $addHttpCookie = true;

    /**
     * Constructeur
     */
    public function __construct(protected EncrypterInterface $encrypter)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @param Request $request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isReading($request) || $this->runningUnitTests() || $this->inExceptArray($request) || $this->tokensMatch($request)) {
            return tap($handler->handle($request), function ($response) use ($request) {
                if ($this->shouldAddXsrfTokenCookie()) {
                    $this->addCookieToResponse($request, $response);
                }
            });
        }

        throw new TokenMismatchException('Erreur de jeton CSRF.');
    }

    /**
     * Détermine si la requête HTTP utilise un verbe « read ».
     */
    protected function isReading(Request $request): bool
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS'], true);
    }

    /**
     * Détermine si l'application exécute des tests unitaires.
     */
    protected function runningUnitTests(): bool
    {
        return is_cli() && on_test();
    }

    /**
     * Détermine si la requête comporte un URI qui doit faire l'objet d'une vérification CSRF.
     */
    protected function inExceptArray(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->pathIs($except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détermine si les jetons CSRF de session et d'entrée correspondent.
     */
    protected function tokensMatch(Request $request): bool
    {
        $token = $this->getTokenFromRequest($request);

        return is_string($request->session()->token())
               && is_string($token)
               && hash_equals($request->session()->token(), $token);
    }

    /**
     * Récupère le jeton CSRF de la requête.
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (! $token && $header = $request->header('X-XSRF-TOKEN')) {
            try {
                $token = CookieValuePrefix::remove($this->encrypter->decrypt($header));
            } catch (EncryptionException) {
                $token = '';
            }
        }

        return $token;
    }

    /**
     * Détermine si le cookie doit être ajouté à la réponse.
     */
    public function shouldAddXsrfTokenCookie(): bool
    {
        return $this->addHttpCookie;
    }

    /**
     * Ajoute le jeton CSRF aux cookies de la réponse.
     *
     * @param Response $response
     */
    protected function addCookieToResponse(Request $request, $response): ResponseInterface
    {
        if ($response instanceof ResponsableInterface) {
            $response = $response->toResponse($request);
        }

        if (! ($response instanceof Response)) {
            return $response;
        }

        $config = config('cookie');

        return $response->withCookie(Cookie::create('XSRF-TOKEN', $request->session()->token(), [
            'expires'  => $this->availableAt(config('session.expiration')),
            'path'     => $config['path'],
            'domain'   => $config['domain'],
            'secure'   => $config['secure'],
            'httponly' => false,
            'samesite' => $config['samesite'] ?? null,
        ]));
    }

    /**
     * Détermine si le contenu du cookie doit être sérialisé.
     */
    public static function serialized(): bool
    {
        return EncryptCookies::serialized('XSRF-TOKEN');
    }
}
