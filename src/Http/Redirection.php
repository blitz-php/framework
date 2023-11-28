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
use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Session\Store;
use BlitzPHP\Validation\ErrorBag;
use GuzzleHttp\Psr7\UploadedFile;

/**
 * Gérer une réponse de redirection
 *
 * @credit CodeIgniter 4 <a href="https://codeigniter.com">CodeIgniter\HTTP\RedirectResponse</a>
 */
class Redirection extends Response
{
    /**
     * The session store instance.
     */
    protected Store $session;

    protected Request $request;

    /**
     * @param UrlGenerator $generator The URL generator instance.
     */
    public function __construct(protected UrlGenerator $generator, array $options = [])
    {
        parent::__construct($options);
        $this->request = $generator->getRequest();
        $this->session = $this->request->session();
    }

    /**
     * Creer une redirection vers la route nommee "home" ou vers la page d'accueil.
     */
    public function home(int $status = StatusCode::FOUND): static
    {
        try {
            return $this->to($this->generator->route('home'), $status);
        } catch (HttpException) {
            return $this->to('/', $status);
        }
    }

    /**
     * Définit l'URI vers lequel rediriger et, éventuellement, le code d'état HTTP à utiliser.
     * Si aucun code n'est fourni, il sera automatiquement déterminé.
     *
     * @param string   $uri  L'URI vers laquelle rediriger
     * @param int|null $code Code d'état HTTP
     */
    public function to(string $uri, ?int $code = null, array $headers = [], ?bool $secure = null, string $method = 'auto'): static
    {
        $uri = $this->generator->to($uri, [], $secure);

        // Si cela semble être une URL relative, alors convertissez-la en URL complète
        // pour une meilleure sécurité.
        if (! str_starts_with($uri, 'http')) {
            $uri = site_url($uri);
        }

        return $this->createRedirect($uri, $code, $headers, $method);
    }

    /**
     * Create a new redirect response to an external URL (no validation).
     */
    public function away(string $path, int $status = StatusCode::FOUND, array $headers = []): static
    {
        return $this->createRedirect($path, $status, $headers);
    }

    /**
     * Create a new redirect response to the given HTTPS path.
     */
    public function secure(string $path, int $status = StatusCode::FOUND, array $headers = []): static
    {
        return $this->to($path, $status, $headers, true);
    }

    /**
     * Sets the URI to redirect to but as a reverse-routed or named route
     * instead of a raw URI.
     */
    public function route(string $route, array $params = [], int $code = StatusCode::FOUND, array $headers = []): static
    {
        return $this->to($this->generator->route($route, $params, true), $code, $headers);
    }

    /**
     * Sets the URI to redirect to but as a controller action.
     */
    public function action(array|string $action, array $params = [], int $code = StatusCode::FOUND, array $headers = []): static
    {
        return $this->to($this->generator->action($action, $params, true), $code, $headers);
    }

    /**
     * Helper function to return to previous page.
     *
     * Example:
     *  return redirect()->back();
     *
     * @param mixed $status
     * @param mixed $fallback
     */
    public function back($status = StatusCode::FOUND, array $headers = [], $fallback = false): static
    {
        return $this->createRedirect($this->generator->previous($fallback), $status, $headers);
    }

    /**
     * Create a new redirect response to the current URI.
     */
    public function refresh(int $status = StatusCode::FOUND, array $headers = []): static
    {
        return $this->to($this->generator->getRequest()->path(), $status, $headers);
    }

    /**
     * Create a new redirect response, while putting the current URL in the session.
     */
    public function guest(string $path, int $status = StatusCode::FOUND, array $headers = [], ?bool $secure = null): static
    {
        $request = $this->generator->getRequest();

        $intended = $request->method() === 'GET' && ! $request->expectsJson()
                        ? $this->generator->full()
                        : $this->generator->previous();

        if ($intended) {
            $this->setIntendedUrl($intended);
        }

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Create a new redirect response.
     */
    protected function createRedirect(string $uri, ?int $code = null, array $headers = [], string $method = 'auto'): static
    {
        $instance = $this->redirect($uri, $method, $code);

        foreach ($headers as $key => $value) {
            $instance = $instance->withHeader($key, $value);
        }

        return $instance;
    }

    /**
     * Create a new redirect response to the previously intended location.
     */
    public function intended(string $default = '/', int $status = StatusCode::FOUND, array $headers = [], ?bool $secure = null): static
    {
        $path = $this->session->pull('url.intended', $default);

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Set the intended url.
     */
    public function setIntendedUrl(string $url): void
    {
        $this->session->put('url.intended', $url);
    }

    /**
     * Ajoute des erreurs à la session en tant que Flashdata.
     */
    public function withErrors(array|ErrorBag|string $errors, string $key = 'default'): static
    {
        if ($errors instanceof ErrorBag) {
            $errors = $errors->toArray();
        } elseif (is_string($errors)) {
            $errors = [$key => $errors];
        }

        if (! empty($errors)) {
            Services::viewer()->share('errors', new ErrorBag($this->session->flashErrors($errors, $key)));
        }

        return $this;
    }

    /**
     * Spécifie que les tableaux $_GET et $_POST actuels doivent être
     * emballé avec la réponse.
     *
     * Il sera alors disponible via la fonction d'assistance 'old()'.
     */
    public function withInput(): static
    {
        return $this->with('_blitz_old_input', [
            'get'  => $_GET ?: [],
            'post' => $_POST ?: [],
        ]);
    }

    /**
     * Ajoute une clé et un message à la session en tant que Flashdata.
     */
    public function with(array|string $key, mixed $value = null): static
    {
        $key = is_array($key) ? $key : [$key => $value];

        foreach ($key as $k => $v) {
            $this->session->flash($k, $v);
        }

        return $this;
    }

    /**
     * Copie tous les cookies de l’instance de réponse globale dans cette RedirectResponse.
     * Utile lorsque vous venez de définir un cookie mais que vous devez vous assurer qu'il est réellement envoyé avec la réponse au lieu d'être perdu.
     */
    public function withCookies(): static
    {
        return $this->withCookieCollection(Services::response()->getCookieCollection());
    }

    /**
     * Copie tous les en-têtes de l'instance de réponse globale
     * dans cette Redirection. Utile lorsque vous venez de
     * définir un en-tête pour s'assurer qu'il est bien envoyé
     * avec la réponse de redirection..
     */
    public function withHeaders(): static
    {
        $new = clone $this;

        foreach (Services::response()->getHeaders() as $name => $header) {
            $new = $new->withHeader($name, $header);
        }

        return $new;
    }

    /**
     * Supprimez tous les fichiers téléchargés du tableau d’entrée donné.
     */
    protected function removeFilesFromInput(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = $this->removeFilesFromInput($value);
            }

            if ($value instanceof UploadedFile) {
                unset($input[$key]);
            }
        }

        return $input;
    }
}
