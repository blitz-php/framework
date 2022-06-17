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
use BlitzPHP\Formatter\Formatter;
use BlitzPHP\Http\Response;
use BlitzPHP\Loader\Services;
use BlitzPHP\Traits\ApiResponseTrait;
use stdClass;

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

    public function __construct()
    {
        $this->config = (object) config('rest');

        $locale       = $this->config->language ?? null;
        $locale       = ! empty($locale) ? $locale : config('app.language');
        $this->locale = ! empty($locale) ? $locale : 'en';
    }

    /**
     * Fournit une méthode simple et unique pour renvoyer une réponse d'API, formatée
     * pour correspondre au format demandé, avec le type de contenu et le code d'état appropriés.
     *
     * @param mixed    $data   Les donnees a renvoyer
     * @param int|null $status Le statut de la reponse
     */
    final protected function respond($data, ?int $status = StatusCode::OK): Response
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
     * @param int             $status  Code d'état HTTP
     * @param int|string|null $code    Code d'erreur personnalisé, spécifique à l'API
     * @param array           $errors  La liste des erreurs rencontrées
     */
    final protected function respondFail(?string $message = "Une erreur s'est produite", int $status = StatusCode::INTERNAL_ERROR, int|string|null $code = null, array $errors = []): Response
    {
        $message = $message ?: "Une erreur s'est produite";
        $code    = ! empty($code) ? $code : $status;

        $response = [
            $this->config->field['message'] ?? 'message' => $message,
        ];
        if (! empty($this->config->field['status'])) {
            $response[$this->config->field['status']] = false;
        }
        if (! empty($this->config->field['code'])) {
            $response[$this->config->field['code']] = $code;
        }
        if (! empty($errors)) {
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
     * @param mixed|null $result
     */
    final protected function respondSuccess(?string $message = 'Resultat', $result = null, ?int $status = StatusCode::OK): Response
    {
        $message = $message ?: 'Resultat';
        $status  = ! empty($status) ? $status : StatusCode::OK;

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
        if ($element instanceof Entity) {
            if (method_exists($element, 'format')) {
                return Services::injector()->call([$element, 'format']);
            }

            return call_user_func([$element, 'toArray']);
        }

        return $element;
    }

    /**
     * Formatte les donnees a envoyer au bon format
     *
     * @param mixed $data Les donnees a envoyer
     */
    private function _parseResponse($data)
    {
        $format = strtolower($this->config['return_format']);
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
}
