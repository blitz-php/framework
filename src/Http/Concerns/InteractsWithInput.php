<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http\Concerns;

use BlitzPHP\Filesystem\Files\UploadedFile;
use BlitzPHP\Traits\Support\InteractsWithData;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\Iterable\Arr;
use BlitzPHP\Utilities\Support\Fluent;
use Kint\Kint;
use Psr\Http\Message\UploadedFileInterface;
use SplFileInfo;

/**
 * @credit <a href="http://laravel.com/">Laravel - Illuminate\Http\Concerns\InteractsWithInput</a>
 */
trait InteractsWithInput
{
    use InteractsWithData;

    /**
     * Tableau de données d'environnement.
     *
     * @var array<string, mixed>
     */
    protected array $_environment = [];

    /**
     * Récupérez une variable de serveur à partir de la requête.
     *
     * @return array|string|null
     */
    public function server(?string $key = null, array|string|null $default = null)
    {
        return Arr::get($this->_environment, $key, $default);
    }

    /**
     * Détermine si un en-tête est défini dans la requête.
     */
    public function hasHeader(string $key): bool
    {
        return null !== $this->header($key);
    }

    /**
     * Récupére un en-tête de la requête.
     *
     * @return array|string|null
     */
    public function header(?string $key = null, array|string|null $default = null)
    {
        if (null === $key) {
            return $this->getHeaders();
        }

        return empty($header = $this->getHeaderLine($key)) ? $default : $header;
    }

    /**
     * Obtenez le jeton du porteur à partir des en-têtes de requête.
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');

        $position = strripos($header, 'Bearer ');

        if ($position !== false) {
            $header = substr($header, $position + 7);

            return str_contains($header, ',') ? strstr($header, ',', true) : $header;
        }

        return null;
    }

    /**
     * Obtenez les clés pour toutes les entrées et tous les fichiers.
     */
    public function keys(): array
    {
        return array_keys($this->input());
    }

    /**
     * Obtenez toutes les entrées et tous les fichiers de la requête.
     *
     * @param array|mixed|null $keys
     */
    public function all($keys = null): array
    {
        $input = array_replace_recursive($this->input(), $this->allFiles());

        if (! $keys) {
            return $input;
        }

        $results = [];

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            Arr::set($results, $key, Arr::get($input, $key));
        }

        return $results;
    }

    /**
     * Récupère un élément d'entrée de la requête.
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        return Helpers::dataGet(
            $this->data + $this->query,
            $key,
            $default,
        );
    }

    /**
     * Récupère les données saisies dans la requête sous forme d'instance d'objet Fluent.
     */
    public function fluent(array|string|null $key = null, array $default = [])
    {
        $value = is_array($key) ? $this->only($key) : $this->input($key);

        return new Fluent($value ?? $default);
    }

    /**
     * Récupère un élément de chaîne de requête à partir de la requête.
     *
     * @return array|string|null
     */
    public function query(?string $key = null, array|string|null $default = null)
    {
        return $this->getQuery($key, $default);
    }

    /**
     * Récupère un élément de charge utile de requête à partir de la requête.
     *
     * @return array|string|null
     */
    public function post(?string $key = null, array|string|null $default = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return Arr::get($this->data, $key, $default);
    }

    /**
     * Détermine si un cookie est défini sur la requête.
     */
    public function hasCookie(string $key): bool
    {
        return null !== $this->cookie($key);
    }

    /**
     * Récupère un cookie de la requête.
     *
     * @return array|string|null
     */
    public function cookie(?string $key = null, array|string|null $default = null)
    {
        if (null === $key) {
            return $this->getCookieParams();
        }

        return $this->getCookie($key, $default);
    }

    /**
     * Renvoie un tableau de tous les fichiers de la requête.
     */
    public function allFiles(): array
    {
        return $this->getUploadedFiles();
    }

    /**
     * Détermine si les données téléchargées contiennent un fichier.
     */
    public function hasFile(string $key): bool
    {
        if (! is_array($files = $this->file($key))) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($this->isValidFile($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie que le fichier donné est une instance de fichier valide.
     */
    protected function isValidFile(mixed $file): bool
    {
        return ($file instanceof SplFileInfo && $file->getPath() !== '') || $file instanceof UploadedFileInterface;
    }

    /**
     * Récupère un fichier à partir de la requête.
     *
     * @return ($key is null ? array<string, list<UploadedFile>|UploadedFile> : list<UploadedFile>|UploadedFile|null)
     */
    public function file(?string $key = null, mixed $default = null)
    {
        return Helpers::dataGet($this->allFiles(), $key, $default);
    }

    /**
     * Récupère les données de l'instance.
     */
    protected function data(?string $key = null, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    /**
     * Vide les éléments de la requête et terminez le script.
     */
    public function dd(...$keys): never
    {
        $this->dump(...$keys);

        exit(1);
    }

    /**
     * Vide les elements.
     */
    public function dump(mixed $keys = []): self
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        Kint::dump($keys !== [] ? $this->only($keys) : $this->all());

        return $this;
    }
}
