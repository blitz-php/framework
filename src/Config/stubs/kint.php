<?php

/**
 * ------------------------------------------------- -------------------------
 * Configuration de Kint PHP
 * ------------------------------------------------- -------------------------
 *
 * Nous utilisons `RichRenderer` et `CLIRenderer`.
 * Cette section contient les options que vous pouvez modifier pour personnaliser
 * la façon dont vous voulez que KINT fonctionne pour vous
 *
 * @see https://kint-php.github.io/kint/ pour plus de details.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration global
    |--------------------------------------------------------------------------
    */

    /**
     * @var array
     */
    'plugins' => [],

    /**
     * Profondeur maximale pour le parcours des tableaux/objets.
     * 0 pour aucune limite
     *
     * @var int
     */
    'depth_limit' => 6,

    /**
     * Doit-on afficher où est-ce qu'on a lancer l'appel de kint ?
     *
     * @var bool
     */
    'display_called_from' => true,

    /**
     * Ouvre tous les arboressences pour la RichView ?
     *
     * @var bool
     */
    'expanded' => false,

    /*
    |--------------------------------------------------------------------------
    | Configuration RichRenderer
    |--------------------------------------------------------------------------
    */

    /**
     * Theme a utiliser
     *
     * @var string
     */
    'rich_theme' => 'original.css',

    /**
     * Deplace tous les elements dans un dossier au pas de la page ?
     *
     * @var bool
     */
    'rich_folder' => false,

    /**
     * @var array
     */
    'rich_value_plugins' => [],

    /**
     * @var array
     */
    'rich_tab_plugins' => [],

    /*
    |--------------------------------------------------------------------------
    | Configurations CLI
    |--------------------------------------------------------------------------
    */

    /**
     * Activation de la couleur dans le terminal ?
     *
     * @var bool
     */
    'cli_colors' => true,

    /**
     * Force la sortie en utf8 ?
     *
     * @var bool
     */
    'cli_force_utf8' => false,

    /**
     * Detecte la largeur du terminal au demarrage ?
     *
     * @var bool
     */
    'cli_detect_width' => true,

    /**
     * La valeur minimale de la largeur a detecter.
     * Les largeurs plus petites que celle-ci seront ignorees et on rentrera a la taille par défaut
     *
     * @var int
     */
    'cli_min_terminal_width' => 40,
];
