<?php

/**
 * ------------------------------------------------- -------------------------
 * Configuration du gestionnaire de cache
 * ------------------------------------------------- -------------------------
 *
 * Ce fichier vous permet de definir les parametres de gestion du cache de votre application
 */

return [
    /**
     * ------------------------------------------------- -------------------------
     * Gestionnaire principal
     * ------------------------------------------------- -------------------------
     *
     * Le nom du gestionnaire préféré qui doit être utilisé. Si pour une raison quelconque
     * il n'est pas disponible, le $backupHandler sera utilisé à sa place.
     *
     * @var string
     */
    'handler' => env('cache.handler', 'file'),

    /**
     * ------------------------------------------------- -------------------------
     * Gestionnaire de relais
     * ------------------------------------------------- -------------------------
     *
     * Le nom du gestionnaire qui sera utilisé si le premier est inaccessible.
     * Souvent, 'file' est utilisé ici puisque le système de fichiers est
     * toujours disponible, même si ce n'est pas toujours pratique pour l'application.
     *
     * @var string
     */
    'fallback_handler' => 'dummy',

    /**
     * --------------------------------------------------------------------------
     * Cache Include Query String
     * --------------------------------------------------------------------------
     *
     * Indique si la chaîne de requête de l'URL doit être prise en compte lors de la génération des fichiers de cache de sortie.
     * Les options valides sont :
     *
     *  false = Désactivé
     *  true  = Activé, prend en compte tous les paramètres de la requête.
     *          Veuillez noter que cela peut entraîner la génération de nombreux fichiers de cache pour la même page à plusieurs reprises.
     *  ['q'] = Activé, mais ne prend en compte que la liste spécifiée des paramètres de la requête.
     *
     * @var bool|list<string>
     */
    'cache_query_string' => false,

    /**
     * --------------------------------------------------------------------------
     * Prefixe des clés
     * --------------------------------------------------------------------------
     *
     * Cette chaîne est ajoutée à tous les noms d'éléments de cache pour éviter les collisions
     * si vous utilisez plusieurs applications avec le même moteur de cache.
     *
     * @var string
     */
    'prefix' => env('cache.prefix', config('app.name', 'blitz_app') . '_cache_'),

    /**
     * --------------------------------------------------------------------------
     * Durée de vie par défaut
     * --------------------------------------------------------------------------
     *
     * Le nombre de secondes par défaut pour enregistrer les éléments si aucun n'est spécifié.
     *
     * AVERTISSEMENT : Cette valeur n'est pas utilisée par les gestionnaires du cadre où la valeur de 60 secondes est codée en dur, mais elle peut être utile aux projets et aux modules.
     * Cette valeur remplacera la valeur codée en dur dans une prochaine version.
     *
     * @var int
     */
    'ttl' => env('cache.duration', MINUTE),

    /**
     * --------------------------------------------------------------------------
     * Caractères réservés
     * --------------------------------------------------------------------------
     *
     * Une chaîne de caractères réservés qui ne sera pas autorisée dans les clés ou les balises.
     * Les chaînes qui ne respectent pas cette restriction entraîneront le déclenchement d'un traitement.
     * Défaut : {}()/\@ :
     *
     * NOTE : Le jeu par défaut est requis pour la conformité PSR-6.
     *
     * @var string
     */
    'reserved_characters' => '{}()/\@:',

    /**
     * --------------------------------------------------------------------------
     * Paramètres du gestionnaire 'file'
     * --------------------------------------------------------------------------
     * Vos préférences en matière de stockage de fichiers peuvent être spécifiées ci-dessous, si vous utilisez le pilote de fichiers.
     *
     * @var array<string, int|string|null>
     */
    'file' => [
        'path' => cache_path(),
        'mode' => 0640,
    ],

    /**
     * -------------------------------------------------------------------------
     * Paramètres du gestionnaire 'Memcached'
     * -------------------------------------------------------------------------
     * Vos serveurs Memcached peuvent être spécifiés ci-dessous, si vous utilisez les pilotes Memcached.
     *
     * @var array<string, bool|int|string>
     */
    'memcached' => [
        'host' => '127.0.0.1',
        'port' => 11211,
    ],

    /**
     * -------------------------------------------------------------------------
     * Paramètres du gestionnaire 'Redis'
     * -------------------------------------------------------------------------
     * Votre serveur Redis peut être spécifié ci-dessous, si vous utilisez les pilotes Redis ou Predis.
     *
     * @var array<string, array|int|string|null>
     */
    'redis' => [
        'host'     => '127.0.0.1',
        'password' => false,
        'port'     => 6379,
        'timeout'  => 0,
        'database' => 0,
    ],

    /**
     * ------------------------------------------------- -------------------------
     * Gestionnaires de cache disponibles
     * ------------------------------------------------- -------------------------
     *
     * Il s'agit d'un tableau d'alias de moteur de cache et de noms de classe. Seuls les moteurs
     * qui sont répertoriés ici sont autorisés à être utilisés.
     *
     * @var array<string, class-string<BlitzPHP\Cache\Handlers\BaseHandler>>
     */
    'valid_handlers' => [
        'apcu'      => BlitzPHP\Cache\Handlers\Apcu::class,
        'array'     => BlitzPHP\Cache\Handlers\ArrayHandler::class,
        'dummy'     => BlitzPHP\Cache\Handlers\Dummy::class,
        'file'      => BlitzPHP\Cache\Handlers\File::class,
        'memcached' => BlitzPHP\Cache\Handlers\Memcached::class,
        'redis'     => BlitzPHP\Cache\Handlers\RedisHandler::class,
        'wincache'  => BlitzPHP\Cache\Handlers\Wincache::class,
    ],
];
