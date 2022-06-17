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

use ArrayIterator;
use BlitzPHP\Contracts\Http\CookieInterface;
use Countable;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

/**
 * Fournit une collection immuable d'objets cookies. Ajout ou suppression
 * à une collection renvoie une *nouvelle* collection que vous devez conserver.
 *
 * @credit <a href="https://api.cakephp.org/4.3/class-Cake.Http.Cookie.CookieCollection.html">CakePHP - \Cake\Http\Cookie\CookieCollection</a>
 */
/** @phpstan-consistent-constructor */
class CookieCollection implements IteratorAggregate, Countable
{
    /**
     * Cbjets cookies
     *
     * @var CookieInterface[]
     */
    protected $cookies = [];

    /**
     * Constructeur
     *
     * @param CookieInterface[] $cookies Tableau de cookies
     */
    public function __construct(array $cookies = [])
    {
        $this->checkCookies($cookies);

        foreach ($cookies as $cookie) {
            $this->cookies[$cookie->getId()] = $cookie;
        }
    }

    /**
     * Créer une collection de cookies à partir d'un tableau d'en-têtes Set-Cookie
     *
     * @param array<string>        $header   Le tableau des valeurs d'en-tête set-cookie.
     * @param array<string, mixed> $defaults Les attributs par défaut.
     */
    public static function createFromHeader(array $header, array $defaults = []): self
    {
        $cookies = [];

        foreach ($header as $value) {
            try {
                $cookies[] = Cookie::createFromHeaderString($value, $defaults);
            } catch (Exception $e) {
                // Ne pas exploser sur les cookies invalides
            }
        }

        return new static($cookies);
    }

    /**
     * Créer une nouvelle collection à partir des cookies dans un ServerRequest
     */
    public static function createFromServerRequest(ServerRequestInterface $request): self
    {
        $data    = $request->getCookieParams();
        $cookies = [];

        foreach ($data as $name => $value) {
            $cookies[] = new Cookie($name, $value);
        }

        return new static($cookies);
    }

    /**
     * Obtenir le nombre de cookies dans la collection.
     */
    public function count(): int
    {
        return count($this->cookies);
    }

    /**
     * Ajoutez un cookie et obtenez une collection mise à jour.
     *
     * Les cookies sont stockés par identifiant. Cela signifie qu'il peut y avoir des doublons
     * cookies si une collection de cookies est utilisée pour les cookies sur plusieurs
     * domaines. Cela peut avoir un impact sur le comportement de get(), has() et remove().
     */
    public function add(CookieInterface $cookie): self
    {
        $new                            = clone $this;
        $new->cookies[$cookie->getId()] = $cookie;

        return $new;
    }

    /**
     * Obtenez le premier cookie par son nom.
     *
     * @throws InvalidArgumentException If cookie not found.
     */
    public function get(string $name): CookieInterface
    {
        $key = mb_strtolower($name);

        foreach ($this->cookies as $cookie) {
            if (mb_strtolower($cookie->getName()) === $key) {
                return $cookie;
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Cookie %s not found. Use has() to check first for existence.',
                $name
            )
        );
    }

    /**
     * Vérifier si un cookie avec le nom donné existe
     */
    public function has(string $name): bool
    {
        $key = mb_strtolower($name);

        foreach ($this->cookies as $cookie) {
            if (mb_strtolower($cookie->getName()) === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Créez une nouvelle collection avec tous les cookies correspondant à $name supprimés.
     *
     * Si le cookie n'est pas dans la collection, cette méthode ne fera rien.
     */
    public function remove(string $name): self
    {
        $new = clone $this;
        $key = mb_strtolower($name);

        foreach ($new->cookies as $i => $cookie) {
            if (mb_strtolower($cookie->getName()) === $key) {
                unset($new->cookies[$i]);
            }
        }

        return $new;
    }

    /**
     * Vérifie si seuls des objets de cookie valides sont dans le tableau
     *
     * @param CookieInterface[] $cookies
     *
     * @throws InvalidArgumentException
     */
    protected function checkCookies(array $cookies): void
    {
        foreach ($cookies as $index => $cookie) {
            if (! $cookie instanceof CookieInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Expected `%s[]` as $cookies but instead got `%s` at index %d',
                        static::class,
                        getTypeName($cookie),
                        $index
                    )
                );
            }
        }
    }

    /**
     * Obtient l'itérateur
     *
     * @return Traversable<string, CookieInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->cookies);
    }

    /**
     * Ajoutez des cookies qui correspondent au chemin/domaine/expiration de la requête.
     *
     * Cela permet aux CookieCollections d'être utilisées comme "cookie jar" dans un client HTTP
     * situation. Cookies correspondant au domaine + chemin de la requête qui n'ont pas expiré
     * lorsque cette méthode est appelée sera appliquée à la requête.
     *
     * @param array $extraCookies Tableau associatif de cookies supplémentaires à ajouter à la requête. Ceci
     *                            est utile lorsque vous avez des données de cookies en dehors de la collection que vous souhaitez envoyer.
     */
    public function addToRequest(RequestInterface $request, array $extraCookies = []): RequestInterface
    {
        $uri     = $request->getUri();
        $cookies = $this->findMatchingCookies(
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPath() ?: '/'
        );
        $cookies     = array_merge($cookies, $extraCookies);
        $cookiePairs = [];

        foreach ($cookies as $key => $value) {
            $cookie = sprintf('%s=%s', rawurlencode($key), rawurlencode($value));
            $size   = strlen($cookie);
            if ($size > 4096) {
                trigger_warning(sprintf(
                    'The cookie `%s` exceeds the recommended maximum cookie length of 4096 bytes.',
                    $key
                ));
            }
            $cookiePairs[] = $cookie;
        }

        if (empty($cookiePairs)) {
            return $request;
        }

        return $request->withHeader('Cookie', implode('; ', $cookiePairs));
    }

    /**
     * Rechercher les cookies correspondant au schéma, à l'hôte et au chemin
     */
    protected function findMatchingCookies(string $scheme, string $host, string $path): array
    {
        $out = [];
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        foreach ($this->cookies as $cookie) {
            if ($scheme === 'http' && $cookie->isSecure()) {
                continue;
            }
            if (strpos($path, $cookie->getPath()) !== 0) {
                continue;
            }
            $domain     = $cookie->getDomain();
            $leadingDot = substr($domain, 0, 1) === '.';
            if ($leadingDot) {
                $domain = ltrim($domain, '.');
            }

            if ($cookie->isExpired($now)) {
                continue;
            }

            $pattern = '/' . preg_quote($domain, '/') . '$/';
            if (! preg_match($pattern, $host)) {
                continue;
            }

            $out[$cookie->getName()] = $cookie->getValue();
        }

        return $out;
    }

    /**
     * Créez une nouvelle collection qui inclut les cookies de la réponse.
     */
    public function addFromResponse(ResponseInterface $response, RequestInterface $request): self
    {
        $uri  = $request->getUri();
        $host = $uri->getHost();
        $path = $uri->getPath() ?: '/';

        $cookies = static::createFromHeader(
            $response->getHeader('Set-Cookie'),
            ['domain' => $host, 'path' => $path]
        );
        $new = clone $this;

        foreach ($cookies as $cookie) {
            $new->cookies[$cookie->getId()] = $cookie;
        }
        $new->removeExpiredCookies($host, $path);

        return $new;
    }

    /**
     * Supprimez les cookies expirés de la collection.
     */
    protected function removeExpiredCookies(string $host, string $path): void
    {
        $time        = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $hostPattern = '/' . preg_quote($host, '/') . '$/';

        foreach ($this->cookies as $i => $cookie) {
            if (! $cookie->isExpired($time)) {
                continue;
            }
            $pathMatches = strpos($path, $cookie->getPath()) === 0;
            $hostMatches = preg_match($hostPattern, $cookie->getDomain());
            if ($pathMatches && $hostMatches) {
                unset($this->cookies[$i]);
            }
        }
    }
}
