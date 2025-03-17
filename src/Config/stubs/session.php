<?php

return [
    /**
     * ------------------------------------------------- -------------------------
     * Pilote de session
     * ------------------------------------------------- -------------------------
     *
     * Le pilote de stockage de session à utiliser :
     * - `BlitzPHP\Session\Handlers\File`
     * - `BlitzPHP\Session\Handlers\Database`
     * - `BlitzPHP\Session\Handlers\Memcached`
     * - `BlitzPHP\Session\Handlers\Redis`
     *
     * @phpstan-var class-string<BaseHandler>
     */
    'handler' => env('session.driver', \BlitzPHP\Session\Handlers\File::class),

    /**
     * ------------------------------------------------- -------------------------
     * Nom du cookie de session
     * ------------------------------------------------- -------------------------
     *
     * Le nom du cookie de session ne doit contenir que des caractères [0-9a-z_-]
     * 
     * @var string
     */
    'cookie_name' => env('session.cookieName', config('app.name', 'blitz_app') . '_session'),

    /**
     * ------------------------------------------------- -------------------------
     * Expiration de la session
     * ------------------------------------------------- -------------------------
     *
     * Le nombre de SECONDES que vous voulez que la session dure.
     * Le réglage sur 0 (zéro) signifie qu'il expire lorsque le navigateur est fermé.
     * 
     * @var int
     */
    'expiration' => env('session.expiration', 7200),

    /**
     * ------------------------------------------------- -------------------------
     * Chemin de sauvegarde de la session
     * ------------------------------------------------- -------------------------
     *
     * L'emplacement d'enregistrement des sessions et dépend du pilote.
     *
     * Pour le pilote 'files', c'est un chemin vers un répertoire accessible en écriture.
     * AVERTISSEMENT : Seuls les chemins absolus sont pris en charge !
     *
     * Pour le pilote 'database', c'est un nom de table.
     * Veuillez lire le manuel pour le format avec d'autres pilotes de session.
     *
     * IMPORTANT : vous devez définir un chemin de sauvegarde valide !
     * 
     * @var string
     */
    'savePath' => env('session.savePath', FRAMEWORK_STORAGE_PATH . 'session'),

    /**
     * ------------------------------------------------- -------------------------
     * IP de correspondance de session
     * ------------------------------------------------- -------------------------
     *
     * S'il faut faire correspondre l'adresse IP de l'utilisateur lors de la lecture des données de session.
     *
     * ATTENTION : si vous utilisez le pilote de base de données, n'oubliez pas de mettre à jour
     * la PRIMARY KEY de votre table de session lors de la modification de ce paramètre.
     * 
     * @var bool
     */
    'matchIP' => env('session.matchIP', false),

    /**
     * ------------------------------------------------- -------------------------
     * Temps de session pour mettre à jour
     * ------------------------------------------------- -------------------------
     *
     * Combien de secondes pour regenerer l'ID de session.
     * 
     * @var int
     */
    'time_to_update' => env('session.timeToUpdate', 300),

    /**
     * ------------------------------------------------- -------------------------
     * Session Régénérer Détruire
     * ------------------------------------------------- -------------------------
     *
     * S'il faut détruire les données de session associées à l'ancien ID de session
     * lors de la régénération automatique de l'ID de session. Lorsqu'il est défini sur FALSE, les données
     * sera ensuite supprimé par le ramasse-miettes.
     * 
     * @var bool
     */
    'regenerate_destroy' => env('session.regenerateDestroy', false),

    /**
     * ------------------------------------------------- -------------------------
     * Groupe de base de données de session
     * ------------------------------------------------- -------------------------
     *
     * Connexion de la bd pour la session de base de données.
     * 
     * @var ?string
     */
    'group' => null,
];
