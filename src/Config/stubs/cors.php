<?php

/**
 * ------------------------------------------------- -------------------------
 * Configuration du CORS (Cross-Origin Resource Sharing)
 * ------------------------------------------------- -------------------------
 *
 * Ici, vous pouvez configurer vos paramètres pour le partage des ressources inter-origines ou « CORS ».
 * Cela détermine quelles opérations cross-originales peuvent être exécutées dans les navigateurs web.
 * Vous êtes libre d'ajuster ces paramètres selon vos besoins.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS]
 */

return [
    /**
     * Indique les en-têtes HTTP autorisés.
     *
     * L'en-tête de réponse Access-Control-Allow-Headers est utilisé en réponse à une demande
     * de contrôle en amont qui inclut les Access-Control-Request-Headers pour indiquer
     * quels en-têtes HTTP peuvent être utilisés lors de la requete proprement dite.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers
     *
     * @var list<string>
     */
    'allowed_headers' => ['*'],

    /**
     * Defini les méthodes HTTP autorisées.
     *
     * L'en-tête de réponse Access-Control-Allow-Methods spécifie une ou plusieurs méthodes autorisées lors de l'accès à une ressource en réponse à une demande de contrôle en amont.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Methods
     *
     * @var list<string>
     *
     * E.g.:
     *   - ['GET', 'POST', 'PUT', 'DELETE']
     */
    'allowed_methods' => ['*'],

    /**
     * Origine des requêtes autorisées
     *
     * Indique quelles origines sont autorisées à effectuer des requetes.
     * Les motifs sont également acceptés, par exemple *.foo.com
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
     *
     * @var list<string>
     *
     * Ex.:
     *   - ['http://localhost:8080']
     *   - ['https://www.example.com']
     */
    'allowed_origins' => ['*'],

    /**
     * Modèles d'origines autorisés
     *
     * Les motifs qui peuvent être utilisés avec `preg_match` pour correspondre à l'origine.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
     *
     * NOTE : Un motif spécifié ici fait partie d'une expression régulière. Il sera en fait `#\A<pattern>\z#`.
     *
     * @var list<string>
     *
     * Ex.:
     *   - ['https://\w+\.example\.com']
     */
    'allowed_origins_patterns' => [],

    /**
     * Défini les en-têtes qui sont autorisés à être exposés au serveur web.
     *
     * L'en-tête de réponse Access-Control-Expose-Headers permet à un serveur
     * d'indiquer quels en-têtes de réponse doivent être mis à la disposition
     * des scripts s'exécutant dans le navigateur, en réponse à une requête inter-origine.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Expose-Headers
     *
     * @var list<string>
     */
    'exposed_headers' => [],

    /**
     * Définir le nombre de secondes pendant lesquelles les résultats d'une demande de contrôle en amont peuvent être mis en cache.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Max-Age
     *
     * @var int
     */
    'max_age' => 7200,

    /**
     * Indique aux navigateurs si le serveur autorise les requêtes HTTP inter-origines
     * à inclure des informations d'identification.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Credentials
     *
     * @var bool
     */
    'supports_credentials' => false,
];
