<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http;

use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Session\Store;
use BlitzPHP\Traits\Macroable;
use BlitzPHP\Utilities\Iterable\Arr;
use BlitzPHP\Utilities\String\Text;
use Closure;

/**
 * Générateur d'URL
 *
 * @credit <a href="http://laravel.com">Laravel - \Illuminate\Routing\UrlGenerator</a>
 */
class UrlGenerator
{
    use Macroable;

    /**
     * Racine forcée de l'URL.
     */
    protected string $forcedRoot = '';

    /**
     * Schéma forcé pour les URLs.
     */
    protected string $forceScheme = '';

    /**
     * Copie mise en cache de la racine de l'URL pour la requête actuelle.
     */
    protected ?string $cachedRoot = null;

    /**
     * Copie mise en cache du schéma de l'URL pour la requête actuelle.
     */
    protected ?string $cachedScheme = null;

    /**
     * Espace de noms racine appliqué aux actions des contrôleurs.
     */
    protected string $rootNamespace = '';

    /**
     * Fonction de résolution de session.
     *
     * @var callable
     */
    protected $sessionResolver;

    /**
     * Fonction de résolution de clé de chiffrement.
     *
     * @var callable
     */
    protected $keyResolver;

    /**
     * Callback utilisé pour formater les hôtes.
     *
     * @var Closure
     */
    protected $formatHostUsing;

    /**
     * Callback utilisé pour formater les chemins.
     *
     * @var Closure
     */
    protected $formatPathUsing;

    /**
     * Crée une nouvelle instance du générateur d'URL.
     *
     * @param RouteCollectionInterface $routes    Collection des routes.
     * @param Request                  $request   Instance de la requête.
     * @param string|null              $assetRoot URL racine des assets.
     *
     * @return void
     */
    public function __construct(protected RouteCollectionInterface $routes, protected Request $request, protected ?string $assetRoot = null)
    {
        $this->setRequest($request);
    }

    /**
     * Obtient l'URL complète pour la requête actuelle.
     */
    public function full(): string
    {
        return $this->request->fullUrl();
    }

    /**
     * Obtient l'URL actuelle pour la requête.
     */
    public function current(): string
    {
        return $this->to($this->request->getUri()->getPath());
    }

    /**
     * Obtient l'URL de la requête précédente.
     *
     * @param mixed $fallback URL de secours.
     */
    public function previous($fallback = false): string
    {
        $referrer = $this->request->getHeaderLine('Referer');

        $url = $referrer !== '' ? $this->to($referrer) : $this->getPreviousUrlFromSession();

        if ($url !== null && $url !== '') {
            return $url;
        }
        if ($fallback) {
            return $this->to($fallback);
        }

        return $this->to('/');
    }

    /**
     * Obtient l'URL précédente depuis la session si possible.
     */
    protected function getPreviousUrlFromSession(): ?string
    {
        return $this->getSession()?->previousUrl();
    }

    /**
     * Génère une URL absolue vers le chemin donné.
     */
    public function to(string $path, mixed $extra = [], ?bool $secure = null): string
    {
        // D'abord, nous vérifions si l'URL est déjà une URL valide. Si c'est le cas,
        // nous n'essaierons pas d'en générer une nouvelle mais retournerons simplement
        // l'URL telle quelle, ce qui est pratique car les développeurs n'ont pas toujours
        // à vérifier si elle est valide.
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $tail = implode(
            '/',
            array_map(
                'rawurlencode',
                $this->formatParameters($extra),
            ),
        );

        // Une fois que nous avons le schéma, nous compilons la "queue" en rassemblant les valeurs
        // en une seule chaîne délimitée par des barres obliques. Cela rend simplement pratique
        // le passage du tableau de paramètres à cette URL sous forme de liste de segments.
        $root = $this->formatRoot($this->formatScheme($secure));

        [$path, $query] = $this->extractQueryString($path);

        return $this->format(
            $root,
            '/' . trim($path . '/' . $tail, '/'),
        ) . $query;
    }

    /**
     * Génère une URL sécurisée absolue vers le chemin donné.
     */
    public function secure(string $path, array $parameters = []): string
    {
        return $this->to($path, $parameters, true);
    }

    /**
     * Génère l'URL vers un asset de l'application.
     */
    public function asset(string $path, ?bool $secure = null): string
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        // Une fois que nous obtenons l'URL racine, nous vérifions si elle contient un fichier index.php
        // dans les chemins. Si c'est le cas, nous le supprimerons car il n'est pas nécessaire
        // pour les chemins d'assets, mais uniquement pour les routes vers les points de terminaison de l'application.
        $root = $this->assetRoot ?: $this->formatRoot($this->formatScheme($secure));

        return $this->removeIndex($root) . '/' . trim($path, '/');
    }

    /**
     * Génère l'URL vers un asset sécurisé.
     */
    public function secureAsset(string $path): string
    {
        return $this->asset($path, true);
    }

    /**
     * Génère l'URL vers un asset depuis une racine de domaine personnalisée telle qu'un CDN, etc.
     */
    public function assetFrom(string $root, string $path, ?bool $secure = null): string
    {
        // Une fois que nous obtenons l'URL racine, nous vérifions si elle contient un fichier index.php
        // dans les chemins. Si c'est le cas, nous le supprimerons car il n'est pas nécessaire
        // pour les chemins d'assets, mais uniquement pour les routes vers les points de terminaison de l'application.
        $root = $this->formatRoot($this->formatScheme($secure), $root);

        return $this->removeIndex($root) . '/' . trim($path, '/');
    }

    /**
     * Supprime le fichier index.php d'un chemin.
     */
    protected function removeIndex(string $root): string
    {
        $i = 'index.php';

        return Text::contains($root, /** @scrutinizer ignore-type */ $i) ? str_replace('/' . $i, '', $root) : $root;
    }

    /**
     * Obtient le schéma par défaut pour une URL brute.
     */
    public function formatScheme(?bool $secure = null): string
    {
        if (null !== $secure) {
            return $secure ? 'https://' : 'http://';
        }

        if (null === $this->cachedScheme) {
            $this->cachedScheme = $this->forceScheme ?: $this->request->getScheme() . '://';
        }

        return $this->cachedScheme;
    }

    /**
     * Obtient l'URL vers une route nommée.
     */
    public function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        if (false === $route = $this->routes->reverseRoute($name, ...$parameters)) {
            throw HttpException::invalidRedirectRoute($name);
        }

        return $absolute ? site_url($route) : $route;
    }

    /**
     * Obtient l'URL vers une action de contrôleur.
     *
     * @return false|string
     */
    public function action(array|string $action, array $parameters = [], bool $absolute = true)
    {
        if (is_array($action)) {
            $action = implode('::', $action);
        }

        $route = $this->routes->reverseRoute($action, ...$parameters);

        if (! $route) {
            throw RouterException::actionNotDefined($action);
        }

        return $absolute ? site_url($route) : $route;
    }

    /**
     * Formate le tableau des paramètres d'URL.
     */
    public function formatParameters(mixed $parameters): array
    {
        return Arr::wrap($parameters);
    }

    /**
     * Extrait la chaîne de requête du chemin donné.
     */
    protected function extractQueryString(string $path): array
    {
        if (($queryPosition = strpos($path, '?')) !== false) {
            return [
                substr($path, 0, $queryPosition),
                substr($path, $queryPosition),
            ];
        }

        return [$path, ''];
    }

    /**
     * Obtient l'URL de base pour la requête.
     */
    public function formatRoot(string $scheme, ?string $root = null): string
    {
        if (null === $root) {
            if (null === $this->cachedRoot) {
                $this->cachedRoot = $this->forcedRoot ?: $this->request->root();
            }

            $root = $this->cachedRoot;
        }

        $start = Text::startsWith($root, /** @scrutinizer ignore-type */ 'http://') ? 'http://' : 'https://';

        return preg_replace('~' . $start . '~', $scheme, $root, 1);
    }

    /**
     * Formate les segments d'URL donnés en une seule URL.
     */
    public function format(string $root, string $path, mixed $route = null): string
    {
        $path = '/' . trim($path, '/');

        if ($this->formatHostUsing) {
            $root = ($this->formatHostUsing)($root, $route);
        }

        if ($this->formatPathUsing) {
            $path = ($this->formatPathUsing)($path, $route);
        }

        return trim($root . $path, '/');
    }

    /**
     * Détermine si le chemin donné est une URL valide.
     */
    public function isValidUrl(string $path): bool
    {
        if (! preg_match('~^(#|//|https?://|(mailto|tel|sms):)~', $path)) {
            return filter_var($path, FILTER_VALIDATE_URL) !== false;
        }

        return true;
    }

    /**
     * Force le schéma pour les URLs.
     */
    public function forceScheme(?string $scheme): void
    {
        $this->cachedScheme = null;

        $this->forceScheme = $scheme ? $scheme . '://' : null;
    }

    /**
     * Définit l'URL racine forcée.
     */
    public function forceRootUrl(?string $root): void
    {
        $this->forcedRoot = $root ? rtrim($root, '/') : null;

        $this->cachedRoot = null;
    }

    /**
     * Définit un callback à utiliser pour formater l'hôte des URLs générées.
     *
     * @return $this
     */
    public function formatHostUsing(Closure $callback)
    {
        $this->formatHostUsing = $callback;

        return $this;
    }

    /**
     * Définit un callback à utiliser pour formater le chemin des URLs générées.
     *
     * @return $this
     */
    public function formatPathUsing(Closure $callback)
    {
        $this->formatPathUsing = $callback;

        return $this;
    }

    /**
     * Obtient le formateur de chemin utilisé par le générateur d'URL.
     *
     * @return Closure
     */
    public function pathFormatter()
    {
        return $this->formatPathUsing ?: static fn ($path) => $path;
    }

    /**
     * Obtient l'instance de la requête.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Définit l'instance de la requête actuelle.
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        $this->cachedRoot   = null;
        $this->cachedScheme = null;

        return $this;
    }

    /**
     * Définit la collection des routes.
     */
    public function setRoutes(RouteCollectionInterface $routes): self
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Obtient l'implémentation de la session depuis le résolveur.
     */
    protected function getSession(): ?Store
    {
        if ($this->sessionResolver) {
            return ($this->sessionResolver)();
        }

        return session();
    }

    /**
     * Définit le résolveur de session pour le générateur.
     *
     * @return $this
     */
    public function setSessionResolver(callable $sessionResolver)
    {
        $this->sessionResolver = $sessionResolver;

        return $this;
    }

    /**
     * Définit le résolveur de clé de chiffrement.
     *
     * @return $this
     */
    public function setKeyResolver(callable $keyResolver)
    {
        $this->keyResolver = $keyResolver;

        return $this;
    }

    /**
     * Définit l'espace de noms racine des contrôleurs.
     *
     * @return $this
     */
    public function setRootControllerNamespace(string $rootNamespace)
    {
        $this->rootNamespace = $rootNamespace;

        return $this;
    }
}
