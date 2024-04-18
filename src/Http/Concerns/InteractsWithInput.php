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
use BlitzPHP\Utilities\Date;
use BlitzPHP\Utilities\Iterable\Arr;
use BlitzPHP\Utilities\Iterable\Collection;
use BlitzPHP\Utilities\String\Stringable;
use BlitzPHP\Utilities\String\Text;
use Kint\Kint;
use Psr\Http\Message\UploadedFileInterface;
use SplFileInfo;
use stdClass;

/**
 * @credit <a href="http://laravel.com/">Laravel - Illuminate\Http\Concerns\InteractsWithInput</a>
 */
trait InteractsWithInput
{
    /**
     * Récupérez une variable de serveur à partir de la requête.
     *
     * @return array|string|null
     */
    public function server(?string $key = null, null|array|string $default = null)
    {
        return Arr::get($this->_environment, $key, $default);
    }

    /**
     * Récupérer un en-tête de la requête.
     *
     * @return array|string|null
     */
    public function header(?string $key = null, null|array|string $default = null)
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

        $position = strrpos($header, 'Bearer ');

        if ($position !== false) {
            $header = substr($header, $position + 7);

            return str_contains($header, ',') ? strstr($header, ',', true) : $header;
        }

        return null;
    }

    /**
     * Déterminez si la demande contient une clé d'élément d'entrée donnée.
     */
    public function exists(array|string $key): bool
    {
        return $this->has($key);
    }

    /**
     *Déterminez si la demande contient une clé d'élément d'entrée donnée.
     */
    public function has(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        $input = $this->all();

        foreach ($keys as $value) {
            if (! Arr::has($input, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Déterminez si la demande contient l'une des entrées données.
     */
    public function hasAny(array|string $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $input = $this->all();

        return Arr::hasAny($input, $keys);
    }

    /**
     * Appliquez le rappel si la demande contient la clé d'élément d'entrée donnée.
     *
     * @return mixed|self
     */
    public function whenHas(string $key, callable $callback, ?callable $default = null)
    {
        if ($this->has($key)) {
            return $callback(Arr::dataGet($this->all(), $key)) ?: $this;
        }

        if ($default) {
            return $default();
        }

        return $this;
    }

    /**
     * Déterminez si la requête contient une valeur non vide pour un élément d'entrée.
     */
    public function filled(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if ($this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Déterminez si la requête contient une valeur vide pour un élément d'entrée.
     */
    public function isNotFilled(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if (! $this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the request contains a non-empty value for any of the given inputs.
     */
    public function anyFilled(array|string $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if ($this->filled($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Appliquez le rappel si la requête contient une valeur non vide pour la clé d'élément d'entrée donnée.
     *
     * @return mixed|self
     */
    public function whenFilled(string $key, callable $callback, ?callable $default = null)
    {
        if ($this->filled($key)) {
            return $callback(Arr::dataGet($this->all(), $key)) ?: $this;
        }

        if ($default) {
            return $default();
        }

        return $this;
    }

    /**
     * Déterminez s'il manque une clé d'élément d'entrée donnée dans la requête.
     */
    public function missing(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        return ! $this->has($keys);
    }

    /**
     * Appliquez le rappel s'il manque à la demande la clé d'élément d'entrée donnée.
     *
     * @return mixed|self
     */
    public function whenMissing(string $key, callable $callback, ?callable $default = null)
    {
        if ($this->missing($key)) {
            return $callback(Arr::dataGet($this->all(), $key)) ?: $this;
        }

        if ($default) {
            return $default();
        }

        return $this;
    }

    /**
     * Déterminez si la clé d'entrée donnée est une chaîne vide pour "remplie".
     */
    protected function isEmptyString(string $key): bool
    {
        $value = $this->input($key);

        return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
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
     * Récupérer un élément d'entrée de la requête.
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        return Arr::dataGet(
            $this->data + $this->query,
            $key,
            $default
        );
    }

    /**
     * Récupérez l'entrée de la requête en tant qu'instance Stringable.
     */
    public function str(string $key, mixed $default = null): Stringable
    {
        return Text::of($this->input($key, $default));
    }

    /**
     * Récupérez l'entrée de la requête en tant que chaine de caractere.
     */
    public function string(string $key, mixed $default = null): string
    {
        return $this->str($key, $default)->value();
    }

    /**
     * Récupérer l'entrée sous forme de valeur booléenne.
     *
     * Renvoie true lorsque la valeur est "1", "true", "on" et "yes". Sinon, renvoie faux.
     */
    public function boolean(?string $key = null, bool $default = false): bool
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Récupérer l'entrée sous forme de valeur entière.
     */
    public function integer(string $key, int $default = 0): int
    {
        return (int) ($this->input($key, $default));
    }

    /**
     * Récupérer l'entrée sous forme de valeur flottante.
     */
    public function float(string $key, float $default = 0.0): float
    {
        return (float) ($this->input($key, $default));
    }

    /**
     * Récupérez l'entrée de la demande en tant qu'instance Date.
     */
    public function date(string $key, ?string $format = null, ?string $tz = null): ?Date
    {
        if ($this->isNotFilled($key)) {
            return null;
        }

        if (null === $format) {
            return Date::parse($this->input($key), $tz);
        }

        return Date::createFromFormat($format, $this->input($key), $tz);
    }

    /**
     * Retrieve input from the request as an enum.
     *
     * @template TEnum
     *
     * @param class-string<TEnum> $enumClass
     *
     * @return TEnum|null
     */
    public function enum(string $key, $enumClass)
    {
        if ($this->isNotFilled($key)
            || ! function_exists('enum_exists')
            || ! enum_exists($enumClass)
            || ! method_exists($enumClass, 'tryFrom')) {
            return null;
        }

        return $enumClass::tryFrom($this->input($key));
    }

    /**
     * Récupérer l'entrée de la requête sous forme de collection.
     */
    public function collect(null|array|string $key = null): Collection
    {
        return collect(is_array($key) ? $this->only($key) : $this->input($key));
    }

    /**
     * Obtenez un sous-ensemble contenant les clés fournies avec les valeurs des données d'entrée.
     *
     * @param array|mixed $keys
     */
    public function only($keys): array
    {
        $results = [];

        $input = $this->all();

        $placeholder = new stdClass();

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            $value = Arr::dataGet($input, $key, $placeholder);

            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }

        return $results;
    }

    /**
     * Récupère toutes les entrées à l'exception d'un tableau d'éléments spécifié.
     *
     * @param array|mixed $keys
     */
    public function except($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = $this->all();

        Arr::forget($results, $keys);

        return $results;
    }

    /**
     * Récupérez un élément de chaîne de requête à partir de la demande.
     *
     * @return array|string|null
     */
    public function query(?string $key = null, null|array|string $default = null)
    {
        return $this->getQuery($key, $default);
    }

    /**
     * Récupérer un élément de charge utile de requête à partir de la requête.
     *
     * @return array|string|null
     */
    public function post(?string $key = null, null|array|string $default = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return Arr::get($this->data, $key, $default);
    }

    /**
     * Déterminez si un cookie est défini sur la demande.
     */
    public function hasCookie(string $key): bool
    {
        return null !== $this->cookie($key);
    }

    /**
     * Récupérer un cookie de la requête.
     *
     * @return array|string|null
     */
    public function cookie(?string $key = null, null|array|string $default = null)
    {
        if (null === $key) {
            return $this->getCookieParams();
        }

        return $this->getCookie($key, $default);
    }

    /**
     * Obtenez un tableau de tous les fichiers de la requête.
     */
    public function allFiles(): array
    {
        return $this->getUploadedFiles();
    }

    /**
     * Déterminez si les données téléchargées contiennent un fichier.
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
     * Vérifiez que le fichier donné est une instance de fichier valide.
     */
    protected function isValidFile(mixed $file): bool
    {
        return ($file instanceof SplFileInfo && $file->getPath() !== '') || $file instanceof UploadedFileInterface;
    }

    /**
     * Récupérer un fichier à partir de la requête.
     *
     * @return array|UploadedFile|UploadedFile[]|null
     */
    public function file(?string $key = null, mixed $default = null)
    {
        return Arr::dataGet($this->allFiles(), $key, $default);
    }

    /**
     * Videz les éléments de la requête et terminez le script.
     *
     * @return never
     */
    public function dd(...$keys)
    {
        $this->dump(...$keys);

        exit(1);
    }

    /**
     * Videz les elements.
     *
     * @param mixed $keys
     */
    public function dump($keys = []): self
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        Kint::dump(count($keys) > 0 ? $this->only($keys) : $this->all());

        return $this;
    }
}
