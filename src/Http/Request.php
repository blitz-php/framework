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

use ArrayAccess;
use BlitzPHP\Config\Config;
use BlitzPHP\Contracts\Session\SessionInterface;
use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Utilities\Iterable\Arr;
use BlitzPHP\Utilities\String\Text;
use BlitzPHP\Validation\Validator;
use Dimtrovich\Validation\Exceptions\ValidationException;

class Request extends ServerRequest implements Arrayable, ArrayAccess
{
    use Concerns\InteractsWithContentTypes,
        Concerns\InteractsWithInput,
        Concerns\InteractsWithFlashData;

    /**
     * Validation des donnees de la requete
     *
     * @return array|Redirection
     */
    public function validate(array $rules, array $messages = [])
    {
        try {
            return Validator::validate($this->all(), $rules, $messages);  
        }
        catch (ValidationException $e) {
            return redirect()->back()->withInput();
        }
    }


    /**
     * Obtenez la méthode de requête.
     */
    public function method(): string
    {
        return $this->getMethod();
    }

    /**
     * Obtenez l'URL racine de l'application.
     */
    public function root(): string
    {
        return rtrim(site_url(), '/');
    }

    /**
     * Renvoie l'URL racine à partir de laquelle cette requête est exécutée.
     *
     * L'URL de base ne se termine jamais par un /.
     *
     * Ceci est similaire à getBasePath(), sauf qu'il inclut également le
     * nom de fichier du script (par exemple index.php) s'il existe.
     *
     * @return string L'URL brute (c'est-à-dire non décodée en url)
     */
    public function getBaseUrl(): string
    {
        return trim(Config::get('app.base_url'), '/');
    }

    /**
     * Obtenez l'URL (pas de chaîne de requête) pour la demande.
     */
    public function url(): string
    {
        return rtrim(preg_replace('/\?.*/', '', (string) $this->getUri()), '/');
    }

    /**
     * Obtenez l'URL complète de la demande.
     */
    public function fullUrl(): string
    {
        if (! empty($query = $this->getEnv('QUERY_STRING'))) {
            return $this->url().'?'.$query;
        }

        return $this->url();
    }

    /**
     * Obtenez l'URL complète de la demande avec les paramètres de chaîne de requête ajoutés.
     */
    public function fullUrlWithQuery(array $query): string
    {
        $question = '?';

        return count($this->query()) > 0
            ? $this->url().$question.Arr::query(array_merge($this->query(), $query))
            : $this->fullUrl().$question.Arr::query($query);
    }

    /**
     * Obtenez l'URL complète de la requête sans les paramètres de chaîne de requête donnés.
     */
    public function fullUrlWithoutQuery(array|string $keys): string
    {
        $query = Arr::except($this->query(), $keys);

        return count($query) > 0
            ? $this->url().'?'.Arr::query($query)
            : $this->url();
    }

    /**
     * Obtenez les informations de chemin actuelles pour la demande.
     */
    public function path(): string
    {
        return $this->getPath();
    }

    /**
     * Obtenez les informations de chemin décodées actuelles pour la demande.
     */
    public function decodedPath(): string
    {
        return rawurldecode($this->path());
    }

    /**
     * Obtenir un segment de l'URI (index basé sur 1).
     */
    public function segment(int $index, ?string $default = null): ?string
    {
        return Arr::get($this->segments(), $index - 1, $default);
    }

    /**
     * Obtenez tous les segments pour le chemin de la demande.
     */
    public function segments(): array
    {
        $segments = explode('/', $this->decodedPath());

        return array_values(array_filter($segments, fn ($value) => $value !== ''));
    }

    /**
     * Déterminez si le nom de l'itinéraire correspond à un modèle donné.
     *
     * @param  mixed  ...$patterns
     */
    public function routeIs(...$patterns): bool
    {
        return false;
        // return $this->route() && $this->route()->named(...$patterns);
    }

    /**
     * Déterminez si l'URL de requête et la chaîne de requête actuelles correspondent à un modèle.
     *
     * @param  mixed  ...$patterns
     */
    public function fullUrlIs(...$patterns): bool
    {
        $url = $this->fullUrl();

        return collect($patterns)->contains(fn ($pattern) => Text::is($pattern, $url));
    }

    /**
     * Obtenez l'hôte HTTP demandé.
     */
    public function httpHost(): ?string
    {
        return $this->host();
    }

    /**
     * Déterminez si la demande est le résultat d'un appel AJAX.
     */
    public function ajax(): bool
    {
        return $this->isAjax();
    }

    /**
     * Déterminez si la demande est le résultat d'un appel PJAX.
     */
    public function pjax(): bool
    {
        return $this->header('X-PJAX') === true;
    }

    /**
     * Déterminez si la demande est le résultat d'un appel de prélecture.
     */
    public function prefetch(): bool
    {
        return strcasecmp($this->server('HTTP_X_MOZ', ''), 'prefetch') === 0 ||
               strcasecmp($this->header('Purpose', ''), 'prefetch') === 0;
    }

    /**
     * Déterminez si la demande est via HTTPS.
     */
    public function secure(): bool
    {
        return $this->is('ssl');
    }

    /**
     * Obtenez l'adresse IP du client.
     */
    public function ip(): ?string
    {
        return $this->clientIp();
    }

    /**
     * Obtenez l'agent utilisateur client.
     */
    public function userAgent(): ?string
    {
        return $this->header('User-Agent');
    }

    /**
     * Fusionne la nouvelle entrée dans le tableau d'entrée de la requête actuelle.
     */
    public function merge(array $input): self
    {
        $this->data = array_merge($this->data, $input);

        return $this;
    }

    /**
     * Fusionne la nouvelle entrée dans l'entrée de la requête, mais uniquement lorsque cette clé est absente de la requête.
     */
    public function mergeIfMissing(array $input): self
    {
        return $this->merge(collect($input)->filter(fn($value, $key) => $this->missing($key))->toArray());
    }

    /**
     * Remplacez l'entrée de la requête en cours.
     */
    public function replace(array $input): self
    {
        $this->data = $input;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSession(): bool
    {
        return ! is_null($this->session);
    }

    /**
     * Définissez l'instance de session sur la demande.
     */
    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    /**
     * Obtenez toutes les entrées et tous les fichiers de la requête.
     */
    public function toArray(): array
    {
        return $this->all();
    }

    /**
     * {@inheritDoc}
     *
     * @param  string  $offset
     */
    public function offsetExists($offset): bool
    {
        return Arr::has($this->all(), $offset);
    }

    /**
     * {@inheritDoc}
     *
     * @param  string  $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->__get($offset);
    }

    /**
     * {@inheritDoc}
     *
     * @param  string  $offset
     */
    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param  string  $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Vérifiez si un élément d'entrée est défini sur la demande.
     */
    public function __isset(string $key): bool
    {
        return ! is_null($this->__get($key));
    }

    /**
     * Obtenez un élément d'entrée à partir de la requête.
     */
    public function __get(string $key): mixed
    {
        return Arr::get($this->all(), $key, null);
    }
}
