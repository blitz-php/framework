<?php

/**
 * ------------------------------------------------- -------------------------
 * Barre d'outils de débogage
 * ------------------------------------------------- -------------------------
 *
 * La barre d'outils de débogage permet d'afficher des informations sur les performances
 * et l'état de votre application lors de l'affichage de cette page. Par défaut, ce sera
 * NE PAS être affiché dans les environnements de production
 */

return [
    /**
     * Liste des collecteurs de barre d'outils qui seront appelés lors du débogage de la barre d'outils
     * se déclenche et collecte des données à partir de.
     *
     * @var string[]
     */
    'collectors' => [
        \BlitzPHP\Debug\Toolbar\Collectors\TimersCollector::class,
        \BlitzPHP\Debug\Toolbar\Collectors\LogsCollector::class,
        \BlitzPHP\Debug\Toolbar\Collectors\ViewsCollector::class,
        \BlitzPHP\Debug\Toolbar\Collectors\FilesCollector::class,
        \BlitzPHP\Debug\Toolbar\Collectors\RoutesCollector::class,
        \BlitzPHP\Debug\Toolbar\Collectors\EventsCollector::class,
    ],

    /**
     * Si défini sur false, les données var des vues ne seront pas collectées. Utile pour
     * éviter une utilisation élevée de la mémoire lorsqu'il y a beaucoup de données transmises à la vue.
     *
     * @var bool
     */
    'collect_var_data' => true,

    /**
     * Définit une limite sur le nombre de requêtes passées qui sont stockées,
     * aidant à conserver l'espace de fichier utilisé pour les stocker. Vous pouvez le régler sur
     * 0 (zéro) pour ne pas avoir d'historique stocké, ou -1 pour un historique illimité.
     *
     * @var int
     */
    'max_history' => 20,

    /**
     * Le chemin d'accès complet aux vues utilisées par la barre d'outils.
     *
     * @var string
     */
    'view_path' => SYST_PATH . 'Debug' . DS . 'Toolbar' . DS . 'Views',

    /**
     * Specifie si on doit afficher la debugbar ou pas
     * Quelque soit la valeur défini, la debugbar ne sera pas affichée en production
     *
     * @var bool
     */
    'show_debugbar' => true,

    /**
     * --------------------------------------------------------------------------
     * Dossiers surveillés
     * --------------------------------------------------------------------------
     *
     * Contient un tableau de dossiers dont les modifications seront surveillées et
     * utilisées pour déterminer si la fonctionnalité de rechargement à chaud doit recharger la page ou non.
     * Nous limitons les valeurs afin de maintenir les performances au maximum.
     *
     * REMARQUE : Le chemin racine (ROOTPATH) sera ajouté au début de toutes les valeurs.
     *
     * @var list<string>
     */
    'watched_directories' => [
        'app',
    ],

    /**
     * --------------------------------------------------------------------------
     * Extensions de fichiers surveillées
     * --------------------------------------------------------------------------
     *
     * Contient un tableau d'extensions de fichiers dont les modifications seront surveillées et
     * utilisé pour déterminer si la fonctionnalité de rechargement à chaud doit recharger la page ou non.
     *
     * @var list<string>
     */
    'watched_extensions' => [
        'php', 'css', 'js', 'html', 'svg', 'json', 'env',
    ],

	/**
     * --------------------------------------------------------------------------
     * En-têtes HTTP ignorés
     * --------------------------------------------------------------------------
     *
     * La barre d'outils de débogage BlitzPHP injecte normalement du code HTML et JavaScript dans chaque
     * réponse HTML. Cela fonctionne correctement pour le chargement complet d'une page, mais cela perturbe les requêtes
     * qui n'attendent qu'un fragment HTML propre.
     *
     * Les bibliothèques telles que HTMX, Unpoly et Hotwire (Turbo) mettent à jour certaines parties de la page ou
     * gèrent la navigation côté client. L'injection de la barre d'outils de débogage dans leurs
     * réponses peut entraîner un HTML invalide, des scripts dupliqués ou des erreurs JavaScript
     * (telles que des boucles infinies ou « Maximum call stack size exceeded »).
     *
     * Toute requête contenant l'un des en-têtes suivants est traitée comme une
     * requête gérée par le client ou partielle, et l'injection de la barre d'outils de débogage est ignorée.
     *
     * @var array<string, string|null>
     */
	'disable_on_headers' => [
        'X-Requested-With' => 'xmlhttprequest', // Requetes AJAX
        'HX-Request'       => 'true',           // Requestes HTMX
        'X-Up-Version'     => null,             // Requetes partielle Unpoly
	],
];
