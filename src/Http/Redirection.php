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
use BlitzPHP\Session\Store;
use Rakit\Validation\ErrorBag;

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

    /**
     * @param UrlGenerator $generator The URL generator instance.
     */
    public function __construct(protected UrlGenerator $generator, array $options = [])
    {
        parent::__construct($options);
        $this->session = $generator->getRequest()->session();
    }

    /**
     * Create a new redirect response to the "home" route.
     */
    public function home(int $status = StatusCode::FOUND): self
    {
        return $this->to($this->generator->route('home'), $status);
    }

    /**
     * Définit l'URI vers lequel rediriger et, éventuellement, le code d'état HTTP à utiliser.
     * Si aucun code n'est fourni, il sera automatiquement déterminé.
     *
     * @param string   $uri  L'URI vers laquelle rediriger
     * @param int|null $code Code d'état HTTP
     */
    public function to(string $uri, ?int $code = null, array $headers = [], ?bool $secure = null, string $method = 'auto'): self
    {
        $uri = $this->generator->to($uri, [], $secure);
        
        // Si cela semble être une URL relative, alors convertissez-la en URL complète
        // pour une meilleure sécurité.
        if (strpos($uri, 'http') !== 0) {
            $uri = site_url($uri);
        }

        return $this->createRedirect($uri, $code, $headers, $method);
    }

    /**
     * Create a new redirect response to an external URL (no validation).
     */
    public function away(string $path, int $status = StatusCode::FOUND, array $headers = []): self
    {
        return $this->createRedirect($path, $status, $headers);
    }

    /**
     * Create a new redirect response to the given HTTPS path.
     */
    public function secure(string $path, int $status = StatusCode::FOUND, array $headers = []): self
    {
        return $this->to($path, $status, $headers, true);
    }

    /**
     * Sets the URI to redirect to but as a reverse-routed or named route
     * instead of a raw URI.
     */
    public function route(string $route, array $params = [], int $code = StatusCode::FOUND, array $headers = []): self
    {
        return $this->to($this->generator->route($route, $params, true), $code, $headers);
    }

    /**
     * Helper function to return to previous page.
     *
     * Example:
     *  return redirect()->back();
     */
    public function back($status = StatusCode::FOUND, array $headers = [], $fallback = false): self
    {
        return $this->createRedirect($this->generator->previous($fallback), $status, $headers);
    }

    /**
     * Create a new redirect response to the current URI.
     */
    public function refresh(int $status = StatusCode::FOUND, array $headers = []): self
    {
        return $this->to($this->generator->getRequest()->path(), $status, $headers);
    }

    /**
     * Create a new redirect response, while putting the current URL in the session.
     */
    public function guest(string $path, int $status = StatusCode::FOUND, array $headers = [], ?bool $secure = null): self
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
    protected function createRedirect(string $uri, ?int $code = null, array $headers = [], string $method = 'auto'): self
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
    public function intended(string $default = '/', int $status = StatusCode::FOUND, array $headers = [], ?bool $secure = null): self
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
    public function withErrors(array|ErrorBag|string $errors, string $key = 'default'): self
    {
        if ($errors instanceof ErrorBag) {
            $errors = $errors->all();
        } elseif (is_string($errors)) {
            $errors = [$errors];
        }

        if (! empty($errors)) {
            $_errors = $this->session->getFlashdata('errors') ?? [];
            $this->session->setFlashdata(
                'errors',
                array_merge($_errors, [$key => $errors])
            );
        }

        return $this;
    }

    /**
     * Spécifie que les tableaux $_GET et $_POST actuels doivent être
     * emballé avec la réponse.
     *
     * Il sera alors disponible via la fonction d'assistance 'old()'.
     */
    public function withInput(): self
    {
        return $this->with('_blitz_old_input', [
            'get'  => $_GET ?? [],
            'post' => $_POST ?? [],
        ]);
    }

    /**
     * Ajoute une clé et un message à la session en tant que Flashdata.
     *
     * @param array|string $message
     */
    public function with(string $key, $message): self
    {
        $this->session->setFlashdata($key, $message);

        return $this;
    }

    /**
     * Copie tous les en-têtes de l'instance de réponse globale
     * dans cette Redirection. Utile lorsque vous venez de
     * définir un en-tête pour s'assurer qu'il est bien envoyé
     * avec la réponse de redirection..
     */
    public function withHeaders(): self
    {
        $new = clone $this;

        foreach (Services::response()->getHeaders() as $name => $header) {
            $new = $new->withHeader($name, $header);
        }

        return $new;
    }
}
