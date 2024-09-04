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

use BlitzPHP\Annotations\AnnotationReader;
use BlitzPHP\Annotations\Http\AjaxOnlyAnnotation;
use BlitzPHP\Annotations\Http\RequestMappingAnnotation;
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Exceptions\ValidationException;
use BlitzPHP\Formatter\Formatter;
use BlitzPHP\Traits\Http\ApiResponseTrait;
use BlitzPHP\Utilities\Jwt;
use Exception;
use mindplay\annotations\IAnnotation;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Throwable;

/**
 * Le contrôleur de base pour les API REST
 */
class RestController extends BaseController
{
    use ApiResponseTrait;

    /**
     * Configurations
     *
     * @var stdClass
     */
    protected $config;

    /**
     * Langue à utiliser
     *
     * @var string
     */
    private $locale;

    /**
     * Type mime associé à chaque format de sortie
     *
     * Répertoriez tous les formats pris en charge, le première sera le format par défaut.
     */
    protected $mimes = [
        'json' => 'application/json',
        'csv'  => 'application/csv',
        // 'html'       => 'text/html',
        'jsonp'      => 'application/javascript',
        'php'        => 'text/plain',
        'serialized' => 'application/vnd.php.serialized',
        'xml'        => 'application/xml',

        'array' => 'php/array',
    ];

    /**
     * @var array|object Payload provenant du token jwt
     */
    protected $payload;

    public function __construct()
    {
        $this->config = (object) config('rest');

        $locale       = $this->config->language ?? null;
        $this->locale = ! empty($locale) ? $locale : $this->request->getLocale();
    }

    public function _remap(string $method, array $params = [])
    {
        $class = static::class;

        // Bien sûr qu'il existe, mais peuvent-ils en faire quelque chose ?
        if (! method_exists($class, $method)) {
            return $this->respondNotImplemented($this->_translate('notImplemented', [$class, $method]));
        }

        // Appel de la méthode du contrôleur et passage des arguments
        try {
            $instance = Services::container()->get($class);
            $instance->initialize($this->request, $this->response, $this->logger);

            $instance = $this->_execAnnotations($instance, AnnotationReader::fromClass($instance));
            $instance = $this->_execAnnotations($instance, AnnotationReader::fromMethod($instance, $method));

            $checkProcess = $this->checkProcess();
            if ($checkProcess instanceof ResponseInterface) {
                return $checkProcess;
            }

            $instance->payload = $this->payload;

            $response = Services::container()->call([$instance, $method], $params);

            if ($response instanceof ResponseInterface) {
                return $response;
            }

            return $this->respondOk($response);
        } catch (Throwable $ex) {
            return $this->manageException($ex);
        }
    }

    /**
     * Gestionnaire des exceptions
     *
     * Ceci permet aux classes filles de specifier comment elles doivent gerer les exceptions lors de la methode remap
     *
     * @return ResponseInterface
     */
    protected function manageException(Throwable $ex)
    {
        if ($ex instanceof ValidationException) {
            $message = 'Validation failed';
            $errors  = $ex->getErrors()->all();

            return $this->respondBadRequest($message, $ex->getCode(), $errors);
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
     * Fournit une méthode simple et unique pour renvoyer une réponse d'API, formatée
     * pour correspondre au format demandé, avec le type de contenu et le code d'état appropriés.
     *
     * @param mixed    $data   Les donnees a renvoyer
     * @param int|null $status Le statut de la reponse
     */
    final protected function respond($data, ?int $status = StatusCode::OK)
    {
        // Si les données sont NULL et qu'aucun code d'état HTTP n'est fourni, affichage, erreur et sortie
        if ($data === null && $status === null) {
            $status = StatusCode::NOT_FOUND;
        }

        $this->response = $this->response->withStatus($status)->withCharset(strtolower(config('app.charset') ?? 'utf-8'));

        $this->_parseResponse($data);

        return $this->response;
    }

    /**
     * Utilisé pour les échecs génériques pour lesquels aucune méthode personnalisée n'existe.
     *
     * @param string          $message Le message décrivant l'erreur
     * @param int|string|null $code    Code d'erreur personnalisé, spécifique à l'API
     * @param array           $errors  La liste des erreurs rencontrées
     *
     * @return ResponseInterface
     */
    final protected function respondFail(?string $message = "Une erreur s'est produite", ?int $status = StatusCode::INTERNAL_ERROR, null|int|string $code = null, array $errors = [])
    {
        $message = $message ?: "Une erreur s'est produite";
        $code    = ($code !== 0 && $code !== '' && $code !== '0') ? $code : $status;

        $response = [
            $this->config->field['message'] ?? 'message' => $message,
        ];
        if (! empty($this->config->field['status'])) {
            $response[$this->config->field['status']] = false;
        }
        if (! empty($this->config->field['code'])) {
            $response[$this->config->field['code']] = $code;
        }
        if ($errors !== []) {
            $response[$this->config->field['errors'] ?? 'errors'] = $errors;
        }

        if ($this->config->strict !== true) {
            $status = StatusCode::OK;
        }

        return $this->respond($response, $status);
    }

    /**
     * Utilisé pour les succès génériques pour lesquels aucune méthode personnalisée n'existe.
     *
     * @param mixed|null $result Les données renvoyées par l'API
     *
     * @return ResponseInterface
     */
    final protected function respondSuccess(?string $message = 'Resultat', $result = null, ?int $status = StatusCode::OK)
    {
        $message = $message ?: 'Resultat';
        $status  = $status !== null && $status !== 0 ? $status : StatusCode::OK;

        $response = [
            $this->config->field['message'] ?? 'message' => $message,
        ];
        if (! empty($this->config->field['status'])) {
            $response[$this->config->field['status']] = true;
        }
        if (is_array($result)) {
            $result = array_map(fn ($element) => $this->formatEntity($element), $result);
        }

        $response[$this->config->field['result'] ?? 'result'] = $this->formatEntity($result);

        return $this->respond($response, $status);
    }

    /**
     * Formatte les données à renvoyer lorsqu'il s'agit des objets de la classe Entity
     *
     * @param mixed $element
     *
     * @return mixed
     */
    protected function formatEntity($element)
    {
        /*
        if ($element instanceof Entity) {
            if (method_exists($element, 'format')) {
                return Services::injector()->call([$element, 'format']);
            }

            return call_user_func([$element, 'toArray']);
        }
        */
        return $element;
    }

    /**
     * Genere un token d'authentification
     */
    protected function generateToken(array $data = [], array $config = []): string
    {
        $config = array_merge(['base_url' => base_url()], $this->config->jwt ?? [], $config);

        return Jwt::encode($data, $config);
    }

    /**
     * Decode un token d'autorisation
     *
     * @return mixed
     */
    protected function decodeToken(string $token, string $authType = 'bearer', array $config = [])
    {
        $config = array_merge(['base_url' => base_url()], $this->config->jwt ?? [], $config);

        if ('bearer' === $authType) {
            return Jwt::decode($token, $config);
        }

        return null;
    }

    /**
     * Recupere le token d'acces a partier des headers
     */
    protected function getBearerToken(): ?string
    {
        return Jwt::getToken();
    }

    /**
     * Recupere le header "Authorization"
     */
    protected function getAuthorizationHeader(): ?string
    {
        return Jwt::getAuthorization();
    }

    /**
     * Une méthode pratique pour traduire une chaîne ou un tableau d'entrées et
     * formater le résultat avec le MessageFormatter de l'extension intl.
     */
    protected function lang(string $line, ?array $args = null): string
    {
        return lang($line, $args, $this->locale);
    }

    /**
     * @internal Ne pas censé être utilisé par le developpeur
     */
    protected function _translate(string $line, ?array $args = null): string
    {
        return $this->lang('Rest.' . $line, $args);
    }

    /**
     * Specifie que seules les requetes ajax sont acceptees
     */
    final protected function ajaxOnly(): self
    {
        $this->config->ajax_only = true;

        return $this;
    }

    /**
     * Definit les methodes authorisees par le web service
     */
    final protected function allowedMethods(string ...$methods): self
    {
        if ($methods !== []) {
            $this->config->allowed_methods = array_map(static fn ($str) => strtoupper($str), $methods);
        }

        return $this;
    }

    /**
     * Definit le format de donnees a renvoyer au client
     */
    final protected function returnFormat(string $format): self
    {
        $this->config->format = $format;

        return $this;
    }

    /**
     * N'autorise que les acces pas https
     */
    final protected function requireHttps(): self
    {
        $this->config->force_https = true;

        return $this;
    }

    /**
     * auth
     *
     * @param false|string $type
     */
    final protected function auth($type): self
    {
        $this->config->auth = $type;

        return $this;
    }

    /**
     * Definit la liste des adresses IP a bannir
     * Si le premier argument vaut "false", la suite ne sert plus a rien
     */
    final protected function ipBlacklist(...$params): self
    {
        $params = func_get_args();
        $enable = array_shift($params);

        if (false === $enable) {
            $params = [];
        } else {
            array_unshift($params, $enable);
            $params = array_merge($this->config->ip_blacklist ?? [], $params);
        }

        $this->config->ip_blacklist = $params;

        return $this;
    }

    /**
     * Definit la liste des adresses IP qui sont autorisees a acceder a la ressources
     * Si le premier argument vaut "false", la suite ne sert plus a rien
     */
    final protected function ipWhitelist(...$params): self
    {
        $params = func_get_args();
        $enable = array_shift($params);

        if (false === $enable) {
            $params = [];
        } else {
            array_unshift($params, $enable);
            $params = array_merge($this->config->ip_whitelist ?? [], $params);
        }

        $this->config->ip_whitelist = $params;

        return $this;
    }

    /**
     * Formatte les donnees a envoyer au bon format
     *
     * @param mixed $data Les donnees a envoyer
     */
    private function _parseResponse($data)
    {
        $format = strtolower($this->config->format);
        $mime   = null;

        if (array_key_exists($format, $this->mimes)) {
            $mime = $this->mimes[$format];
        } elseif (in_array($format, $this->mimes, true)) {
            $mime = $format;
        }

        // Si la méthode de format existe, appelle et renvoie la sortie dans ce format
        if (! empty($mime)) {
            $output = Formatter::type($mime)->format($data);

            // Définit l'en-tête du format
            // Ensuite, vérifiez si le client a demandé un rappel, et si la sortie contient ce rappel :
            $callback = $this->request->getQuery('callback');
            if (! empty($callback) && $mime === $this->mimes['json'] && preg_match('/^' . $callback . '/', $output)) {
                $this->response = $this->response->withType($this->mimes['jsonp']);
            } else {
                $this->response = $this->response->withType($mime === $this->mimes['array'] ? $this->mimes['json'] : $mime);
            }

            // Un tableau doit être analysé comme une chaîne, afin de ne pas provoquer d'erreur de tableau en chaîne
            // Json est la forme la plus appropriée pour un tel type de données
            if ($mime === $this->mimes['array']) {
                $output = Formatter::type($this->mimes['json'])->format($output);
            }
        } else {
            // S'il s'agit d'un tableau ou d'un objet, analysez-le comme un json, de manière à être une 'chaîne'
            if (is_array($data) || is_object($data)) {
                $data = Formatter::type($this->mimes['json'])->format($data);
            }
            // Le format n'est pas pris en charge, sortez les données brutes sous forme de chaîne
            $output = $data;
        }

        $this->response = $this->response->withStringBody($output);
    }

    /**
     * Execute les annotations definies dans le contrôleur
     *
     * @param IAnnotation[] $annotations Liste des annotations d'un contrôleur/méthode
     */
    protected function _execAnnotations(self $instance, array $annotations): self
    {
        foreach ($annotations as $annotation) {
            switch (get_type_name($annotation)) {
                case RequestMappingAnnotation::class:
                    $this->allowedMethods(...(array) $annotation->method);
                    break;

                case AjaxOnlyAnnotation::class:
                    $this->ajaxOnly();
                    break;

                default:
                    break;
            }
        }

        return $instance;
    }

    /**
     * Verifie si les informations fournis par le client du ws sont conforme aux attentes du developpeur
     *
     * @throws Exception
     */
    private function checkProcess(): bool|ResponseInterface
    {
        // Verifie si la requete est en ajax
        if (! $this->request->is('ajax') && $this->config->ajax_only) {
            return $this->respondNotAcceptable($this->_translate('ajaxOnly'));
        }

        // Verifie si la requete est en https
        if (! $this->request->is('https') && $this->config->force_https) {
            return $this->respondForbidden($this->_translate('unsupported'));
        }

        // Verifie si la methode utilisee pour la requete est autorisee
        if (! in_array(strtoupper($this->request->getMethod()), $this->config->allowed_methods, true)) {
            return $this->respondNotAcceptable($this->_translate('unknownMethod'));
        }

        // Verifie que l'ip qui emet la requete n'est pas dans la blacklist
        if (! empty($this->config->ip_blacklis)) {
            $this->config->ip_blacklist = implode(',', $this->config->ip_blacklist);

            // Correspond à une adresse IP dans une liste noire, par ex. 127.0.0.0, 0.0.0.0
            $pattern = sprintf('/(?:,\s*|^)\Q%s\E(?=,\s*|$)/m', $this->request->clientIp());

            // Renvoie 1, 0 ou FALSE (en cas d'erreur uniquement). Donc convertir implicitement 1 en TRUE
            if (preg_match($pattern, $this->config->ip_blacklist)) {
                return $this->respondUnauthorized($this->_translate('ipDenied'));
            }
        }

        // Verifie que l'ip qui emet la requete est dans la whitelist
        if (! empty($this->config->ip_whitelist)) {
            $whitelist   = $this->config->ip_whitelist;
            $whitelist[] = '127.0.0.1';
            $whitelist[] = '0.0.0.0';

            // coupez les espaces de début et de fin des ip
            $whitelist = array_map('trim', $whitelist);

            if (! in_array($this->request->clientIp(), $whitelist, true)) {
                return $this->respondUnauthorized($this->_translate('ipUnauthorized'));
            }
        }

        // Verifie l'authentification du client
        if (false !== $this->config->auth && ! $this->request->is('options') && 'bearer' === strtolower($this->config->auth)) {
            $token = $this->getBearerToken();
            if ($token === null || $token === '' || $token === '0') {
                return $this->respondInvalidToken($this->_translate('tokenNotFound'));
            }
            $payload = $this->decodeToken($token, 'bearer');
            if ($payload instanceof Throwable) {
                return $this->respondInvalidToken($payload->getMessage());
            }
            $this->payload = $payload;
        }

        return true;
    }
}
