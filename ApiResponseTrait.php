<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Traits;

use BlitzPHP\Contracts\Http\StatusCode;

/**
 * Fournit des méthodes courantes, plus lisibles,
 * pour fournir des réponses HTTP cohérentes dans diverses situations courantes
 * lorsque vous travaillez en tant qu'API.
 */
trait ApiResponseTrait
{
    /**
     * Permet aux classes enfants de remplacer
     * code d'état utilisé dans leur API.
     *
     * @var array<string, int>
     */
    protected $codes = [
        'created'                   => StatusCode::CREATED,
        'deleted'                   => StatusCode::OK,
        'updated'                   => StatusCode::OK,
        'no_content'                => StatusCode::NO_CONTENT,
        'invalid_request'           => StatusCode::BAD_REQUEST,
        'unsupported_response_type' => StatusCode::BAD_REQUEST,
        'invalid_scope'             => StatusCode::BAD_REQUEST,
        'temporarily_unavailable'   => StatusCode::BAD_REQUEST,
        'invalid_grant'             => StatusCode::BAD_REQUEST,
        'invalid_credentials'       => StatusCode::BAD_REQUEST,
        'invalid_refresh'           => StatusCode::BAD_REQUEST,
        'no_data'                   => StatusCode::BAD_REQUEST,
        'invalid_data'              => StatusCode::BAD_REQUEST,
        'access_denied'             => StatusCode::UNAUTHORIZED,
        'unauthorized'              => StatusCode::UNAUTHORIZED,
        'invalid_client'            => StatusCode::UNAUTHORIZED,
        'forbidden'                 => StatusCode::FORBIDDEN,
        'resource_not_found'        => StatusCode::NOT_FOUND,
        'not_acceptable'            => StatusCode::NOT_ACCEPTABLE,
        'resource_exists'           => StatusCode::CONFLICT,
        'conflict'                  => StatusCode::CONFLICT,
        'resource_gone'             => StatusCode::GONE,
        'payload_too_large'         => StatusCode::PAYLOAD_TOO_LARGE,
        'unsupported_media_type'    => StatusCode::UNSUPPORTED_MEDIA_TYPE,
        'too_many_requests'         => StatusCode::TOO_MANY_REQUESTS,
        'server_error'              => StatusCode::INTERNAL_ERROR,
        'unsupported_grant_type'    => StatusCode::NOT_IMPLEMENTED,
        'not_implemented'           => StatusCode::NOT_IMPLEMENTED,
    ];

    /**
     * Fournit une méthode simple et unique pour renvoyer une réponse d'API, formatée
     * pour correspondre au format demandé, avec le type de contenu et le code d'état appropriés.
     *
     * @param mixed    $data   Les donnees a renvoyer
     * @param int|null $status Le statut de la reponse
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract protected function respond($data, ?int $status = StatusCode::OK);

    /**
     * Utilisé pour les échecs génériques pour lesquels aucune méthode personnalisée n'existe.
     *
     * @param string          $message Le message décrivant l'erreur
     * @param int             $status  Code d'état HTTP
     * @param int|string|null $code    Code d'erreur personnalisé, spécifique à l'API
     * @param array           $errors  La liste des erreurs rencontrées
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract protected function respondFail(?string $message = "Une erreur s'est produite", ?int $status = StatusCode::INTERNAL_ERROR, int|string|null $code = null, array $errors = []);

    /**
     * Utilisé pour les succès génériques pour lesquels aucune méthode personnalisée n'existe.
     *
     * @param mixed|null $result
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract protected function respondSuccess(?string $message = 'Resultat', $result = null, ?int $status = StatusCode::OK);

    /**
     * Reponse de type bad request
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondBadRequest($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Bad Request', $this->codes['invalid_request'] ?? StatusCode::BAD_REQUEST, $code, $errors);
    }

    /**
     * À utiliser lorsque vous essayez de créer une nouvelle ressource et qu'elle existe déjà.
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondConflict($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Conflict', $this->codes['conflict'] ?? StatusCode::CONFLICT, $code, $errors);
    }

    /**
     * Utilisé après la création réussie d'une nouvelle ressource.
     *
     * @param mixed|null $result
     * @param mixed      $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondCreated($message, $result = null)
    {
        ['message' => $message, 'data' => $result] = $this->_parseParams($message, null, $result);

        return $this->respondSuccess($message ?? 'Created', $result, $this->codes['created'] ?? StatusCode::CREATED);
    }

    /**
     * Utilisé après qu'une ressource a été supprimée avec succès.
     *
     * @param mixed|null $result
     * @param mixed      $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function respondDeleted($message, $result = null)
    {
        ['message' => $message, 'data' => $result] = $this->_parseParams($message, null, $result);

        return $this->respondSuccess($message ?? 'Deleted', $result, $this->codes['deleted'] ?? StatusCode::OK);
    }

    /**
     * Utilisé lorsque l'accès est toujours refusé à cette ressource
     * et qu'aucune nouvelle tentative n'aidera.
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondForbidden($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Forbidden', $this->codes['forbidden'] ?? StatusCode::FORBIDDEN, $code, $errors);
    }

    /**
     * À utiliser lorsqu'une ressource a été précédemment supprimée. Ceci est différent de Not Found,
     * car ici, nous savons que les données existaient auparavant, mais sont maintenant disparues,
     * où Not Found signifie que nous ne pouvons tout simplement pas trouver d'informations à leur sujet.
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondGone($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Gone', $this->codes['resource_gone'] ?? StatusCode::GONE, $code, $errors);
    }

    /**
     * Utilisé lorsqu'il y a une erreur de serveur.
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondInternalError($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Internal Server Error', $this->codes['server_error'] ?? StatusCode::INTERNAL_ERROR, $code, $errors);
    }

    /**
     * Reponse de type invalid token
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondInvalidToken($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Invalid Token', $this->codes['invalid_token'] ?? StatusCode::INVALID_TOKEN, $code, $errors);
    }

    /**
     * Reponse de type method not allowed
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondMethodNotAllowed($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Method Not Allowed', $this->codes['not_allowed'] ?? StatusCode::METHOD_NOT_ALLOWED, $code, $errors);
    }

    /**
     * Utilisé après qu'une commande a été exécutée avec succès
     * mais qu'il n'y a pas de réponse significative à renvoyer au client.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondNoContent(string $message = 'No Content')
    {
        return $this->respondSuccess($message, null, $this->codes['no_content'] ?? StatusCode::NO_CONTENT);
    }

    /**
     * Reponse de type not acceptable
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondNotAcceptable($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Not Acceptable', $this->codes['not_acceptable'] ?? StatusCode::NOT_ACCEPTABLE, $code, $errors);
    }

    /**
     * Utilisé lorsqu'une ressource spécifiée est introuvable.
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondNotFound($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Not Found', $this->codes['resource_not_found'] ?? StatusCode::NOT_FOUND, $code, $errors);
    }

    /**
     * Reponse de type not implemented
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondNotImplemented($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Not Implemented', $this->codes['not_implemented'] ?? StatusCode::NOT_IMPLEMENTED, $code, $errors);
    }

    /**
     * Reponse de type ok
     *
     * @param mixed|null $result
     * @param mixed      $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondOk($message, $result = null)
    {
        ['message' => $message, 'data' => $result] = $this->_parseParams($message, null, $result);

        return $this->respondSuccess($message ?? 'Ok', $result, $this->codes['ok'] ?? StatusCode::OK);
    }

    /**
     * Utilisé lorsque l'utilisateur a fait trop de demandes pour la ressource récemment.
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface|void
     */
    final protected function respondTooManyRequests($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Too Many Requests', $this->codes['too_many_requests'] ?? StatusCode::TOO_MANY_REQUESTS, $code, $errors);
    }

    /**
     * Utilisé lorsque le client n'a pas envoyé d'informations d'autorisation
     * ou avait de mauvaises informations d'identification d'autorisation.
     * L'utilisateur est encouragé à réessayer avec les informations appropriées.
     *
     * @param mixed $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondUnauthorized($message, int|string|null|array $code = null, array $errors = [])
    {
        if (is_array($code)) {
            $errors = $code;
            $code   = null;
        }

        ['message' => $message, 'data' => $errors, 'code' => $code] = $this->_parseParams($message, $code, $errors);

        return $this->respondFail($message ?? 'Unauthorized', $this->codes['unauthorized'] ?? StatusCode::UNAUTHORIZED, $code, $errors);
    }

    /**
     * Utilisé après qu'une ressource a été mise à jour avec succès.
     *
     * @param mixed|null $result
     * @param mixed      $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondUpdated($message, $result = null)
    {
        ['message' => $message, 'data' => $result] = $this->_parseParams($message, null, $result);

        return $this->respondSuccess($message ?? 'Updated', $result, $this->codes['updated'] ?? StatusCode::OK);
    }

    /**
     * Parse les parametres a renvoyer comme corps de la reponse json
     *
     * @param array|object|string $message
     * @param array|int|string    $code
     * @param array|mixed         $data
     *
     * @internal
     */
    private function _parseParams($message, $code = null, $data = []): array
    {
        if (is_array($message) || is_object($message)) {
            if (empty($data)) {
                $data = $message;
            }
            $message = null;
        }
        if (is_array($code)) {
            if (empty($data)) {
                $data = $code;
            }
            $code = null;
        }

        return compact('message', 'code', 'data');
    }
}
