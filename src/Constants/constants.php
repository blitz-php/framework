<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

define('BASEPATH', dirname(COMPOSER_PATH) . DS);

define('ROOTPATH', dirname(WEBROOT) . DS);

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

/**
 * Chemin  vers le dossier des configurations
 */
defined('CONFIG_PATH') || define('CONFIG_PATH', APP_PATH . 'Config' . DS);

/**
 * Chemin  vers le dossier des controllers
 */
defined('CONTROLLER_PATH') || define('CONTROLLER_PATH', APP_PATH . 'Controllers' . DS);

/**
 * Chemin  vers le dossier des entités
 */
defined('ENTITY_PATH') || define('ENTITY_PATH', APP_PATH . 'Entities' . DS);

/**
 * Chemin vers le dossier des helpers de l'application
 */
defined('HELPER_PATH') || define('HELPER_PATH', APP_PATH . 'Helpers' . DS);

/**
 * Chemin vers le dossier des helpers de l'application
 */
defined('LIBRARY_PATH') || define('LIBRARY_PATH', APP_PATH . 'Libraries' . DS);

/**
 * Chemin  vers le dossier des middlewares
 */
defined('MIDDLEWARE_PATH') || define('MIDDLEWARE_PATH', APP_PATH . 'Middlewares' . DS);

/**
 * Chemin  vers le dossier des modeles
 */
defined('MODEL_PATH') || define('MODEL_PATH', APP_PATH . 'Models' . DS);

/**
 * Chemin  vers le dossier des ressources
 */
defined('RESOURCE_PATH') || define('RESOURCE_PATH', APP_PATH . 'Resources' . DS);

/**
 * Chemin vers le dossier de stockage des fichiers temporaires
 */
defined('TEMP_PATH') || define('TEMP_PATH', STORAGE_PATH . 'temp' . DS);

/**
 * Chemin vers le dossier des vues
 */
defined('VIEW_PATH') || define('VIEW_PATH', APP_PATH . 'Views' . DS);

/**
 * Chemin vers le dossier de cache des vues
 */
defined('VIEW_CACHE_PATH') || define('VIEW_CACHE_PATH', STORAGE_PATH . 'cache' . DS);

/**
 * Chemin vers le dossier des traductions
 */
defined('LANG_PATH') || define('LANG_PATH', RESOURCE_PATH . 'lang' . DS);

/**
 * Chemin vers le dossier des layouts
 */
defined('LAYOUT_PATH') || define('LAYOUT_PATH', VIEW_PATH . 'layouts' . DS);

if (! defined('SERVICE_PATH')) {
    /**
     * Services directory path
     */
    define('SERVICE_PATH', APP_PATH . 'services' . DS);
}

if (! defined('LOG_PATH')) {
    /**
     * Application logs files storage path
     */
    define('LOG_PATH', STORAGE_PATH . 'logs' . DS);
}

if (! defined('DATABASE_PATH')) {
    /**
     * Database storage directory path
     */
    define('DATABASE_PATH', STORAGE_PATH . 'database' . DS);
}

if (! defined('DB_MIGRATION_PATH')) {
    /**
     * Database migrations storage path
     */
    define('DB_MIGRATION_PATH', RESOURCE_PATH . 'database' . DS . 'migrations' . DS);
}

if (! defined('DB_SEED_PATH')) {
    /**
     * Database seeds storage path
     */
    define('DB_SEED_PATH', RESOURCE_PATH . 'database' . DS . 'seeds' . DS);
}

if (! defined('DB_DUMP_PATH')) {
    /**
     * Database backup storage path
     */
    define('DB_DUMP_PATH', DATABASE_PATH . 'dump' . DS);
}

if (! defined('DB_CACHE_PATH')) {
    /**
     * Database cache directory path
     */
    define('DB_CACHE_PATH', DATABASE_PATH . 'cache' . DS);
}

/**
 * Cela définit l'espace de noms par défaut qui est utilisé dans BlitzPHP pour faire référence au répertoire de l'application.
 * Modifiez cette constante pour modifier l'espace de noms que toutes les classes d'application doivent utiliser.
 *
 *  REMARQUE : changer cela nécessitera de modifier manuellement les espaces de noms existants des classes d'espaces de noms App\*.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/**
 * Constantes de temps
 *
 * Fournissez des moyens simples de travailler avec la myriade de fonctions PHP qui nécessitent des informations en quelques secondes.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2592000);
defined('YEAR')   || define('YEAR', 31536000);
defined('DECADE') || define('DECADE', 315360000);

/**
 * --------------------------------------------------------------------------
 * | Codes d'état de sortie
 * --------------------------------------------------------------------------
 *
 * Utilisé pour indiquer les conditions dans lesquelles le script sort().
 *
 * Bien qu'il n'existe pas de norme universelle pour les codes d'erreur, il existe quelques conventions générales.
 * Trois de ces conventions sont mentionnées ci-dessous, pour ceux qui souhaitent en faire usage.
 * Les valeurs par défaut de BlitzPHP ont été choisies pour le moins de chevauchement avec ces conventions,
 * tout en laissant de la place pour que d'autres soient définies dans les futures versions et applications utilisateur.
 *
 * Les trois principales conventions utilisées pour déterminer les codes d'état de sortie sont les suivantes :
 *
 * - Librairie Standard C/C++ (stdlibc): http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 * - BSD sysexits.h: http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 * - Bash scripting: http://tldp.org/LDP/abs/html/exitcodes.html
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0); // pas d'erreurs
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1); // erreur generique
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3); // erreur de configuration
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4); // fichier non trouvé
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5); // classe inconnue
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // membre de classe inconnu
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7); // saisie utilisateur invalide
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8); // erreur de base de données
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9); // code d'erreur attribué automatiquement le plus bas
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125); // code d'erreur attribué automatiquement le plus élevé
