<?php

/**
 * Configurez le fonctionnement du gestionnaire d'exceptions.
 */

return [
    /**
     * --------------------------------------------------------------------------
     * LOG LES EXCEPTIONS ?
     * ------------------------------------------------- -------------------------
     * Si c'est vrai, les exceptions seront enregistrées via Services::logger.
     *
     * @var bool
     */
    'log' => true,

    /**
     * --------------------------------------------------------------------------
     * NE PAS ENREGISTRER LES CODES D'ÉTAT
     * ------------------------------------------------- -------------------------
     * Tous les codes d'état de cet options ne seront PAS enregistré si la journalisation est activée.
     * Par défaut, seules les exceptions 404 (Page non trouvée) sont ignorées.
     *
     * @var list<int>
     */
    'ignore_codes' => [404],

    /**
     * --------------------------------------------------------------------------
     * Chemin des vues d’erreur
     * ------------------------------------------------- -------------------------
     * Il s'agit du chemin d'accès au répertoire contenant les vues utilisées pour générer les pages d'erreurs personnalisées.
     *
     * @var string
     */
    'error_view_path' => VIEW_PATH . 'errors',

    /**
     * Titre de la page d'erreur whoops.
     *
     * @var string
     */
    'title' => 'Oups ! Il y avait une erreur.',

    /**
     * Editeur à utiliser pour ouvrir le fichier responsable de l'erreur.
     *
     * @var closure|string
     *
     * @see https://github.com/filp/whoops/blob/master/docs/Open%20Files%20In%20An%20Editor.md
     */
    'editor' => 'vscode',

    /**
     * --------------------------------------------------------------------------
     * CACHER DE LA TRACE DE DÉBOGAGE
     * ------------------------------------------------- -------------------------
     * Toutes les données que vous souhaitez masquer de la trace de débogage.
     * Afin de spécifier 2 niveaux, utilisez "/" pour séparer.
     * exp. ['server', 'cookie/token', 'post/username,password']
     *
     * @var list<string>
     */
    'blacklist' => [],

    /**
     * Donnees a joindre sur la page d'affichage d'erreur whoops
     *
     * @var array<string, array<string, string>|closure>
     *
     * @see https://github.com/filp/whoops/blob/master/examples/example.php#L33
     * @see https://github.com/filp/whoops/blob/master/examples/example.php#L42
     */
    'data' => [],

    /**
     * DÉFINIR LES GESTIONNAIRES UTILISÉS
     * ------------------------------------------------- -------------------------
     * Étant donné le code d'état HTTP, renvoie le gestionnaire d'exceptions qui doit être utilisé pour traiter cette erreur.
     * Les gestionnaires personnalisés peuvent être renvoyés si vous souhaitez gérer un ou plusieurs gestionnaires spécifiques.
     *
     * @var list<class-string>
     */
    'handlers' => [],
];
