<?php

/**
 * ------------------------------------------------- -------------------------
 * Configuration des API REST
 * ------------------------------------------------- -------------------------
 *
 * Ce fichier vous permet de définir le fonctionnement de vos contrôleurs REST
 */

return [
    /**
     * Locale par défaut pour les messages d'API
     *
     * @var string
     */
    'locale' => config('app.locale', 'en'),

    /**
     * Définir pour forcer l'utilisation de HTTPS pour les appels d'API REST
     *
     * Lorsque activé, toutes les requêtes non-HTTPS seront rejetées avec une erreur 403
     *
     * @var bool
     */
    'force_https' => false,

    /**
     * Format par défaut des réponses de l'API
     *
     * Options disponibles :
     * - 'array'    : Structure de données sous forme de tableau PHP
     * - 'csv'      : Fichier séparé par des virgules (Comma Separated Values)
     * - 'json'     : Format JSON standard (utilise json_encode())
     * - 'jsonp'    : JSON avec Padding (si paramètre 'callback' présent dans la requête)
     * - 'php'      : Format PHP (utilise var_export())
     * - 'serialized': Données sérialisées PHP (utilise serialize())
     * - 'xml'      : Format XML (utilise simplexml_load_string())
     *
     * @var string
     */
    'format' => 'json',

    /**
     * Spécifie si on doit utiliser le mode strict (envoi des codes HTTP appropriés pour la réponse)
     *
     * - TRUE  : Retourne les codes HTTP réels (200, 400, 404, 500, etc.)
     * - FALSE : Toutes les réponses auront le statut 200, seul le champ 'code' dans le body changera
     *
     * @var bool
     */
    'strict' => true,

    /**
     * Noms des champs dans les réponses JSON de l'API
     *
     * @var array<string, string>
     */
    'field' => [
        /**
         * Nom du champ pour le statut de l'opération (true/false)
         *
         * @var string
         */
        'status' => 'status',

        /**
         * Nom du champ pour le message descriptif de la réponse
         *
         * @var string
         */
        'message' => 'message',

        /**
         * Nom du champ pour le code d'erreur/succès personnalisé
         *
         * @var string
         */
        'code' => 'code',

        /**
         * Nom du champ pour la liste des erreurs de validation
         *
         * @var string
         */
        'errors' => 'errors',

        /**
         * Nom du champ pour les données résultantes de l'opération
         *
         * @var string
         */
        'result' => 'result',
    ],

    /**
     * Liste des adresses IP interdites d'accès à l'API
     *
     * Si vide, aucune adresse IP ne sera bloquée
     *
     * Format : ['123.456.789.0', '192.168.1.100', '10.0.0.0/24']
     *
     * @var list<string>
     */
    'ip_blacklist' => [],

    /**
     * Liste des adresses IP autorisées à accéder à l'API
     *
     * Si vide, toutes les adresses IP seront autorisées
     *
     * Note : 127.0.0.1 et 0.0.0.0 sont toujours ajoutés automatiquement
     *
     * Format : ['123.456.789.0', '192.168.1.0/24', '10.0.0.1']
     *
     * @var list<string>
     */
    'ip_whitelist' => [],

    /**
     * Restreint l'accès aux requêtes AJAX uniquement
     *
     * Lorsque activé, les requêtes non-AJAX seront rejetées avec une erreur 406
     *
     * Recommandé pour les environnements de production
     *
     * @var bool
     */
    'ajax_only' => false,
];
