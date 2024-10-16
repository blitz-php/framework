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
 * Analysez les données du corps de la requête encodée.
 *
 * Permet aux charges utiles de requête JSON et XML d'être analysées dans la protection et la validation CSRF de la requête.
 *
 * Vous pouvez également ajouter vos propres analyseurs de corps de requête usi
 *
 * @credit		CakePHP (Cake\Http\Middleware\BodyParserMiddleware - https://cakephp.org)
 */
class BodyParser extends BaseMiddleware implements MiddlewareInterface
{
    /**
     * Parseurs enregistrés
     *
     * @var list<Closure>
     */
    protected array $parsers = [];

    /**
     * Les méthodes HTTP sur lesquelles analyser les données.
     *
     * @var list<string>
     */
    protected array $methods = ['PUT', 'POST', 'PATCH', 'DELETE'];

    /**
     * Constructor
     *
     * ### Options
     *
     * - `json` Définir sur false pour désactiver l'analyse du corps JSON.
     * - `xml` Définir sur true pour activer l'analyse XML. La valeur par défaut est false, en tant que XML
     * La manipulation nécessite plus de soin que JSON.
     * - `methods` Les méthodes HTTP à analyser. Par défaut, PUT, POST, PATCH DELETE.
     */
    public function __construct(array $options = [])
    {
        $options += ['json' => true, 'xml' => false, 'methods' => null];
        if ($options['json']) {
            $this->addParser(
                ['application/json', 'text/json'],
                $this->decodeJson(...)
            );
        }
        if ($options['xml']) {
            $this->addParser(
                ['application/xml', 'text/xml'],
                $this->decodeXml(...)
            );
        }
        if ($options['methods']) {
            $this->setMethods($options['methods']);
        }
    }

    /**
     * Définissez les méthodes HTTP sur lesquelles analyser les corps de requête.
     *
     * @param list<string> $methods Les méthodes sur lesquelles analyser les données.
     */
    public function setMethods(?array $methods): static
    {
        if (is_array($methods)) {
            $this->methods = $methods;
        }

        return $this;
    }

    /**
     * Obtenez les méthodes HTTP pour analyser les corps de requête.
     *
     * @return list<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Ajoute un parser.
     *
     * Mappez un ensemble de valeurs d'en-tête de type de contenu à analyser par $parser.
     *
     * ### Example
     *
     * Un parseur de corps de requête CSV naïf pourrait être construit comme suit :
     *
     * ```
     * $parser->addParser(['text/csv'], function ($body) {
     *   return str_getcsv($body);
     * });
     * ```
     *
     * @param list<string> $types  Un tableau de valeurs d'en-tête de type de contenu à faire correspondre. par exemple. application/json
     * @param Closure      $parser La fonction de parser. Doit renvoyer un tableau de données à insérer dans la requête.
     */
    public function addParser(array $types, Closure $parser): static
    {
        foreach ($types as $type) {
            $type                 = strtolower($type);
            $this->parsers[$type] = $parser;
        }

        return $this;
    }

    /**
     * Obtenir les parseurs actuels
     *
     * @return list<Closure>
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    /**
     * {@inheritDoc}
     *
     * Modifie la requête en ajoutant un corps analysé si le type de contenu est connu.
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
     * Décode JSON dans un tableau.
     *
     * @param string $body Le corps de la requête à décoder
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
     * Décode XML dans un tableau.
     *
     * @param string $body Le corps de la requête à décoder
     */
    protected function decodeXml(string $body): array
    {
        return Formatter::type('application/xml')->parse($body);
    }
}
