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

use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Session\Store;
use BlitzPHP\Traits\Macroable;
use BlitzPHP\Utilities\Iterable\Arr;
use BlitzPHP\Utilities\String\Text;
use Closure;

/**
 * @credit <a href="http://laravel.com">Laravel - \Illuminate\Routing\UrlGenerator</a>
 */
class UrlGenerator
{
    use Macroable;

    /**
     * The forced URL root.
     */
    protected string $forcedRoot = '';

    /**
     * The forced scheme for URLs.
     */
    protected string $forceScheme = '';

    /**
     * A cached copy of the URL root for the current request.
     */
    protected ?string $cachedRoot = null;

    /**
     * A cached copy of the URL scheme for the current request.
     */
    protected ?string $cachedScheme = null;

    /**
     * The root namespace being applied to controller actions.
     */
    protected string $rootNamespace = '';

    /**
     * The session resolver callable.
     *
     * @var callable
     */
    protected $sessionResolver;

    /**
     * The encryption key resolver callable.
     *
     * @var callable
     */
    protected $keyResolver;

    /**
     * The callback to use to format hosts.
     *
     * @var Closure
     */
    protected $formatHostUsing;

    /**
     * The callback to use to format paths.
     *
     * @var Closure
     */
    protected $formatPathUsing;

    /**
     * Create a new URL Generator instance.
     *
     * @param RouteCollectionInterface $routes    The route collection.
     * @param Request                  $request   The request instance.
     * @param string|null              $assetRoot The asset root URL.
     *
     * @return void
     */
    public function __construct(protected RouteCollectionInterface $routes, protected Request $request, protected ?string $assetRoot = null)
    {
        $this->setRequest($request);
    }

    /**
     * Get the full URL for the current request.
     */
    public function full(): string
    {
        return $this->request->fullUrl();
    }

    /**
     * Get the current URL for the request.
     */
    public function current(): string
    {
        return $this->to($this->request->getUri()->getPath());
    }

    /**
     * Get the URL for the previous request.
     *
     * @param mixed $fallback
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
     * Get the previous URL from the session if possible.
     */
    protected function getPreviousUrlFromSession(): ?string
    {
        return $this->getSession()?->previousUrl();
    }

    /**
     * Generate an absolute URL to the given path.
     */
    public function to(string $path, mixed $extra = [], ?bool $secure = null): string
    {
        // First we will check if the URL is already a valid URL. If it is we will not
        // try to generate a new one but will simply return the URL as is, which is
        // convenient since developers do not always have to check if it's valid.
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $tail = implode(
            '/',
            array_map(
                'rawurlencode',
                $this->formatParameters($extra)
            )
        );

        // Once we have the scheme we will compile the "tail" by collapsing the values
        // into a single string delimited by slashes. This just makes it convenient
        // for passing the array of parameters to this URL as a list of segments.
        $root = $this->formatRoot($this->formatScheme($secure));

        [$path, $query] = $this->extractQueryString($path);

        return $this->format(
            $root,
            '/' . trim($path . '/' . $tail, '/')
        ) . $query;
    }

    /**
     * Generate a secure, absolute URL to the given path.
     */
    public function secure(string $path, array $parameters = []): string
    {
        return $this->to($path, $parameters, true);
    }

    /**
     * Generate the URL to an application asset.
     */
    public function asset(string $path, ?bool $secure = null): string
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
        $root = $this->assetRoot ?: $this->formatRoot($this->formatScheme($secure));

        return $this->removeIndex($root) . '/' . trim($path, '/');
    }

    /**
     * Generate the URL to a secure asset.
     */
    public function secureAsset(string $path): string
    {
        return $this->asset($path, true);
    }

    /**
     * Generate the URL to an asset from a custom root domain such as CDN, etc.
     */
    public function assetFrom(string $root, string $path, ?bool $secure = null): string
    {
        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
        $root = $this->formatRoot($this->formatScheme($secure), $root);

        return $this->removeIndex($root) . '/' . trim($path, '/');
    }

    /**
     * Remove the index.php file from a path.
     */
    protected function removeIndex(string $root): string
    {
        $i = 'index.php';

        return Text::contains($root, /** @scrutinizer ignore-type */ $i) ? str_replace('/' . $i, '', $root) : $root;
    }

    /**
     * Get the default scheme for a raw URL.
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
     * Get the URL to a named route.
     */
    public function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        if (false === $route = $this->routes->reverseRoute($name, ...$parameters)) {
            throw HttpException::invalidRedirectRoute($name);
        }

        return $absolute ? site_url($route) : $route;
    }

    /**
     * Get the URL to a controller action.
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
     * Format the array of URL parameters.
     */
    public function formatParameters(mixed $parameters): array
    {
        return Arr::wrap($parameters);
    }

    /**
     * Extract the query string from the given path.
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
     * Get the base URL for the request.
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
     * Format the given URL segments into a single URL.
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
     * Determine if the given path is a valid URL.
     */
    public function isValidUrl(string $path): bool
    {
        if (! preg_match('~^(#|//|https?://|(mailto|tel|sms):)~', $path)) {
            return filter_var($path, FILTER_VALIDATE_URL) !== false;
        }

        return true;
    }

    /**
     * Force the scheme for URLs.
     */
    public function forceScheme(?string $scheme): void
    {
        $this->cachedScheme = null;

        $this->forceScheme = $scheme ? $scheme . '://' : null;
    }

    /**
     * Set the forced root URL.
     */
    public function forceRootUrl(?string $root): void
    {
        $this->forcedRoot = $root ? rtrim($root, '/') : null;

        $this->cachedRoot = null;
    }

    /**
     * Set a callback to be used to format the host of generated URLs.
     *
     * @return $this
     */
    public function formatHostUsing(Closure $callback)
    {
        $this->formatHostUsing = $callback;

        return $this;
    }

    /**
     * Set a callback to be used to format the path of generated URLs.
     *
     * @return $this
     */
    public function formatPathUsing(Closure $callback)
    {
        $this->formatPathUsing = $callback;

        return $this;
    }

    /**
     * Get the path formatter being used by the URL generator.
     *
     * @return Closure
     */
    public function pathFormatter()
    {
        return $this->formatPathUsing ?: static fn ($path) => $path;
    }

    /**
     * Get the request instance.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Set the current request instance.
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        $this->cachedRoot   = null;
        $this->cachedScheme = null;

        return $this;
    }

    /**
     * Set the route collection.
     */
    public function setRoutes(RouteCollectionInterface $routes): self
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Get the session implementation from the resolver.
     */
    protected function getSession(): ?Store
    {
        if ($this->sessionResolver) {
            return ($this->sessionResolver)();
        }

        return Services::session();
    }

    /**
     * Set the session resolver for the generator.
     *
     * @return $this
     */
    public function setSessionResolver(callable $sessionResolver)
    {
        $this->sessionResolver = $sessionResolver;

        return $this;
    }

    /**
     * Set the encryption key resolver.
     *
     * @return $this
     */
    public function setKeyResolver(callable $keyResolver)
    {
        $this->keyResolver = $keyResolver;

        return $this;
    }

    /**
     * Set the root controller namespace.
     *
     * @param string $rootNamespace
     *
     * @return $this
     */
    public function setRootControllerNamespace($rootNamespace)
    {
        $this->rootNamespace = $rootNamespace;

        return $this;
    }
}
