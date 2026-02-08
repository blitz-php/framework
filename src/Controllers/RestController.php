<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Controllers;

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Exceptions\ValidationException;
use BlitzPHP\Formatter\Formatter;
use BlitzPHP\Traits\Http\ApiResponseTrait;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Contrôleur de base pour les API REST
 *
 * Cette classe fournit une base complète pour créer des API RESTful avec :
 * - Gestion des formats de réponse (JSON, XML, CSV, etc.)
 * - Validation des requêtes
 * - Gestion centralisée des erreurs
 * - Hooks avant/après l'exécution
 * - Configuration fluide
 */
class RestController extends BaseController
{
    use ApiResponseTrait;

    /**
     * Configuration REST chargée depuis le fichier de configuration
     *
     * @var array{
     *     locale: string,
     *     force_https: bool,
     *     format: string,
     *     strict: bool,
     *     field: array{status: string, message: string, code: string, errors: string, result: string},
     *     ip_blacklist: array<string>,
     *     ip_whitelist: array<string>,
     *     ajax_only: bool
     * }
     */
    protected array $restConfig;

    /**
     * Locale à utiliser pour les messages de l'API
     */
    protected string $locale;

    /**
     * Mapping des types MIME pour chaque format de sortie
     *
     * @var array<string, string>
     */
    protected array $mimes = [
        'json'       => 'application/json',
        'csv'        => 'application/csv',
        'jsonp'      => 'application/javascript',
        'php'        => 'text/plain',
        'serialized' => 'application/vnd.php.serialized',
        'xml'        => 'application/xml',
        'array'      => 'php/array',
    ];

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->restConfig = config('rest');
        $this->locale     = $this->restConfig['locale'] ?? $this->request->getLocale();
    }

    /**
     * Point d'entrée principal pour toutes les requêtes API
     *
     * Cette méthode est appelée automatiquement par le routeur et gère :
     * 1. Vérification de l'existence de la méthode
     * 2. Exécution des hooks "before"
     * 3. Validation de la requête
     * 4. Exécution de la méthode du contrôleur
     * 5. Gestion des exceptions
     * 6. Exécution des hooks "after"
     *
     * @param string $method Nom de la méthode à exécuter
     * @param array  $params Paramètres à passer à la méthode
     *
     * @return ResponseInterface Réponse HTTP formatée
     */
    public function _remap(string $method, array $params = []): ResponseInterface
    {
        if (! method_exists($this, $method)) {
            return $this->respondNotImplemented($this->_translate('notImplemented', [static::class, $method]));
        }

        try {
            // Hook before
            $before = $this->before($method, $params);
            if ($before instanceof ResponseInterface) {
                return $before;
            }

            // Validation de la requête
            if (($check = $this->validateRequest()) instanceof ResponseInterface) {
                return $check;
            }

            // Exécution de la méthode
            $returned = service('container')->call([$this, $method], $params);
            $response = $returned instanceof ResponseInterface ? $returned : $this->respond($returned);

            // Hook after
            $this->after($method, $params, $response);

            return $response;
        } catch (Throwable $ex) {
            return $this->handleException($ex);
        }
    }

    /**
     * Gestion centralisée des exceptions
     *
     * Transforme les exceptions en réponses API formatées
     *
     * @param Throwable $ex Exception à gérer
     *
     * @return ResponseInterface Réponse d'erreur formatée
     */
    protected function handleException(Throwable $ex): ResponseInterface
    {
        if ($ex instanceof ValidationException) {
            return $this->respondBadRequest(
                'Validation failed',
                $ex->getCode(),
                $ex->getErrors()->all()
            );
        }

        if (! on_dev()) {
            $url = explode('?', $this->request->getRequestTarget())[0];

            return $this->respondBadRequest($this->_translate('badUsed', [$url]));
        }

        return $this->respondInternalError('Internal Server Error', [
            'type'    => $ex::class,
            'message' => $ex->getMessage(),
            'code'    => $ex->getCode(),
            'file'    => $ex->getFile(),
            'line'    => $ex->getLine(),
        ]);
    }

    /**
     * Validation complète de la requête
     *
     * Exécute une série de validateurs dans l'ordre :
     * 1. Restriction AJAX
     * 2. Vérification HTTPS
     * 3. Liste noire d'IP
     * 4. Liste blanche d'IP
     *
     * @return bool|ResponseInterface true si la validation réussit, sinon une réponse d'erreur
     */
    protected function validateRequest(): bool|ResponseInterface
    {
        $validators = [
            'validateAjaxOnly',
            'validateHttps',
            'validateIpBlacklist',
            'validateIpWhitelist',
        ];

        foreach ($validators as $validator) {
            if (($result = $this->{$validator}()) instanceof ResponseInterface) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Vérifie si seules les requêtes AJAX sont autorisées
     *
     * @return bool|ResponseInterface true si la requête est AJAX ou si la restriction est désactivée
     */
    protected function validateAjaxOnly(): bool|ResponseInterface
    {
        if ($this->restConfig['ajax_only'] && ! $this->request->is('ajax')) {
            return $this->respondNotAcceptable($this->_translate('ajaxOnly'));
        }

        return true;
    }

    /**
     * Vérifie si HTTPS est requis
     *
     * @return bool|ResponseInterface true si la requête utilise HTTPS ou si HTTPS n'est pas requis
     */
    protected function validateHttps(): bool|ResponseInterface
    {
        if ($this->restConfig['force_https'] && ! $this->request->is('https')) {
            return $this->respondForbidden($this->_translate('unsupported'));
        }

        return true;
    }

    /**
     * Vérifie la liste noire d'IP
     *
     * @return bool|ResponseInterface true si l'IP du client n'est pas dans la liste noire
     */
    protected function validateIpBlacklist(): bool|ResponseInterface
    {
        $blacklist = $this->restConfig['ip_blacklist'];
        if (! empty($blacklist) && in_array($this->request->clientIp(), $blacklist, true)) {
            return $this->respondUnauthorized($this->_translate('ipDenied'));
        }

        return true;
    }

    /**
     * Vérifie la liste blanche d'IP
     *
     * @return bool|ResponseInterface true si l'IP du client est dans la liste blanche
     */
    protected function validateIpWhitelist(): bool|ResponseInterface
    {
        $whitelist = $this->restConfig['ip_whitelist'];
        if (! empty($whitelist)) {
            $whitelist = array_merge($whitelist, ['127.0.0.1', '0.0.0.0']);
            if (! in_array($this->request->clientIp(), $whitelist, true)) {
                return $this->respondUnauthorized($this->_translate('ipUnauthorized'));
            }
        }

        return true;
    }

    /**
     * --------------------------------------------------------------------------
     * MÉTHODES DE CONFIGURATION (Fluent Interface)
     * --------------------------------------------------------------------------
     */

    /**
     * Restreint l'accès aux requêtes AJAX uniquement
     */
    public function ajaxOnly(): self
    {
        $this->restConfig['ajax_only'] = true;

        return $this;
    }

    /**
     * Définit le format de réponse
     *
     * @param string $format Format de réponse (json, xml, csv, etc.)
     */
    public function returnFormat(string $format): self
    {
        if (array_key_exists($format, $this->mimes) || in_array($format, $this->mimes, true)) {
            $this->restConfig['format'] = $format;
        }

        return $this;
    }

    /**
     * Force l'utilisation de HTTPS
     */
    public function requireHttps(): self
    {
        $this->restConfig['force_https'] = true;

        return $this;
    }

    /**
     * Définit la liste noire d'adresses IP
     *
     * @param string ...$ips Liste des adresses IP à bloquer
     */
    public function ipBlacklist(string ...$ips): self
    {
        $this->restConfig['ip_blacklist'] = $ips;

        return $this;
    }

    /**
     * Définit la liste blanche d'adresses IP
     *
     * @param string ...$ips Liste des adresses IP autorisées
     */
    public function ipWhitelist(string ...$ips): self
    {
        $this->restConfig['ip_whitelist'] = $ips;

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * MÉTHODES DU TRAIT ApiResponseTrait
     * --------------------------------------------------------------------------
     *
     * @param mixed $data
     */

    /**
     * Formate et envoie une réponse HTTP
     *
     * @param mixed $data   Données à envoyer dans la réponse
     * @param int   $status Code de statut HTTP (200 par défaut)
     *
     * @return ResponseInterface Réponse HTTP formatée
     */
    protected function respond($data, ?int $status = StatusCode::OK): ResponseInterface
    {
        $this->response = $this->response
            ->withStatus($status ?? StatusCode::OK)
            ->withCharset(strtolower(config('app.charset') ?? 'utf-8'));

        $this->formatResponse($data);

        return $this->response;
    }

    /**
     * Réponse d'erreur générique
     *
     * @param string|null     $message Message d'erreur
     * @param int|null        $status  Code de statut HTTP
     * @param int|string|null $code    Code d'erreur personnalisé
     * @param array           $errors  Liste détaillée des erreurs
     *
     * @return ResponseInterface Réponse d'erreur formatée
     */
    protected function respondFail(
        ?string $message = "Une erreur s'est produite",
        ?int $status = StatusCode::INTERNAL_ERROR,
        int|string|null $code = null,
        array $errors = []
    ): ResponseInterface {
        $message = $message ?: "Une erreur s'est produite";
        $code    = ! in_array($code, [0, '', '0', null], true) ? $code : $status;

        $response = [
            $this->restConfig['field']['message'] => $message,
        ];

        if (! empty($this->restConfig['field']['status'])) {
            $response[$this->restConfig['field']['status']] = false;
        }
        if (! empty($this->restConfig['field']['code'])) {
            $response[$this->restConfig['field']['code']] = $code;
        }
        if (! empty($errors)) {
            $response[$this->restConfig['field']['errors']] = $errors;
        }

        // Mode non strict : toujours retourner 200 avec le statut dans le body
        if (! $this->restConfig['strict']) {
            $status = StatusCode::OK;
        }

        return $this->respond($response, $status);
    }

    /**
     * Réponse de succès générique
     *
     * @param string|null $message Message de succès
     * @param mixed       $result  Données résultantes
     * @param int|null    $status  Code de statut HTTP
     *
     * @return ResponseInterface Réponse de succès formatée
     */
    protected function respondSuccess(
        ?string $message = 'Resultat',
        $result = null,
        ?int $status = StatusCode::OK
    ): ResponseInterface {
        $message = $message ?: 'Resultat';
        $status ??= StatusCode::OK;

        $response = [
            $this->restConfig['field']['message'] => $message,
        ];

        if (! empty($this->restConfig['field']['status'])) {
            $response[$this->restConfig['field']['status']] = true;
        }

        $response[$this->restConfig['field']['result']] = $this->formatResult($result);

        return $this->respond($response, $status);
    }

    /**
     * Formate les données de résultat
     *
     * Applique formatEntity() à chaque élément si c'est un tableau
     *
     * @param mixed $result Données à formater
     *
     * @return mixed Données formatées
     */
    protected function formatResult($result)
    {
        if (is_array($result)) {
            return array_map([$this, 'formatEntity'], $result);
        }

        return $this->formatEntity($result);
    }

    /**
     * Formate une entité individuelle
     *
     * Tente d'appeler toArray() ou jsonSerialize() sur les objets
     *
     * @param mixed $element Élément à formater
     *
     * @return mixed Élément formaté
     */
    protected function formatEntity($element)
    {
        if (is_object($element)) {
            if (method_exists($element, 'toArray')) {
                return $element->toArray();
            }
            if ($element instanceof JsonSerializable) {
                return $element->jsonSerialize();
            }
        }

        return $element;
    }

    /**
     * Formate la réponse finale selon le format configuré
     *
     * @param mixed $data Données à formater
     */
    protected function formatResponse($data): void
    {
        $format = strtolower($this->restConfig['format']);
        $mime   = $this->mimes[$format] ?? null;

        if (! $mime && in_array($format, $this->mimes, true)) {
            $mime = $format;
        }

        if ($mime) {
            $output = Formatter::type($mime)->format($data);

            // Gestion JSONP
            if ($mime === $this->mimes['json']) {
                $callback = $this->request->getQuery('callback');
                if (! empty($callback) && str_starts_with(trim($output), $callback . '(')) {
                    $mime   = $this->mimes['jsonp'];
                    $output = $callback . '(' . trim($output) . ');';
                }
            }

            $this->response = $this->response->withType(
                $mime === $this->mimes['array'] ? $this->mimes['json'] : $mime
            );

            if ($mime === $this->mimes['array']) {
                $output = Formatter::type($this->mimes['json'])->format($output);
            }
        } else {
            // Format non supporté
            $output = is_array($data) || is_object($data)
                ? Formatter::type($this->mimes['json'])->format($data)
                : (string) $data;
        }

        $this->response = $this->response->withStringBody($output);
    }

    /**
     * --------------------------------------------------------------------------
     * INTERNATIONALISATION
     * --------------------------------------------------------------------------
     */

    /**
     * Traduit une chaîne de caractères
     *
     * @param string $line Clé de traduction
     * @param array  $args Arguments à injecter dans la traduction
     *
     * @return string Chaîne traduite
     */
    protected function lang(string $line, array $args = []): string
    {
        return lang($line, $args, $this->locale);
    }

    /**
     * Traduit une chaîne spécifique à l'API REST
     *
     * @param string $line Clé de traduction (préfixée par 'Rest.')
     * @param array  $args Arguments à injecter
     *
     * @return string Chaîne traduite
     */
    protected function _translate(string $line, array $args = []): string
    {
        return $this->lang('Rest.' . $line, $args);
    }

    /**
     * --------------------------------------------------------------------------
     * HOOKS (peuvent être surchargés)
     * --------------------------------------------------------------------------
     */

    /**
     * Hook exécuté avant l'appel de la méthode du contrôleur
     *
     * @param string $method Nom de la méthode qui sera exécutée
     * @param array  $params Paramètres qui seront passés à la méthode
     *
     * @return ResponseInterface|null Si une réponse est retournée, elle interrompt l'exécution
     */
    protected function before(string $method, array $params): ?ResponseInterface
    {
        return null;
    }

    /**
     * Hook exécuté après l'appel de la méthode du contrôleur
     *
     * @param string            $method   Nom de la méthode qui a été exécutée
     * @param array             $params   Paramètres qui ont été passés à la méthode
     * @param ResponseInterface $response Réponse générée par la méthode
     */
    protected function after(string $method, array $params, ResponseInterface $response): void
    {
        // Par défaut, ne fait rien
    }
}
