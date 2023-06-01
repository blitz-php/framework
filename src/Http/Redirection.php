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

use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Loader\Services;
use Rakit\Validation\ErrorBag;

/**
 * Gérer une réponse de redirection
 *
 * @credit CodeIgniter 4 <a href="https://codeigniter.com">CodeIgniter\HTTP\RedirectResponse</a>
 */
class Redirection extends Response
{
    /**
     * Définit l'URI vers lequel rediriger et, éventuellement, le code d'état HTTP à utiliser.
     * Si aucun code n'est fourni, il sera automatiquement déterminé.
     *
     * @param string   $uri  L'URI vers laquelle rediriger
     * @param int|null $code Code d'état HTTP
     */
    public function to(string $uri, ?int $code = null, string $method = 'auto'): self
    {
        // Si cela semble être une URL relative, alors convertissez-la en URL complète
        // pour une meilleure sécurité.
        if (strpos($uri, 'http') !== 0) {
            $uri = site_url($uri);
        }

        return $this->redirect($uri, $method, $code);
    }

    /**
     * Sets the URI to redirect to but as a reverse-routed or named route
     * instead of a raw URI.
     *
     * @throws HTTPException
     */
    public function route(string $route, array $params = [], int $code = 302, string $method = 'auto'): self
    {
        $route = Services::routes()->reverseRoute($route, ...$params);

        if (! $route) {
            throw HttpException::invalidRedirectRoute($route);
        }

        return $this->redirect(site_url($route), $method, $code);
    }

    /**
     * Helper function to return to previous page.
     *
     * Example:
     *  return redirect()->back();
     */
    public function back(?int $code = null, string $method = 'auto'): self
    {
        Services::session();

        return $this->redirect(previous_url(), $method, $code);
    }

    /**
     * Ajoute des erreurs à la session en tant que Flashdata.
     */
    public function withErrors(array|ErrorBag|string $errors, string $key = 'default'): self
    {
        if ($errors instanceof ErrorBag) {
            $errors = $errors->all();
        } else if (is_string($errors)) {
            $errors = [$errors];
        }

        if (!empty($errors)) {
            $session = Services::session();
            $_errors = $session->getFlashdata('errors') ?? [];
            $session->setFlashdata(
                'errors', array_merge($_errors, [$key => $errors])
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
        Services::session()->setFlashdata($key, $message);

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
