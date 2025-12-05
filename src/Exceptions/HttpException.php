<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Exceptions;

class HttpException extends FrameworkException
{
	/**
     * @inheritDoc
     */
    protected int $_defaultCode = 500;

    /**
     * @var array<non-empty-string, array<string>|string>
     */
    protected array $headers = [];

    /**
     * Définir un seul en-tête de réponse HTTP.
     *
     * @param non-empty-string $header Nom de l'en-tête
     * @param array<string>|string|null $value Valeur de l'en-tête
     */
    public function setHeader(string $header, array|string|null $value = null): void
    {
        $this->headers[$header] = $value ?? '';
    }

    /**
     * Définit les en-têtes de réponse HTTP.
     *
     * @param array<non-empty-string, array<string>|string> $headers Tableau de paires nom/valeur d'en-tête.
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Renvoie le tableau d'en-têtes de réponse.
     *
     * @return array<non-empty-string, array<string>|string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public static function methodNotAllowed(string $method): MethodNotFoundException
    {
		return new MethodNotFoundException(self::lang('HTTP.methodNotAllowed', [$method]));
    }

    public static function invalidStatusCode(int $code)
    {
        return new static(lang('HTTP.invalidStatusCode', [$code]));
    }

    public static function unkownStatusCode(int $code)
    {
        return new static(lang('HTTP.unknownStatusCode', [$code]));
    }

    public static function invalidRedirectRoute(string $route)
    {
        return new static(lang('HTTP.invalidRoute', [$route]));
    }

    public static function badRequest(string $message = 'Bad Request')
    {
        return new static($message, 400);
    }

    public static function unableToParseURI(string $uri)
    {
        return new static(lang('HTTP.cannotParseURI', [$uri]));
    }

    public static function uriSegmentOutOfRange(int $segment)
    {
        return new static(lang('HTTP.segmentOutOfRange', [$segment]));
    }

    public static function invalidPort(int $port)
    {
        return new static(lang('HTTP.invalidPort', [$port]));
    }

    public static function malformedQueryString()
    {
        return new static(lang('HTTP.malformedQueryString'));
    }
}
