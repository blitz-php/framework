<?php 

/*
| -------------------------------------------------- -----------------
| CONFIGURATION DU MOTEUR DE RENDU DES VUES
| -------------------------------------------------- -----------------
| Ce fichier contiendra les paramètres necessaires pour rendre des vues au navigateur de l'utilisateur
|
| Pour des instructions complètes, veuillez consulter la « Configuration des vues » dans le Guide de l'utilisateur.
|
*/


return [
    /**
     * Adapteur responsable du rendu des vues
     * 
     * voir le tableau `adapters` ci-dessous
     */
    'active_adapter' => 'native',

    /**
     * Specifie si on doit compresser le code html final ou pas avant de l'envoyer au navigateur
     * 
     * @var bool|'auto'|Closure Si auto, le systeme compressera le code uniquement si vous passez dans un environement de production.
     *                          Peut egalement etre une closure qui renvoie un boolean ou la chaine 'auto'.
     */
    'compress_output' => 'auto',

    /**
     * Chemin de base de stockage des vues
     * 
     * Les vues serront recherchées à partir de ce dossier
     * 
     * @var string
     */
    'view_base' => VIEW_PATH,

    /**
     * Specifie si on doit stocker des informations sur les performances ou non
     * 
     * @var bool|'auto' Si auto, le systeme stockera les informations de performances uniquement dans un environnement de developpement
     */
    'debug' => 'auto',

    /**
     * Données partagées à toutes les vues
     */
    'shared' => function(): array {
        return [

        ];
    },

    /**
     * Les decorateurs de vues sont des méthodes de classe qui seront exécutées en séquence pour avoir la possibilité de modifier la sortie générée juste avant la mise en cache des résultats.
     *
     * Toutes les classes doivent implémenter BlitzPHP\View\ViewDecoratorInterface
     *
     * @var classe-string<\BlitzPHP\View\ViewDecoratorInterface>[]
      */
    'decorators' => [],

    'adapters' => [
        /**
         * [Configuration Native]
         */
        'native' => [
            /**
             * Extension de fichier de vues
             */
            'extension' => 'php',

            /**
             * Lorsque false, la méthode d'affichage effacera les données entre chaque
             * appel. Cela protège vos données et garantit qu'il n'y a pas de
             * fuite entre les appels, vous devrez donc transmettre explicitement les données
             * à chaque vue. Vous préférerez peut-être que les données restent entre
             * appelle afin qu'il soit disponible pour toutes les vues. Si c'est le cas,
             * définissez $saveData sur vrai.
             *
             * @var bool
             */
            'save_data' => true,
        ],

        /**
         * [Configuration Blade](https://laravel.com/docs/9.x/views)
         * 
         * executer `composer require jenssegers/blade`
         * require jenssegers/blade
         */
        'blade' => [
            // /**
            //  * Extension de fichier de vues
            //  */
            // 'extension' => 'blade.php',

            // /**
            //  * Répertoire où sont stockés les caches de vues
            //  * 
            //  * @var string
            //  */
			// 'cache_path'      => VIEW_CACHE_PATH.'blade'.DIRECTORY_SEPARATOR,

            // /**
            //  * Enregistrez un gestionnaire pour les directives personnalisées.
            //  * 
            //  * @var array<string, callable>
            //  */
            // 'directives' => [],

            // /**
            //  * Enregistrez une directive d'instruction "if".
            //  * 
            //  * @var array<string, callable>
            //  */
            // 'if' => [],

            //  /**
            //   * Fonction de configuration manuelle du moteur de template
            //   */
            // 'configure' => function (\Jenssegers\Blade\Blade $engine): \Jenssegers\Blade\Blade {

            //     return $engine;
            // },
        ],

        /**
         * [Configuration Latte]
         * 
         * executer `composer require latte/latte`
         * requiet latte 3.x or higher
         */
        'latte' => [
            // /**
            //  * Extension de fichier de vues
            //  */
            // 'extension' => 'latte',

            // /**
            //  * Latte régénère automatiquement le cache à chaque fois que vous modifiez le template, 
            //  * qui peut être désactivé dans l'environnement de production pour économiser un peu de performances
            //  * 
            //  * @var bool|'auto' SI auto, desactivera ce comportement en production
            //  */ 
            // 'auto_refresh' => 'auto',

            // /**
            //  * Chemin de stockage temporaire des vues générées par latte
            //  */
            // 'temp_path' => TEMP_PATH . 'views',

            //  /**
            //   * Fonction de configuration manuelle du moteur de template
            //   */
            // 'configure' => function (\Latte\Engine $engine): \Latte\Engine {

            //     return $engine;
            // },
        ],

        /**
         * [Configuration Plates](https://platesphp.com/engine/overview/)
         * 
         * executer `composer require league/plates`
         * requiet plates 3.x or higher
         */
        'plates' => [
            // /**
            //  * Extension de fichier de vues
            //  */
            // 'extension' => 'php',

            // /**
            //  * Fonctions
            //  * 
            //  * @var array<string, callable>
            //  */
            // 'functions' => [],

            // /**
            //  * Fonction de configuration manuelle du moteur de template
            //  */
            // 'configure'     => function(\League\Plates\Engine $engine) : \League\Plates\Engine {

            //     return $engine;
            // }
        ],

        /**
         * [Configuration Smarty](https://www.smarty.net/docs/en/api.variables.tpl)
         * 
         * executer `composer require smarty/smarty`
         * requiet smarty 3.x or higher
         */
        'smarty' => [
            // /**
            //  * Extension de fichier de vues
            //  */
            // 'extension' => 'tpl',

            // /**
            //  * Répertoire ou répertoires utilisés pour stocker les fichiers de configuration utilisés dans les modèles
            //  * 
            //  * @var string|string[]
            //  */
			// 'config_dir'     => [CONFIG_PATH],

            // /**
            //  * Répertoire où sont stockés les caches de vues
            //  * 
            //  * @var string
            //  */
			// 'cache_dir'      => VIEW_CACHE_PATH.'smarty'.DIRECTORY_SEPARATOR.'cache',

            // /**
            //  * Répertoire où se trouvent les modèles compilés
            //  * 
            //  * @var string|string[]
            //  */
			// 'compile_dir'    => VIEW_CACHE_PATH.'smarty'.DIRECTORY_SEPARATOR.'compile',

            // /**
            //  * Mise en cache activée ?
            //  * 
            //  * @var bool|int
            //  */
			// 'caching'        => \Smarty::CACHING_OFF,

            // /**
            //  * Durée de vie du cache en secondes
            //  * 
            //  * @var int
            //  */
			// 'cache_lifetime' => 5 * MINUTE,

            // /**
            //  * Mettre à jour les modèles de cache à chaque appel ?
            //  * 
            //  * @var bool
            //  */
			// 'force_cache'    => true,

            // /**
            //  * Mettre à jour les modèles de compilation à chaque appel ?
            //  * 
            //  * @var bool
            //  */
			// 'force_compile'  => false,

            // /**
            //  * Échappera à toutes les sorties de variable du template ?
            //  * 
            //  * @var bool
            //  */
			// 'escape_html'    => on_prod(),

            // /**
            //  * Active la console de débogage ?
            //  * 
            //  * @var bool
            //  */
			// 'debugging'      => on_dev(),
            
            // /**
            //  * Vérifier le modèle pour les modifications ?
            //  * 
            //  * @var bool
            //  */
            // 'compile_check'  => on_dev(),

            // /**
            //  * Fonction de configuration manuelle du moteur de template
            //  */
            // 'configure'     => function(\Smarty $engine) : \Smarty {

            //     return $engine;
            // }
        ],

        /**
         * [Configuration Twig](https://twig.symfony.com/doc/3.x/api.html#environment-options)
         * 
         * executer `composer require twig/twig`
         * requiet twig 3.x or higher
         */
        'twig' => [
            // /**
            //  * Extension de fichier de vues
            //  */
            // 'extension' => 'twig',

            // /**
            //  * Répertoire où sont stockés les caches de modèles
            //  * 
            //  * @var string
            //  */
            // 'cache_dir'            => VIEW_CACHE_PATH.'twig',

            // /**
            //  * Afficher les noeuds générés ?
            //  * 
            //  * @var bool|'auto' Si auto, le systeme affichera les noeuds en phase de developpement mais pas en production
            //  */
			// 'debug'            => 'auto',

            // /**
            //  * Le jeu de caractères utilisé par les templates
            //  * 
            //  * @var string
            //  */
			// 'charset'          => 'UTF-8',

            // /**
            //  * Recompiler le modèle chaque fois que le code source change
            //  * 
            //  * @var bool|'auto' Si auto, la recompilation s'effectuera uniquement en phase de developpement
            //  */
			// 'auto_reload'      => true,

            // /**
            //  * Si défini sur `FALSE`, ignorera silencieusement les variables invalides, sinon lèvera une exception à la place
            //  * 
            //  * @var bool|'auto' Si auto, sera defini automatiquement sur FALSE en phase de production et TRUE en developpement
            //  */
			// 'strict_variables' => 'auto',

            // /**
            //  * Variables globales à passer à Twig
            //  * 
            //  * @var array<string, mixed>
            //  */
            // 'globals' => [
            //     /*
            //     'URL'   => new URL,
            //     'HTML'  => new HTML,
            //     'Form'  => new Form,
            //     'Router' => new Router,
            //     */
            // ],
          
            // /**
            //  * Filtres
            //  * 
            //  * @var \Twig\TwigFilter[]
            //  */
            // 'filters'   => [],

            // /**
            //  * Fonctions
            //  * 
            //  * * @var \Twig\TwigFunction[]
            //  */
            // 'functions' => [],

            // /**
            //  * Fonction de configuration manuelle du moteur de template
            //  */
            // 'configure'     => function(\Twig\Environment $engine) : \Twig\Environment {

            //     return $engine;
            // }
        ]
    ]
];