<?php

return [
    /**
     * Tableau de fichiers qui contiennent des définitions des routes.
     * Les fichiers des routes sont lus dans l'ordre, le premier match trouvé la priorité.
     *
     * Defaut: CONFIG_PATH . 'routes.php'
     */
    'route_files' => [
        CONFIG_PATH . 'routes.php',
    ],

    /**
     * Namespace par défaut à utiliser pour les Contrôleurs lorsqu'aucun autre namespace n'a été spécifié.
     *
     * @var string
     */
    'default_namespace' => 'App\Controllers',

    /**
     * Le contrôleur par défaut à utiliser lorsqu'aucun autre contrôleur n'a été spécifié.
     *
     * @var string
     */
    'default_controller' => 'HomeController',

    /**
     * Méthode par défaut à appeler sur le contrôleur lorsqu'aucune autre méthode n'a été définie dans la route.
     *
     * @var string
     */
    'default_method' => 'index',

    /**
     * Utiliser pour traduire des tirets dans URIs en underscore.
     * Principalement utile lors de l'utilisation de l'auto-routage.
     *
     * @var string
     */
    'translate_uri_dashes' => false,

    /**
     * Définit la classe/la méthode qui doit être appelée si le routage ne trouve pas de correspondance.
     * Il peut être soit une closure ou le nom du contrôleur / méthode exactement comme une route est défini: UsersController::index
     *
     * Ce paramètre est passé à la classe Router et géré là-bas.
     *
     * Example:
     * 	'fallback' => 'App\Errors::show404',
     */
    'fallback' => null,

    /**
     * Si TRUE, le système tentera de faire correspondre l'URI a un contrôleur
     * en faisant correspondre chaque segment à des dossiers/fichiers dans
     * APP_PATH/Controllers, lorsqu'une correspondance n'a pas été trouvée dans les routes définis.
     *
     * Si FALSE, cessera de rechercher et ne fera pas de routage automatique.
     *
     * @var bool
     */
    'auto_route' => false,

    /**
     * Si TRUE, permettra l'utilisation de l'option 'prioriser' lors de la définition des routes.
     *
     * @var bool
     */
    'prioritize' => false,

    /**
     * Pour les routes définies.
     * Si VRAI, les segments d'URI multiples correspondants seront passés en un seul paramètre.
     */
    'multiple_segments_one_param' => false,

    /**
     * Limite ou non les routes avec l'espace réservé {locale} vers App::$supported_locales.
     */
    'use_supported_locales_only' => false,

    /**
     * Carte des segments URI et des namespace. Pour l'Auto Routing.
     *
     * La clé est le premier segment URI. La valeur est le namespace de contrôleur.
     *
     * E.g.,
     *   [
     *       'blog' => 'Acme\Blog\Controllers',
     *   ]
     *
     * @var array [ uri_segment => namespace ]
     */
    'module_routes' => [],

    /**
     * Pour le routage automatique.
     * Si les tirets dans les URIs pour les contrôleurs/méthodes doivent être traduits en CamelCase.
     * Par exemple, blog-controller -> BlogController
     *
     * Si vous activez ceci, `translate_uri_dashes` est ignoré.
     */
    'translate_uri_to_camel_case' => true,
];
