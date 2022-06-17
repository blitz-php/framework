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
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondBadRequest(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['invalid_request'] ?? StatusCode::BAD_REQUEST, $code, $errors);
    }

    /**
     * À utiliser lorsque vous essayez de créer une nouvelle ressource et qu'elle existe déjà.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondConflict(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['conflict'] ?? StatusCode::CONFLICT, $code, $errors);
    }

    /**
     * Utilisé après la création réussie d'une nouvelle ressource.
     *
     * @param mixed|null $result
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondCreated(string $message, $result = null)
    {
        return $this->respondSuccess($message, $result, $this->codes['created'] ?? StatusCode::CREATED);
    }

    /**
     * Utilisé après qu'une ressource a été supprimée avec succès.
     *
     * @param mixed|null $result
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function respondDeleted(string $message, $result = null)
    {
        return $this->respondSuccess($message, $result, $this->codes['deleted'] ?? StatusCode::OK);
    }

    /**
     * Utilisé lorsque l'accès est toujours refusé à cette ressource
     * et qu'aucune nouvelle tentative n'aidera.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondForbidden(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['forbidden'] ?? StatusCode::FORBIDDEN, $code, $errors);
    }

    /**
     * À utiliser lorsqu'une ressource a été précédemment supprimée. Ceci est différent de Not Found,
     * car ici, nous savons que les données existaient auparavant, mais sont maintenant disparues,
     * où Not Found signifie que nous ne pouvons tout simplement pas trouver d'informations à leur sujet.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondGone(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['resource_gone'] ?? StatusCode::GONE, $code, $errors);
    }

    /**
     * Utilisé lorsqu'il y a une erreur de serveur.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondInternalError(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['server_error'] ?? StatusCode::INTERNAL_ERROR, $code, $errors);
    }

    /**
     * Reponse de type invalid token
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondInvalidToken(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['invalid_token'] ?? StatusCode::INVALID_TOKEN, $code, $errors);
    }

    /**
     * Reponse de type method not allowed
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondMethodNotAllowed(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['not_allowed'] ?? StatusCode::METHOD_NOT_ALLOWED, $code, $errors);
    }

    /**
     * Utilisé après qu'une commande a été exécutée avec succès
     * mais qu'il n'y a pas de réponse significative à renvoyer au client.
     *
     * @return \Psr\Http\Message\ResponseInterface|void
     */
    final protected function respondNoContent(string $message)
    {
        return $this->respondSuccess($message, null, $this->codes['no_content'] ?? StatusCode::NO_CONTENT);
    }

    /**
     * Reponse de type not acceptable
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondNotAcceptable(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['not_acceptable'] ?? StatusCode::NOT_ACCEPTABLE, $code, $errors);
    }

    /**
     * Utilisé lorsqu'une ressource spécifiée est introuvable.
     *
     * @return \Psr\Http\Message\ResponseInterface|void
     */
    final protected function respondNotFound(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['resource_not_found'] ?? StatusCode::NOT_FOUND, $code, $errors);
    }

    /**
     * Reponse de type not implemented
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondNotImplemented(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['not_implemented'] ?? StatusCode::NOT_IMPLEMENTED, $code, $errors);
    }

    /**
     * Reponse de type ok
     *
     * @param mixed|null $result
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondOk(string $message, $result = null)
    {
        return $this->respondSuccess($message, $result, $this->codes['ok'] ?? StatusCode::OK);
    }

    /**
     * Utilisé lorsque l'utilisateur a fait trop de demandes pour la ressource récemment.
     *
     * @return \Psr\Http\Message\ResponseInterface|void
     */
    final protected function respondTooManyRequests(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['too_many_requests'] ?? StatusCode::TOO_MANY_REQUESTS, $code, $errors);
    }

    /**
     * Utilisé lorsque le client n'a pas envoyé d'informations d'autorisation
     * ou avait de mauvaises informations d'identification d'autorisation.
     * L'utilisateur est encouragé à réessayer avec les informations appropriées.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondUnauthorized(string $message, int|string|null $code = null, array $errors = [])
    {
        return $this->respondFail($message, $this->codes['unauthorized'] ?? StatusCode::UNAUTHORIZED, $code, $errors);
    }

    /**
     * Utilisé après qu'une ressource a été mise à jour avec succès.
     *
     * @param mixed|null $result
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    final protected function respondUpdated(string $message, $result = null)
    {
        return $this->respondSuccess($message, $result, $this->codes['updated'] ?? StatusCode::OK);
    }
}
