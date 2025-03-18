<?php

/**
 * ------------------------------------------------- -------------------------
 * Configuration des log
 * ------------------------------------------------- -------------------------
 *
 * Ce fichier vous permet de definir comment votre application doit traiter les log
 */

return [
    /**
     * Nom du canal des log
     *
     * @var string
     */
    'name' => config('app.name', 'Application'),

    /**
     * ------------------------------------------------- -------------------------
     * Format de date pour les journaux
     * ------------------------------------------------- -------------------------
     *
     * Chaque élément enregistré a une date associée. Vous pouvez utiliser la date PHP
     * codes pour définir votre propre format de date
     *
     * @var string
     */
    'date_format' => 'Y-m-d H:i:s',

    /**
     * ------------------------------------------------- -------------------------
     * Processeurs de logs
     * ------------------------------------------------- -------------------------
     *
     * Les processeurs permettent d'ajouter des données supplémentaires pour tous les enregistrements.
     *
     * @var list<string>
     */
    'processors' => [
        /**
         * Ajoute l'URI de la requête actuelle, la méthode de requête et l'IP du client à un enregistrement de journal.
         */
        'web',
        /**
         * Ajoute la ligne/le fichier/la classe/la méthode à l'origine de l'appel de journal.
         */
        'introspection',
        /**
         * Ajoute le nom d'hôte actuel à un enregistrement de journal.
         */
        'hostname',
        /**
         * Ajoute l'ID de processus à un enregistrement de journal.
         */
        // 'process_id',
        /**
         * Ajoute un identifiant unique à un enregistrement de journal.
         */
        // 'uid',
        /**
         * Ajoute l'utilisation actuelle de la mémoire à un enregistrement de journal.
         */
        // 'memory_usage',
        /**
         * Traite le message d'un enregistrement de journal conformément aux règles PSR-3, en le remplaçant {foo} par la valeur de $context['foo']
         */
        'psr',
    ],

    /**
     * ------------------------------------------------- -------------------------
     * Gestionnaires de journaux
     * ------------------------------------------------- -------------------------
     *
     * Le système de journalisation prend en charge plusieurs actions à entreprendre lorsque quelque chose
     * est enregistré. Cela se fait en autorisant plusieurs gestionnaires, des classes spéciales
     * conçu pour écrire le journal vers les destinations choisies, que ce soit
     * un fichier sur le getServer, un service basé sur le cloud, ou même prendre des mesures telles
     * en envoyant un e-mail à l'équipe de développement.
     *
     * Chaque gestionnaire est défini par le nom de ce gestionaire.
     *
     * La valeur de chaque clé est un tableau d'éléments de configuration qui sont envoyés
     * au constructeur de chaque gestionnaire. Le seul élément de configuration requis
     * est l'élément 'handles', qui doit être un tableau de niveaux de log entiers.
     * Ceci est plus facilement géré en utilisant les constantes définies dans le
     * Classe `Psr\Log\LogLevel`.
     *
     * Les gestionnaires sont exécutés dans l'ordre défini dans ce tableau, en commençant par
     * le gestionnaire en haut et en continuant vers le bas.
     */
    'handlers' => [
        // ------------------ ENREGISTREMENT ------------------------

        /*
         * --------------------------------------------------------------------
         * Enregistre les logs dans les fichiers
         * --------------------------------------------------------------------
         */
        'file' => [
            /**
             * Le niveau de journalisation que ce gestionnaire gérera.
             *
             * Enregistrera un log uniquement si son niveau est inférieur ou égal à ce niveau
             *
             * @var string
             */
            'level' => on_prod() ? Psr\Log\LogLevel::ERROR : Psr\Log\LogLevel::DEBUG,

            /**
             * L'extension de nom de fichier par défaut pour les fichiers journaux.
             * Une extension de 'php' permet de protéger les fichiers journaux via basic
             * scripting, lorsqu'ils doivent être stockés dans un répertoire accessible au public.
             *
             * Remarque : si vous le laissez vide, la valeur par défaut sera '.log'.
             *
             * @var string
             */
            'extension' => '',

            /**
             * Les autorisations du système de fichiers à appliquer sur les fichiers journaux nouvellement créés.
             *
             * @var int
             */
            'permissions' => 644,

            /**
             * Chemin du répertoire de journalisation
             *
             * Par défaut, les journaux sont écrits dans STORAGE_PATH . 'logs/'
             * Spécifiez une destination différente ici, si vous le souhaitez.
             *
             * @var string
             */
            'path' => '',

            /**
             * Le format d'ecriture des journaux
             *
             * Les valeurs admissible sont:
             *  - json : Encode un enregistrement de journal en json.
             *  - line : Formate un enregistrement de journal en une chaîne d'une ligne.
             *  - normalizer: Normalise les objets/ressources en chaînes afin qu'un enregistrement puisse facilement être sérialisé/encodé.
             *  - scalar: Utilisé pour formater les enregistrements de journal dans un tableau associatif de valeurs scalaires.
             *
             * @var string
             */
            'format' => 'line',

            /**
             * Specifie si on veut un fichier journal par jour
             *
             * @var bool
             */
            'dayly_rotation' => true,

            /**
             * Le nombre maximal de fichiers à conserver (0 signifie illimité)
             * Utilisé uniquement si l'option `dayly_rotation` vaut `true`
             *
             * @var int
             */
            'max_files' => 0,
        ],

        /*
         * --------------------------------------------------------------------
         * Enregistre les logs dans les fichiers
         * --------------------------------------------------------------------
         */
        // 'error' => [
        //     /**
        //      * @var string
        //      */
        //     'level' => \Psr\Log\LogLevel::DEBUG,

        //     /**
        //      * Indique où l'erreur doit aller.
        //      *
        //      * - 0 : Système
        //      * - 4 : SAPI
        //      *
        //      * @var int (0 | 4)
        //      */
        //     'type' => 0,

        //      /**
        //      * Le format d'ecriture des journaux
        //      *
        //      * Les valeurs admissible sont:
        //      *  - json : Encode un enregistrement de journal en json.
        //      *  - line : Formate un enregistrement de journal en une chaîne d'une ligne.
        //      *
        //      * @var string
        //      */
        //     'format' => 'line',
        // ],

        // ------------------ NOTIFICATION ------------------------

        /*
         * --------------------------------------------------------------------
         * Envoi les log par mails
         * --------------------------------------------------------------------
         */
        // 'email' => [
        //     /**
        //      * Le niveau de journalisation que ce gestionnaire gérera.
        //      *
        //      * Enregistrera un log uniquement si son niveau est inférieur ou égal à ce niveau
        //      *
        //      * @var string
        //      */
        //      'level' => \Psr\Log\LogLevel::CRITICAL,

        //     /**
        //      * L'email qui recevera le rapport d'erreur
        //      *
        //      * @var string|array
        //      * @required
        //      */
        //     'to' => '',

        //     /**
        //      * L'emetteur de l'email
        //      *
        //      * @var string
        //      * @required
        //      */
        //     'from' => '',

        //     /**
        //      * L'objet de l'email envoyé
        //      *
        //      * @var string
        //      * @required
        //      */
        //     'subject' => '',

        //     /**
        //      * Le format d'ecriture des journaux
        //      *
        //      * Les valeurs admissible sont:
        //      *  - html : Utilisé pour formater les enregistrements de journal dans un tableau html lisible par l'homme, principalement adapté aux e-mails.
        //      *  - json : Encode un enregistrement de journal en json.
        //      *  - line : Formate un enregistrement de journal en une chaîne d'une ligne.
        //      *
        //      * @var string
        //      */
        //     'format' => 'line',
        // ],

        /*
         * --------------------------------------------------------------------
         * Envoi les log par telegram
         * --------------------------------------------------------------------
         */
        // 'telegram' => [
        //     /**
        //      * Le niveau de journalisation que ce gestionnaire gérera.
        //      *
        //      * Enregistrera un log uniquement si son niveau est inférieur ou égal à ce niveau
        //      *
        //      * @var string
        //      */
        //     'level' => \Psr\Log\LogLevel::CRITICAL,

        //     /**
        //      * Jeton d'accès au bot Telegram fourni par BotFather
        //      *
        //      * Créez un bot de Telegram avec https://telegram.me/BotFather
        //      *
        //      * @var string
        //      * @required
        //      */
        //     'api_key' => '',

        //     /**
        //      * Nom du canal de Telegram
        //      *
        //      * @var string
        //      * @required
        //      */
        //     'channel' => '',

        //      /**
        //      * @var string
        //      */
        //     'format' => 'line',
        // ],

        // ------------------ DEBOGAGE ------------------------

        /*
         * --------------------------------------------------------------------
         * Envoi les log dans la console Chrome pour le débogage
         *
         * Nécessite l'extension ChromeLogger installée dans votre navigateur.
         * --------------------------------------------------------------------
         */
        // 'chrome' => [
        //     /**
        //      * Le niveau de journalisation que ce gestionnaire gérera.
        //      *
        //      * Enregistrera un log uniquement si son niveau est inférieur ou égal à ce niveau
        //      *
        //      * @var string
        //      */
        //     'level' => \Psr\Log\LogLevel::DEBUG,

        //       /**
        //        * @var string
        //        */
        //       'format' => 'line',
        // ],

        /*
         * --------------------------------------------------------------------
         * Envoi les log dans la console Firebug pour le débogage
         *
         * Nécessite l'extension Firebug installée dans votre navigateur.
         * --------------------------------------------------------------------
         */
        // 'firebug' => [],

        /*
         * --------------------------------------------------------------------
         * Envoi les log dans la console du navigareur pour le débogage
         * --------------------------------------------------------------------
         */
        // 'browser' => [],
    ],
];
