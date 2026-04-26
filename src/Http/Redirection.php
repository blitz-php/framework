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

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Session\Store;
use BlitzPHP\Validation\ErrorBag;
use BlitzPHP\Validation\Validation;
use GuzzleHttp\Psr7\UploadedFile;

/**
 * Gestionnaire de réponse de redirection
 *
 * Cette classe étend la classe Response pour fournir des fonctionnalités spécifiques
 * aux redirections HTTP, incluant la gestion des sessions, des messages flash,
 * et des données d'entrée.
 *
 * @credit CodeIgniter 4 <a href="https://codeigniter.com">CodeIgniter\HTTP\RedirectResponse</a>
 */
class Redirection extends Response
{
    /**
     * Instance du stockage de session
     */
    protected Store $session;

    /**
     * Instance de la requête
     */
    protected Request $request;

    /**
     * Constructeur de la classe Redirection
     *
     * @param UrlGenerator $generator Instance du générateur d'URL
     * @param array        $options   Options de configuration pour la réponse
     */
    public function __construct(protected UrlGenerator $generator, array $options = [])
    {
        parent::__construct($options);
        $this->request = $generator->getRequest();
        $this->session = $this->request->session();
    }

    /**
     * Crée une redirection vers la route nommée "home" ou vers la page d'accueil
     *
     * @param int $status Code d'état HTTP pour la redirection
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
     * Définit l'URI vers lequel rediriger
     *
     * @param string    $uri     URI vers laquelle rediriger
     * @param int|null  $code    Code d'état HTTP (si null, déterminé automatiquement)
     * @param array     $headers En-têtes HTTP supplémentaires
     * @param bool|null $secure  Si true, force l'utilisation de HTTPS
     * @param string    $method  Méthode de redirection ('auto', 'refresh', etc.)
     */
    public function to(string $uri, ?int $code = null, array $headers = [], ?bool $secure = null, string $method = 'auto'): static
    {
        $uri = $this->generator->to($uri, [], $secure);

        // Si cela semble être une URL relative, on la convertit en URL complète
        // pour une meilleure sécurité
        if (! str_starts_with($uri, 'http')) {
            $uri = site_url($uri);
        }

        return $this->createRedirect($uri, $code, $headers, $method);
    }

    /**
     * Crée une nouvelle réponse de redirection vers une URL externe (sans validation)
     *
     * @param string $path    Chemin ou URL vers lequel rediriger
     * @param int    $status  Code d'état HTTP
     * @param array  $headers En-têtes HTTP supplémentaires
     */
    public function away(string $path, int $status = StatusCode::FOUND, array $headers = []): static
    {
        return $this->createRedirect($path, $status, $headers);
    }

    /**
     * Crée une nouvelle réponse de redirection vers un chemin HTTPS
     *
     * @param string $path    Chemin vers lequel rediriger
     * @param int    $status  Code d'état HTTP
     * @param array  $headers En-têtes HTTP supplémentaires
     */
    public function secure(string $path, int $status = StatusCode::FOUND, array $headers = []): static
    {
        return $this->to($path, $status, $headers, true);
    }

    /**
     * Définit l'URI de redirection en utilisant une route nommée
     *
     * @param string $route   Nom de la route
     * @param array  $params  Paramètres de la route
     * @param int    $code    Code d'état HTTP
     * @param array  $headers En-têtes HTTP supplémentaires
     */
    public function route(string $route, array $params = [], int $code = StatusCode::FOUND, array $headers = []): static
    {
        return $this->to($this->generator->route($route, $params, true), $code, $headers);
    }

    /**
     * Définit l'URI de redirection en utilisant une action de contrôleur
     *
     * @param array|string $action  Action du contrôleur (format: 'Controller::method' ou ['Controller', 'method'])
     * @param array        $params  Paramètres pour l'action
     * @param int          $code    Code d'état HTTP
     * @param array        $headers En-têtes HTTP supplémentaires
     */
    public function action(array|string $action, array $params = [], int $code = StatusCode::FOUND, array $headers = []): static
    {
        return $this->to($this->generator->action($action, $params, true), $code, $headers);
    }

    /**
     * Fonction helper pour rediriger vers la page précédente
     *
     * Exemple:
     *  return redirect()->back();
     *
     * @param mixed $status   Code d'état HTTP
     * @param array $headers  En-têtes HTTP supplémentaires
     * @param mixed $fallback URL de secours si aucune page précédente n'est disponible
     */
    public function back($status = StatusCode::FOUND, array $headers = [], $fallback = false): static
    {
        return $this->createRedirect($this->generator->previous($fallback), $status, $headers);
    }

    /**
     * Crée une nouvelle réponse de redirection vers l'URI courante
     *
     * @param int   $status  Code d'état HTTP
     * @param array $headers En-têtes HTTP supplémentaires
     */
    public function refresh(int $status = StatusCode::FOUND, array $headers = []): static
    {
        return $this->to($this->generator->getRequest()->path(), $status, $headers);
    }

    /**
     * Crée une nouvelle réponse de redirection, tout en stockant l'URL courante dans la session
     *
     * @param string    $path    Chemin vers lequel rediriger
     * @param int       $status  Code d'état HTTP
     * @param array     $headers En-têtes HTTP supplémentaires
     * @param bool|null $secure  Si true, force l'utilisation de HTTPS
     */
    public function guest(string $path, int $status = StatusCode::FOUND, array $headers = [], ?bool $secure = null): static
    {
        $request = $this->generator->getRequest();

        $intended = $request->method() === 'GET' && ! $request->expectsJson()
                        ? $this->generator->full()
                        : $this->generator->previous();

        if ($intended !== '') {
            $this->setIntendedUrl($intended);
        }

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Crée une réponse de redirection
     *
     * @param string   $uri     URI vers laquelle rediriger
     * @param int|null $code    Code d'état HTTP
     * @param array    $headers En-têtes HTTP supplémentaires
     * @param string   $method  Méthode de redirection
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
     * Crée une nouvelle réponse de redirection vers l'emplacement précédemment intenté
     *
     * @param string    $default URL par défaut si aucune URL intentée n'est stockée
     * @param int       $status  Code d'état HTTP
     * @param array     $headers En-têtes HTTP supplémentaires
     * @param bool|null $secure  Si true, force l'utilisation de HTTPS
     */
    public function intended(string $default = '/', int $status = StatusCode::FOUND, array $headers = [], ?bool $secure = null): static
    {
        $path = $this->session->pull('url.intended', $default);

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Définit l'URL intentée dans la session
     *
     * @param string $url URL à stocker comme intentée
     */
    public function setIntendedUrl(string $url): void
    {
        $this->session->put('url.intended', $url);
    }

    /**
     * Ajoute des erreurs à la session en tant que données flash
     *
     * @param array|ErrorBag|string|Validation $errors Erreurs à stocker
     * @param string                           $key    Clé pour stocker les erreurs dans la session
     */
    public function withErrors(array|ErrorBag|string|Validation $errors, string $key = 'default'): static
    {
        if ($errors instanceof Validation) {
            $errors = $errors->errors();
        }
        if ($errors instanceof ErrorBag) {
            $errors = $errors->toArray();
        } elseif (is_string($errors)) {
            $errors = [$key => $errors];
        }

        if ($errors !== []) {
            $this->session->flashErrors($errors, $key);
        }

        return $this;
    }

    /**
     * Spécifie que les données $_GET et $_POST actuelles doivent être
     * conservées avec la réponse pour être disponibles via la fonction helper 'old()'
     */
    public function withInput(array $input = []): static
    {
        if ($input === []) {
            $input = $this->request->input();
        }
        if ($input === []) {
            $input = $_POST + $_GET;
        }

        $this->session->flashInput($this->removeFilesFromInput($input));

        return $this;
    }

    /**
     * Ajoute une clé et une valeur à la session en tant que données flash
     *
     * @param array|string $key   Clé ou tableau associatif clé/valeur
     * @param mixed        $value Valeur si $key est une chaîne
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
     * Copie tous les cookies de l'instance de réponse globale dans cette RedirectResponse
     *
     * Utile lorsque vous venez de définir un cookie mais que vous devez vous assurer
     * qu'il est réellement envoyé avec la réponse au lieu d'être perdu
     */
    public function withCookies(): static
    {
        return $this->withCookieCollection(service('response')->getCookieCollection());
    }

    /**
     * Supprime tous les fichiers téléchargés du tableau d'entrée donné
     *
     * @param array $input Tableau d'entrée à nettoyer
     *
     * @return array Tableau d'entrée sans les fichiers téléchargés
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
