<?php

/**
 * ------------------------------------------------- -------------------------
 * Configuration des API REST
 * ------------------------------------------------- -------------------------
 *
 * Ce fichier vous permet de definir le fonctionnement de vos controleurs REST
 */

return [
    /**
     * 
     */
    'language'        => 'en',
    
    /**
     * Définir pour forcer l'utilisation de HTTPS pour les appels d'API REST
     * 
     * @var bool
     */
    'force_https'     => false,

    /**
     * Liste des méthodes d'autorisation d'accès au service Web
     * 
     * @var array
     */
    'allowed_methods' => ['GET', 'POST', 'OPTIONS', 'PUT', 'DELETE', 'PATCH'],

    /**
     * Le format par défaut de la réponse
     * 
     * @var string
    *
    * 'array' : structure de données du tableau
    * 'csv' : fichier séparé par des virgules
    * 'json' : Utilise json_encode(). Remarque : Si une chaîne de requête GET appelée "callback" est transmise, jsonp sera renvoyé.
    * 'php' : Utilise var_export()
    * 'sérialisé' : Utilise serialize()
    * 'xml' : Utilise simplexml_load_string()
    */
    'format'          => 'json',

    /**
     * Specifie si on doit utiliser le mode strict (envoi des codes HTTP appropries pour la reponse)
     * Si défini à FALSE, toutes les reponses auront le statut 200, seul le champ code changera
     * 
     * @var bool
     */
    'strict'          => true,

    /**
     * @var array<string, string>
     */
    'field'          => [
        /**
         * Le nom du champ pour le 'statut' dans la réponse
         */
        'status'  => 'status',
        /**
         * Le nom du champ pour le 'message' dans la réponse
         */
        'message' => 'message',
        /**
         * Le nom du champ pour le 'code' dans la réponse
         */
        'code'    => 'code',
        /**
         * Le nom du champ pour les 'erreurs' dans la réponse
         */
        'errors'  => 'errors',
        /**
         * Le nom du champ pour les 'resultats' dans la réponse
         */
        'result'  => 'result'
    ],

    /**
     * Empêcher les connexions à partir des adresses IP suivantes
     * Si vide, aucune adresse IP ne sera bloquée
     * 
     * exemple ['123.456.789.0', '987.654.32.1']
     * 
     * @var string[]
     */
    'ip_blacklist'    => [],

    /**
     * Limitez les connexions à votre serveur REST avec une virgule séparée liste des adresses IP
     * Si vide, toutes les adresses IP seront autorisées
     * 
     * exemple : ['123.456.789.0', '987.654.32.1'] 
     * 
     * @var string[]
     */
    'ip_whitelist'    => [],

    /**
     * Défini sur TRUE pour autoriser uniquement les requêtes AJAX. Définir sur FALSE pour accepter les requêtes HTTP
     * 
     * Remarque : Si la valeur est TRUE et que la requête n'est pas AJAX, une réponse 505 avec 
     * un message d'erreur "Seules les requêtes AJAX sont acceptées." sera retourné.
     * 
     * Astuce : C'est bon pour les environnements de production
     * 
     * @var bool
     */
    'ajax_only'       => false,

    /**
     * Définir pour spécifier que l'API REST nécessite d'être connecté
     * 
     * - FALSE Aucune connexion requise
     * - 'jwt' Jeton Web Token avec en-tête Bearer
     * - 'session' Recherche une variable de session PHP.
     */
    'auth'            => 'jwt',

    'jwt'             => [
        /**
         * Cle du token
         * 
         * @var string
         */
        'key'        => env('jwt.key', 'blitz-php-token'),

        /**
         * La cle et la cle publique doivent etre les memes en cas d'utilisation simple
         * si vous utiliser l'algorithme RS256, vous devez definir la clé privée et la clé public respectivement
         * 
         * @link https://github.com/firebase/php-jwt#example-with-rs256-openssl
         * 
         * @var string
         */
        'public_key' => env('jwt.public_key', 'blitz-php-token'),

        /**
         * Temps d'expiration du token en minute
         * 
         * @var int
         */
        'exp_time'   => 5,

        /**
         * Defini l'algorithme a utiliser pour générer le token
         * 
         * @var string  
         */
        'algorithm'  => 'HS256',

        /**
         * Specifie si on doit fusionner  les données du payload avec les entêtes du token
         * 
         * @var bool
         */
        'merge'      => true
    ],
];
