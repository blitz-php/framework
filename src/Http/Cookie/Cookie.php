<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http\Cookie;

use BlitzPHP\Contracts\Http\CookieInterface;
use BlitzPHP\Utilities\Arr;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Objet cookie pour créer un cookie et le transformer en valeur d'en-tête
 *
 * Un cookie HTTP (également appelé cookie Web, cookie Internet, cookie de navigateur ou
 * simplement cookie) est une petite donnée envoyée depuis un site Web et stockée sur
 * l'ordinateur de l'utilisateur par le navigateur Web de l'utilisateur pendant que l'utilisateur navigue.
 *
 * Les cookies ont été conçus pour être un mécanisme fiable permettant aux sites Web de se souvenir
 * informations avec état (telles que les articles ajoutés dans le panier d'achat en ligne
 * store) ou pour enregistrer l'activité de navigation de l'utilisateur (y compris les clics
 * boutons particuliers, connexion ou enregistrement des pages visitées
 * le passé). Ils peuvent également être utilisés pour mémoriser des informations arbitraires
 * que l'utilisateur a précédemment saisi dans les champs de formulaire tels que les noms et les préférences.
 *
 * Les objets cookies sont immuables et vous devez réaffecter les variables lors de la modification de l'objets cookies :
 *
 * ```
 * $cookie = $cookie->withValue('0');
 * ```
 *
 * @see https://tools.ietf.org/html/draft-ietf-httpbis-rfc6265bis-03
 * @see https://en.wikipedia.org/wiki/HTTP_cookie
 * @see \BlitzPHP\Http\Cookie\CookieCollection for working with collections of cookies.
 * @see \BlitzPHP\Http\Response::getCookieCollection() for working with response cookies.
 * @credit <a href="https://api.cakephp.org/4.3/class-Cake.Http.Cookie.Cookie.html">CakePHP - \Cake\Http\Cookie\Cookie</a>
 */
class Cookie implements CookieInterface
{
    /**
     * Nom du cookie
     *
     * @var string
     */
    protected $name = '';

    /**
     * Valeur brute du cookie.
     *
     * @var array|string
     */
    protected $value = '';

    /**
     * Indique si une valeur JSON a été développée dans un tableau.
     *
     * @var bool
     */
    protected $isExpanded = false;

    /**
     * Date d'expiration
     *
     * @var DateTime|DateTimeImmutable|null
     */
    protected $expiresAt;

    /**
     * Chemin
     *
     * @var string
     */
    protected $path = '/';

    /**
     * Domaine
     *
     * @var string
     */
    protected $domain = '';

    /**
     * Securisé ?
     *
     * @var bool
     */
    protected $secure = false;

    /**
     * Uniquement via HTTP ?
     *
     * @var bool
     */
    protected $httpOnly = false;

    /**
     * Samesite
     *
     * @var string|null
     */
    protected $sameSite;

    /**
     * Attributs par défaut pour un cookie.
     *
     * @var array<string, mixed>
     *
     * @see \BlitzPHP\Http\Cookie\Cookie::setDefaults()
     */
    protected static $defaults = [
        'expires'  => null,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly' => false,
        'samesite' => null,
    ];

    /**
     * Constructor
     *
     * Les arguments des constructeurs sont similaires à la méthode native PHP `setcookie()`.
     * La seule différence est le 3ème argument qui excepte null ou un
     * Objet DateTime ou DateTimeImmutable à la place d'un entier.
     *
     * @see http://php.net/manual/en/function.setcookie.php
     *
     * @param array|string                    $value     Valeur du cookie
     * @param DateTime|DateTimeImmutable|null $expiresAt
     */
    public function __construct(
        string $name,
        $value = '',
        ?DateTimeInterface $expiresAt = null,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?string $sameSite = null
    ) {
        $this->validateName($name);
        $this->name = $name;

        $this->_setValue($value);

        $this->domain   = $domain ?? static::$defaults['domain'];
        $this->httpOnly = $httpOnly ?? static::$defaults['httponly'];
        $this->path     = $path ?? static::$defaults['path'];
        $this->secure   = $secure ?? static::$defaults['secure'];
        if ($sameSite === null) {
            $this->sameSite = static::$defaults['samesite'];
        } else {
            $this->validateSameSiteValue($sameSite);
            $this->sameSite = $sameSite;
        }

        if ($expiresAt) {
            $expiresAt = $expiresAt->setTimezone(new DateTimeZone('GMT'));
        } else {
            $expiresAt = static::$defaults['expires'];
        }
        $this->expiresAt = $expiresAt;
    }

    /**
     * Définissez les options par défaut pour les cookies.
     *
     * Les options valides sont :
     *
     * - `expires` : peut être un horodatage UNIX ou une chaîne compatible `strtotime()` ou une instance `DateTimeInterface` ou `null`.
     * - `path` : une chaîne de chemin. Par défaut `'/'`.
     * - `domain` : chaîne de nom de domaine. La valeur par défaut est ''''.
     * - `httponly` : booléen. La valeur par défaut est "false".
     * - `secure` : booléen. La valeur par défaut est "false".
     * - `samesite` : peut être l'un des éléments suivants : `CookieInterface::SAMESITE_LAX`, `CookieInterface::SAMESITE_STRICT`,
     *              `CookieInterface::SAMESITE_NONE` ou `null`. La valeur par défaut est `null`.
     */
    public static function setDefaults(array $options): void
    {
        if (isset($options['expires'])) {
            $options['expires'] = static::dateTimeInstance($options['expires']);
        }
        if (isset($options['samesite'])) {
            static::validateSameSiteValue($options['samesite']);
        }

        static::$defaults = $options + static::$defaults;
    }

    /**
     * Méthode d'usine pour créer des instances de Cookie.
     *
     * @see \BlitzPHP\Cookie\Cookie::setDefaults()
     *
     * @param mixed $value
     */
    public static function create(string $name, $value, array $options = []): self
    {
        $options += static::$defaults;
        $options['expires'] = static::dateTimeInstance($options['expires']);

        return new static(
            $name,
            $value,
            $options['expires'],
            $options['path'],
            $options['domain'],
            $options['secure'],
            $options['httponly'],
            $options['samesite']
        );
    }

    /**
     * Convertit la valeur d'expiration non nulle en instance DateTimeInterface.
     *
     * @param mixed $expires Expiry value.
     *
     * @return DateTime|DatetimeImmutable|null
     */
    protected static function dateTimeInstance($expires): ?DateTimeInterface
    {
        if ($expires === null) {
            return null;
        }

        if ($expires instanceof DateTimeInterface) {
            /** @psalm-suppress UndefinedInterfaceMethod */
            return $expires->setTimezone(new DateTimeZone('GMT'));
        }

        if (! is_string($expires) && ! is_int($expires)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid type `%s` for expires. Expected an string, integer or DateTime object.',
                getTypeName($expires)
            ));
        }

        if (! is_numeric($expires)) {
            $expires = strtotime($expires) ?: null;
        }

        if ($expires !== null) {
            $expires = new DateTimeImmutable('@' . (string) $expires);
        }

        return $expires;
    }

    /**
     * Créez une instance de cookie à partir de la chaîne d'en-tête "set-cookie".
     *
     * @param string $cookie Chaîne d'en-tête de cookie.
     *
     * @see \BlitzPHP\Http\Cookie\Cookie::setDefaults()
     */
    public static function createFromHeaderString(string $cookie, array $defaults = []): self
    {
        if (strpos($cookie, '";"') !== false) {
            $cookie = str_replace('";"', '{__cookie_replace__}', $cookie);
            $parts  = str_replace('{__cookie_replace__}', '";"', explode(';', $cookie));
        } else {
            $parts = preg_split('/\;[ \t]*/', $cookie);
        }

        [$name, $value] = explode('=', array_shift($parts), 2);
        $data           = [
            'name'  => urldecode($name),
            'value' => urldecode($value),
        ] + $defaults;

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                [$key, $value] = explode('=', $part);
            } else {
                $key   = $part;
                $value = true;
            }

            $key        = strtolower($key);
            $data[$key] = $value;
        }

        if (isset($data['max-age'])) {
            $data['expires'] = time() + (int) $data['max-age'];
            unset($data['max-age']);
        }

        if (isset($data['samesite'])) {
            // Ignorer la valeur non valide lors de l'analyse des en-têtes
            // https://tools.ietf.org/html/draft-west-first-party-cookies-07#section-4.1
            if (! in_array($data['samesite'], CookieInterface::SAMESITE_VALUES, true)) {
                unset($data['samesite']);
            }
        }

        $name  = (string) $data['name'];
        $value = (string) $data['value'];
        unset($data['name'], $data['value']);

        return self::create(
            $name,
            $value,
            $data
        );
    }

    /**
     * Renvoie une valeur d'en-tête sous forme de chaîne
     */
    public function toHeaderValue(): string
    {
        $value = $this->value;
        if ($this->isExpanded) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $value = $this->_flatten($this->value);
        }
        $headerValue = [];
        /** @psalm-suppress PossiblyInvalidArgument */
        $headerValue[] = sprintf('%s=%s', $this->name, rawurlencode($value));

        if ($this->expiresAt) {
            $headerValue[] = sprintf('expires=%s', $this->getFormattedExpires());
        }
        if ($this->path !== '') {
            $headerValue[] = sprintf('path=%s', $this->path);
        }
        if ($this->domain !== '') {
            $headerValue[] = sprintf('domain=%s', $this->domain);
        }
        if ($this->sameSite) {
            $headerValue[] = sprintf('samesite=%s', $this->sameSite);
        }
        if ($this->secure) {
            $headerValue[] = 'secure';
        }
        if ($this->httpOnly) {
            $headerValue[] = 'httponly';
        }

        return implode('; ', $headerValue);
    }

    /**
     * {@inheritDoc}
     */
    public function withName(string $name): self
    {
        $this->validateName($name);
        $new       = clone $this;
        $new->name = $name;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return "{$this->name};{$this->domain};{$this->path}";
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Valide le nom du cookie
     *
     * @throws InvalidArgumentException
     *
     * @see https://tools.ietf.org/html/rfc2616#section-2.2 Rules for naming cookies.
     */
    protected function validateName(string $name): void
    {
        if (preg_match("/[=,;\t\r\n\013\014]/", $name)) {
            throw new InvalidArgumentException(
                sprintf('The cookie name `%s` contains invalid characters.', $name)
            );
        }

        if (empty($name)) {
            throw new InvalidArgumentException('The cookie name cannot be empty.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getScalarValue()
    {
        if ($this->isExpanded) {
            /** @psalm-suppress PossiblyInvalidArgument */
            return $this->_flatten($this->value);
        }

        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function withValue($value): self
    {
        $new = clone $this;
        $new->_setValue($value);

        return $new;
    }

    /**
     * Setter pour l'attribut de valeur.
     *
     * @param array|string $value
     */
    protected function _setValue($value): void
    {
        $this->isExpanded = is_array($value);
        $this->value      = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function withPath(string $path): self
    {
        $new       = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function withDomain(string $domain): self
    {
        $new         = clone $this;
        $new->domain = $domain;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * {@inheritDoc}
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * {@inheritDoc}
     */
    public function withSecure(bool $secure): self
    {
        $new         = clone $this;
        $new->secure = $secure;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withHttpOnly(bool $httpOnly): self
    {
        $new           = clone $this;
        $new->httpOnly = $httpOnly;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * {@inheritDoc}
     */
    public function withExpiry($dateTime): self
    {
        $new            = clone $this;
        $new->expiresAt = $dateTime->setTimezone(new DateTimeZone('GMT'));

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiry()
    {
        return $this->expiresAt;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiresTimestamp(): ?int
    {
        if (! $this->expiresAt) {
            return null;
        }

        return (int) $this->expiresAt->format('U');
    }

    /**
     * {@inheritDoc}
     */
    public function getFormattedExpires(): string
    {
        if (! $this->expiresAt) {
            return '';
        }

        return $this->expiresAt->format(static::EXPIRES_FORMAT);
    }

    /**
     * {@inheritDoc}
     */
    public function isExpired($time = null): bool
    {
        $time = $time ?: new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (! $this->expiresAt) {
            return false;
        }

        return $this->expiresAt < $time;
    }

    /**
     * {@inheritDoc}
     */
    public function withNeverExpire()
    {
        $new            = clone $this;
        $new->expiresAt = new DateTimeImmutable('2038-01-01');

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withExpired()
    {
        $new            = clone $this;
        $new->expiresAt = new DateTimeImmutable('1970-01-01 00:00:01');

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    /**
     * {@inheritDoc}
     */
    public function withSameSite(?string $sameSite)
    {
        if ($sameSite !== null) {
            $this->validateSameSiteValue($sameSite);
        }

        $new           = clone $this;
        $new->sameSite = $sameSite;

        return $new;
    }

    /**
     * Vérifiez que la valeur transmise pour SameSite est valide.
     *
     * @throws InvalidArgumentException
     */
    protected static function validateSameSiteValue(string $sameSite)
    {
        if (! in_array($sameSite, CookieInterface::SAMESITE_VALUES, true)) {
            throw new InvalidArgumentException(
                'Samesite value must be either of: ' . implode(', ', CookieInterface::SAMESITE_VALUES)
            );
        }
    }

    /**
     * Vérifie si une valeur existe dans les données du cookie.
     *
     * Cette méthode étendra les données complexes sérialisées,
     * à la première utilisation.
     */
    public function check(string $path): bool
    {
        if ($this->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $this->value = $this->_expand($this->value);
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        return Arr::check($this->value, $path);
    }

    /**
     * Créer un nouveau cookie avec des données mises à jour.
     *
     * @param mixed $value
     */
    public function withAddedValue(string $path, $value): self
    {
        $new = clone $this;
        if ($new->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $new->value = $new->_expand($new->value);
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        $new->value = Arr::insert($new->value, $path, $value);

        return $new;
    }

    /**
     * Créer un nouveau cookie sans chemin spécifique
     */
    public function withoutAddedValue(string $path): self
    {
        $new = clone $this;
        if ($new->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $new->value = $new->_expand($new->value);
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        $new->value = Arr::remove($new->value, $path);

        return $new;
    }

    /**
     * Lire les données du cookie
     *
     * Cette méthode étendra les données complexes sérialisées,
     * à la première utilisation.
     */
    public function read(?string $path = null)
    {
        if ($this->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $this->value = $this->_expand($this->value);
        }

        if ($path === null) {
            return $this->value;
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        return Arr::get($this->value, $path);
    }

    /**
     * Vérifie si la valeur du cookie a été étendue
     */
    public function isExpanded(): bool
    {
        return $this->isExpanded;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(): array
    {
        $options = [
            'expires'  => (int) $this->getExpiresTimestamp(),
            'path'     => $this->path,
            'domain'   => $this->domain,
            'secure'   => $this->secure,
            'httponly' => $this->httpOnly,
        ];

        if ($this->sameSite !== null) {
            $options['samesite'] = $this->sameSite;
        }

        return $options;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'name'  => $this->name,
            'value' => $this->getScalarValue(),
        ] + $this->getOptions();
    }

    /**
     * La méthode Implode pour conserver les clés sont des tableaux multidimensionnels
     *
     * @param array $array Carte des clés et des valeurs
     *
     * @return string Une chaîne encodée JSON.
     */
    protected function _flatten(array $array): string
    {
        return json_encode($array);
    }

    /**
     * Méthode Explode pour renvoyer le tableau à partir de la chaîne définie dans CookieComponent :: _flatten ()
     * Maintient la rétrocompatibilité de lecture avec 1.x CookieComponent::_flatten().
     *
     * @param string $string Une chaîne contenant des données encodées JSON, ou une chaîne nue.
     *
     * @return array|string Carte des clés et des valeurs
     */
    protected function _expand(string $string)
    {
        $this->isExpanded = true;
        $first            = substr($string, 0, 1);
        if ($first === '{' || $first === '[') {
            $ret = json_decode($string, true);

            return $ret ?? $string;
        }

        $array = [];

        foreach (explode(',', $string) as $pair) {
            $key = explode('|', $pair);
            if (! isset($key[1])) {
                return $key[0];
            }
            $array[$key[0]] = $key[1];
        }

        return $array;
    }
}
