<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Enums;

use InvalidArgumentException;

/**
 * Liste des methodes http
 */
abstract class Method
{
    /**
     * Sûr : Non
     * Idempotent : Non
     * Cacheable : Non
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/CONNECT
     */
    public const CONNECT = 'CONNECT';

    /**
     * Sûr : Non
     * Idempotent : Oui
     * Cacheable : Non
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/DELETE
     */
    public const DELETE = 'DELETE';

    /**
     * Sûr : Oui
     * Idempotent : Oui
     * Cacheable : Oui
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/GET
     */
    public const GET = 'GET';

    /**
     * Sûr : Oui
     * Idempotent : Oui
     * Cacheable : Oui
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/HEAD
     */
    public const HEAD = 'HEAD';

    /**
     * Sûr : Oui
     * Idempotent : Oui
     * Cacheable : Non
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS
     */
    public const OPTIONS = 'OPTIONS';

    /**
     * Sûr : Non
     * Idempotent : Non
     * Cacheable: Seulement si l'information sur la fraîcheur est incluse
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/PATCH
     */
    public const PATCH = 'PATCH';

    /**
     * Sûr : Non
     * Idempotent : Non
     * Cacheable: Seulement si l'information sur la fraîcheur est incluse
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/POST
     */
    public const POST = 'POST';

    /**
     * Sûr : Non
     * Idempotent : Oui
     * Cacheable : Non
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/PUT
     */
    public const PUT = 'PUT';

    /**
     * Sûr : Oui
     * Idempotent : Oui
     * Cacheable : Non
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/TRACE
     */
    public const TRACE = 'TRACE';

    public static function fromName(string $name): string
    {
        return match (strtolower($name)) {
            'connect' => self::CONNECT,
            'delete'  => self::DELETE,
            'get'     => self::GET,
            'head'    => self::HEAD,
            'options' => self::OPTIONS,
            'patch'   => self::PATCH,
            'post'    => self::POST,
            'put'     => self::PUT,
            'trace'   => self::TRACE,
            default   => throw new InvalidArgumentException('Nom de methode inconnu')
        };
    }

    /**
     * Renvoie la liste de toutes les methodes HTTP.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CONNECT,
            self::DELETE,
            self::GET,
            self::HEAD,
            self::OPTIONS,
            self::PATCH,
            self::POST,
            self::PUT,
            self::TRACE,
        ];
    }
}
