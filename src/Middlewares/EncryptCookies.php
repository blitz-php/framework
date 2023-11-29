<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Middlewares;

use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Contracts\Session\CookieInterface;
use BlitzPHP\Contracts\Session\CookieManagerInterface;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Session\Cookie\CookieCollection;
use BlitzPHP\Session\Cookie\CookieValuePrefix;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class EncryptCookies implements MiddlewareInterface
{
    /**
     * Les noms des cookies qui ne doivent pas être cryptés.
     *
     * @var string[]
     */
    protected array $except = [];

    /**
     * Indique si les cookies doivent être sérialisés.
     */
    protected static bool $serialize = false;

    /**
     * Créez une nouvelle instance CookieGuard.
     */
    public function __construct(protected EncrypterInterface $encrypter, protected CookieManagerInterface $cookieManager)
    {
    }

    /**
     * Désactivez le cryptage pour le(s) nom(s) de cookie donné(s).
     */
    public function disableFor(array|string $name): void
    {
        $this->except = array_merge($this->except, (array) $name);
    }

    /**
     * @param Request $request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->encrypt($handler->handle($this->decrypt($request)));
    }

    /**
     * Décryptez les cookies sur requete.
     */
    protected function decrypt(Request $request): Request
    {
        $cookies = new CookieCollection();

        foreach ($request->getCookieParams() as $name => $cookie) {
            if ($this->isDisabled($name)) {
                continue;
            }

            try {
                $value = $this->decryptCookie($name, $cookie);
                $value = $this->validateValue($name, $value);
                $cookies->add($this->cookieManager->make($name, $value));
            } catch (Exception) {
            }
        }

        return $request->withCookieCollection($cookies);
    }

    /**
     * Validez et supprimez le préfixe de valeur du cookie de la valeur.
     *
     * @return array|string|null
     */
    protected function validateValue(string $key, array|string $value)
    {
        return is_array($value)
                    ? $this->validateArray($key, $value)
                    : CookieValuePrefix::validate($key, $value, $this->encrypter->getKey());
    }

    /**
     * Validez et supprimez le préfixe de valeur du cookie de toutes les valeurs d'un tableau.
     */
    protected function validateArray(string $key, array $value): array
    {
        $validated = [];

        foreach ($value as $index => $subValue) {
            $validated[$index] = $this->validateValue("{$key}[{$index}]", $subValue);
        }

        return $validated;
    }

    /**
     * Décryptez le cookie donné et renvoyez la valeur.
     */
    protected function decryptCookie(string $name, array|string $cookie): array|string
    {
        return is_array($cookie)
                        ? $this->decryptArray($cookie)
                        : $this->encrypter->decrypt($cookie);
    }

    /**
     * Décryptez un cookie basé sur un tableau.
     */
    protected function decryptArray(array $cookie): array
    {
        $decrypted = [];

        foreach ($cookie as $key => $value) {
            if (is_string($value)) {
                $decrypted[$key] = $this->encrypter->decrypt($value);
            }

            if (is_array($value)) {
                $decrypted[$key] = $this->decryptArray($value);
            }
        }

        return $decrypted;
    }

    /**
     * Chiffrez les cookies sur une réponse sortante.
     *
     * @param Response $response
     */
    protected function encrypt(ResponseInterface $response): Response
    {
        foreach ($response->getCookieCollection() as $cookie) {
            if ($this->isDisabled($cookie->getName())) {
                continue;
            }

            $response = $response->withCookie($this->duplicate(
                $cookie,
                $this->encrypter->encrypt(
                    CookieValuePrefix::create($cookie->getName(), $this->encrypter->getKey()) . $cookie->getValue(),
                )
            ));
        }

        return $response;
    }

    /**
     * Dupliquez un cookie avec une nouvelle valeur.
     */
    protected function duplicate(CookieInterface $cookie, mixed $value): CookieInterface
    {
        return $cookie->withValue($value);
    }

    /**
     * Déterminez si le cryptage a été désactivé pour le cookie donné.
     */
    public function isDisabled(string $name): bool
    {
        return in_array($name, $this->except, true);
    }

    /**
     * Déterminez si le contenu du cookie doit être sérialisé.
     */
    public static function serialized(string $name): bool
    {
        return static::$serialize;
    }
}
