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

use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Formatter\Formatter;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * BodyParser
 *
 * Parse encoded request body data.
 *
 * Enables JSON and XML request payloads to be parsed into the request's
 * Provides CSRF protection & validation.
 *
 * You can also add your own request body parsers usi *
 * @credit		CakePHP (Cake\Http\Middleware\BodyParserMiddleware - https://cakephp.org)
 */
class BodyParser implements MiddlewareInterface
{
    /**
     * Registered Parsers
     *
     * @var Closure[]
     */
    protected $parsers = [];

    /**
     * The HTTP methods to parse data on.
     *
     * @var string[]
     */
    protected $methods = ['PUT', 'POST', 'PATCH', 'DELETE'];

    /**
     * Constructor
     *
     * ### Options
     *
     * - `json` Set to false to disable JSON body parsing.
     * - `xml` Set to true to enable XML parsing. Defaults to false, as XML
     *   handling requires more care than JSON does.
     * - `methods` The HTTP methods to parse on. Defaults to PUT, POST, PATCH DELETE.
     *
     * @param array $options The options to use. See above.
     */
    public function __construct(array $options = [])
    {
        $options += ['json' => true, 'xml' => false, 'methods' => null];
        if ($options['json']) {
            $this->addParser(
                ['application/json', 'text/json'],
                Closure::fromCallable([$this, 'decodeJson'])
            );
        }
        if ($options['xml']) {
            $this->addParser(
                ['application/xml', 'text/xml'],
                Closure::fromCallable([$this, 'decodeXml'])
            );
        }
        if ($options['methods']) {
            $this->setMethods($options['methods']);
        }
    }

    /**
     * Set the HTTP methods to parse request bodies on.
     *
     * @param string[] $methods The methods to parse data on.
     */
    public function setMethods(array $methods): self
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * Get the HTTP methods to parse request bodies on.
     *
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Add a parser.
     *
     * Map a set of content-type header values to be parsed by the $parser.
     *
     * ### Example
     *
     * An naive CSV request body parser could be built like so:
     *
     * ```
     * $parser->addParser(['text/csv'], function ($body) {
     *   return str_getcsv($body);
     * });
     * ```
     *
     * @param string[] $types  An array of content-type header values to match. eg. application/json
     * @param Closure  $parser The parser function. Must return an array of data to be inserted
     *                         into the request.
     */
    public function addParser(array $types, Closure $parser): self
    {
        foreach ($types as $type) {
            $type                 = strtolower($type);
            $this->parsers[$type] = $parser;
        }

        return $this;
    }

    /**
     * Get the current parsers
     *
     * @return Closure[]
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    /**
     * Apply the middleware.
     *
     * Will modify the request adding a parsed body if the content-type is known.
     *
     * @param ServerRequestInterface  $request The request.
     * @param RequestHandlerInterface $handler The request handler.
     *
     * @return ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! in_array($request->getMethod(), $this->methods, true)) {
            return $handler->handle($request);
        }

        [$type] = explode(';', $request->getHeaderLine('Content-Type'));
        $type   = strtolower($type);
        if (! isset($this->parsers[$type])) {
            return $handler->handle($request);
        }

        $parser = $this->parsers[$type];
        $result = $parser($request->getBody()->getContents());
        if (! is_array($result)) {
            throw HttpException::badRequest();
        }
        $request = $request->withParsedBody($result);

        return $handler->handle($request);
    }

    /**
     * Decode JSON into an array.
     *
     * @param string $body The request body to decode
     */
    protected function decodeJson(string $body): ?array
    {
        if ($body === '') {
            return [];
        }
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return (array) $decoded;
        }

        return null;
    }

    /**
     * Decode XML into an array.
     *
     * @param string $body The request body to decode
     */
    protected function decodeXml(string $body): array
    {
        return Formatter::type('application/xml')->parse($body);
    }
}
