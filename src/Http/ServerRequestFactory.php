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

use Psr\Http\Message\UriInterface;
use BlitzPHP\Utilities\Iterable\Arr;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Usine permettant de créer des instances de ServerRequest.
 *
 * Ceci ajoute un comportement spécifique à BlitzPHP pour remplir les attributs basePath et webroot.
 * En outre, le chemin de l'Uri est corrigé pour ne contenir que le chemin "virtuel" pour la requête.
 *
 * @credit CakePHP <a href="https://api.cakephp.org/5.0/class-Cake.Http.ServerRequestFactory.html">Cake\Http\ServerRequestFactory</a>
 * @credit <a href="https://docs.laminas.dev/laminas-diactoros/">Laminas\Diactoros</a>
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * Créer une requête à partir des valeurs superglobales fournies.
     *
     * Si un argument n'est pas fourni, la valeur superglobale correspondante sera utilisée.
     *
     * @param array|null $server     superglobale $_SERVER
     * @param array|null $query      superglobale $_GET
     * @param array|null $parsedBody superglobale $_POST
     * @param array|null $cookies    superglobale $_COOKIE
     * @param array|null $files      superglobale $_FILES
     *
     * @throws InvalidArgumentException pour les valeurs de fichier non valides
     */
    public static function fromGlobals(
        ?array $server = null,
        ?array $query = null,
        ?array $parsedBody = null,
        ?array $cookies = null,
        ?array $files = null
    ): Request {
        $server = self::normalizeServer($server ?? $_SERVER);

        $request = new Request([
            'environment' => $server,
            'cookies'     => $cookies ?? $_COOKIE,
            'query'       => $query ?? $_GET,
            'session'     => service('session'),
            'input'       => $server['BLITZPHP_INPUT'] ?? null,
        ]);

        $request = static::processBodyAndRequestMethod($parsedBody ?? $_POST, $request);
        // Ceci est nécessaire car `ServerRequest::scheme()` ignore la valeur de `HTTP_X_FORWARDED_PROTO`
        // à moins que `trustProxy` soit activé, alors que l'instance `Uri` initialement créée prend
        // toujours en compte les valeurs de HTTP_X_FORWARDED_PROTO`.
        $uri     = $request->getUri()->withScheme($request->scheme());
        $request = $request->withUri($uri, true);

        return static::processFiles($files ?? $_FILES, $request);
    }

    /**
     * Définit la variable d'environnement REQUEST_METHOD en fonction de la valeur HTTP simulée _method.
     * La valeur 'ORIGINAL_REQUEST_METHOD' est également préservée, si vous souhaitez lire la méthode HTTP non simulée utilisée par le client.
     *
     * Le corps de la requête de type "application/x-www-form-urlencoded" est analysé dans un tableau pour les requêtes PUT/PATCH/DELETE.
     *
     * @param array $parsedBody Corps analysé.
     */
    protected static function processBodyAndRequestMethod(array $parsedBody, Request $request): Request
    {
        $method   = $request->getMethod();
        $override = false;

        if (in_array($method, ['PUT', 'DELETE', 'PATCH'], true) && str_starts_with((string) $request->contentType(), 'application/x-www-form-urlencoded')) {
            $data = (string) $request->getBody();
            parse_str($data, $parsedBody);
        }
        if ($request->hasHeader('X-Http-Method-Override')) {
            $parsedBody['_method'] = $request->getHeaderLine('X-Http-Method-Override');
            $override              = true;
        }

        $request = $request->withEnv('ORIGINAL_REQUEST_METHOD', $method);
        if (isset($parsedBody['_method'])) {
            $request = $request->withEnv('REQUEST_METHOD', $parsedBody['_method']);
            unset($parsedBody['_method']);
            $override = true;
        }

        if ($override && ! in_array($request->getMethod(), ['PUT', 'POST', 'DELETE', 'PATCH'], true)) {
            $parsedBody = [];
        }

        return $request->withParsedBody($parsedBody);
    }

    /**
     * Traiter les fichiers téléchargés et déplacer les éléments dans le corps analysé.
     *
     * @param array $files Tableau de fichiers pour la normalisation et la fusion dans le corps analysé.
     */
    protected static function processFiles(array $files, Request $request): Request
    {
        $files   = UploadedFileFactory::normalizeUploadedFiles($files);
        $request = $request->withUploadedFiles($files);

        $parsedBody = $request->getParsedBody();
        if (! is_array($parsedBody)) {
            return $request;
        }

        $parsedBody = Arr::merge($parsedBody, $files);

        return $request->withParsedBody($parsedBody);
    }

    /**
     * Créer une nouvelle requete de serveur.
     *
     * Notez que les paramètres du serveur sont pris tels quels - aucune analyse/traitement
     * des valeurs données n'est effectué, et, en particulier, aucune tentative n'est faite pour
     * déterminer la méthode HTTP ou l'URI, qui doivent être fournis explicitement.
     *
     * @param string                                $method       La méthode HTTP associée à la requete.
     * @param UriInterface|string $uri L'URI associé à la requete.
     *                                                            Si la valeur est une chaîne, la fabrique DOIT créer une instance d'UriInterface basée sur celle-ci.
     * @param array                                 $serverParams Tableau de paramètres SAPI permettant d'alimenter l'instance de requete générée.
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        $serverParams['REQUEST_METHOD'] = $method;
        $options                        = ['environment' => $serverParams];

        if (is_string($uri)) {
            $uri = new Uri($uri);
        }
        $options['uri'] = $uri;

        return new Request($options);
    }

    /**
     * Marshaller le tableau $_SERVER
     *
     * Prétraite et renvoie la superglobale $_SERVER.
     * En particulier, il tente de détecter l'en-tête Authorization, qui n'est souvent pas agrégé correctement sous diverses combinaisons SAPI/httpd.
     *
     * @param callable|null $apacheRequestHeaderCallback Callback qui peut être utilisé pour récupérer les en-têtes de requête Apache.
     *                                                   La valeur par défaut est `apache_request_headers` sous Apache mod_php.
     *
     * @see https://github.com/laminas/laminas-diactoros/blob/3.4.x/src/functions/normalize_server.php
     *
     * @return array Soit $server mot pour mot, soit avec un en-tête HTTP_AUTHORIZATION ajouté.
     */
    private static function normalizeServer(array $server, ?callable $apacheRequestHeaderCallback = null): array
    {
        if (null === $apacheRequestHeaderCallback && function_exists('apache_request_headers')) {
            $apacheRequestHeaderCallback = 'apache_request_headers';
        }

        // Si la valeur HTTP_AUTHORIZATION est déjà définie, ou si le callback n'est pas appelable, nous renvoyons les parameters server sans changements
        if (isset($server['HTTP_AUTHORIZATION']) || ! is_callable($apacheRequestHeaderCallback)) {
            return $server;
        }

        $apacheRequestHeaders = $apacheRequestHeaderCallback();
        if (isset($apacheRequestHeaders['Authorization'])) {
            $server['HTTP_AUTHORIZATION'] = $apacheRequestHeaders['Authorization'];

            return $server;
        }

        if (isset($apacheRequestHeaders['authorization'])) {
            $server['HTTP_AUTHORIZATION'] = $apacheRequestHeaders['authorization'];

            return $server;
        }

        return $server;
    }
}
