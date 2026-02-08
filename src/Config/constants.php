<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

/**
 * Cela définit l'espace de noms par défaut qui est utilisé dans BlitzPHP pour faire référence au répertoire de l'application.
 * Modifiez cette constante pour modifier l'espace de noms que toutes les classes d'application doivent utiliser.
 *
 *  REMARQUE : changer cela nécessitera de modifier manuellement les espaces de noms existants des classes d'espaces de noms App\*.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/** Séparateur de répertoire du système d'exploitation. (e.g., '/' sur Unix, '\' sur Windows). */
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

/** Chemin vers l'autoloader Composer. */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', defined('VENDOR_PATH') ? VENDOR_PATH . 'autoload.php' : __DIR__ . '/../vendor/autoload.php');

/** Chemin racine du projet. */
defined('BASEPATH') || define('BASEPATH', defined('VENDOR_PATH') ? dirname(VENDOR_PATH) . DS : __DIR__ . '/../');

/** Chemin vers le dossier public (exposé via web server). */
defined('PUBLIC_PATH') || define('PUBLIC_PATH', ROOTPATH . 'public' . DS);

/** Chemin vers le dossier des configurations */
defined('CONFIG_PATH') || define('CONFIG_PATH', APP_PATH . 'Config' . DS);

/** Chemin vers le dossier des controllers */
defined('CONTROLLER_PATH') || define('CONTROLLER_PATH', APP_PATH . 'Controllers' . DS);

/** Chemin vers le dossier des helpers de l'application */
defined('HELPER_PATH') || define('HELPER_PATH', APP_PATH . 'Helpers' . DS);

/** Chemin vers le dossier des middlewares */
defined('MIDDLEWARE_PATH') || define('MIDDLEWARE_PATH', APP_PATH . 'Middlewares' . DS);

/** Chemin  vers le dossier des ressources de base */
defined('RESOURCE_PATH') || define('RESOURCE_PATH', BASEPATH . 'resources' . DS);

/** Chemin vers le dossier des vues */
defined('VIEW_PATH') || define('VIEW_PATH', APP_PATH . 'Views' . DS);

/** Chemin vers le dossier des layouts */
defined('LAYOUT_PATH') || define('LAYOUT_PATH', VIEW_PATH . 'layouts' . DS);

/** Chemin vers le dossier de stockage des fichiers du framework */
defined('FRAMEWORK_STORAGE_PATH') || define('FRAMEWORK_STORAGE_PATH', STORAGE_PATH . 'framework' . DS);

/** Chemin vers le dossier de stockage des fichiers de l'application */
defined('APP_STORAGE_PATH') || define('APP_STORAGE_PATH', STORAGE_PATH . 'app' . DS);

/** Chemin vers le dossier de stockage des fichiers temporaires */
defined('TEMP_PATH') || define('TEMP_PATH', FRAMEWORK_STORAGE_PATH . 'temp' . DS);

/** Chemin vers le dossier de cache des vues */
defined('VIEW_CACHE_PATH') || define('VIEW_CACHE_PATH', FRAMEWORK_STORAGE_PATH . 'cache' . DS);

/** Chemin vers le dossier des logs */
defined('LOG_PATH') || define('LOG_PATH', STORAGE_PATH . 'logs' . DS);

/**
 * Constantes de temps pour cache/sessions (en secondes)
 *
 * Fournissez des moyens simples de travailler avec la myriade de fonctions PHP qui nécessitent des informations en quelques secondes.
 */

/** Une seconde */
defined('SECOND') || define('SECOND', 1);

/**  Une minute */
defined('MINUTE') || define('MINUTE', 60);

/** Une heure */
defined('HOUR') || define('HOUR', 60 * MINUTE);

/** Un jour */
defined('DAY') || define('DAY', 24 * HOUR);

/** Une semaine */
defined('WEEK') || define('WEEK', 7 * DAY);

/** Un mois (30 jours) */
defined('MONTH') || define('MONTH', 30 * DAY);

/** Un trimestre (90 jours) */
defined('QUARTER') || define('QUARTER', 90 * DAY);

/** Un an */
defined('YEAR') || define('YEAR', 365 * DAY);

/** Une décennie */
defined('DECADE') || define('DECADE', 10 * YEAR);

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

/** Succès : pas d'erreurs */
defined('EXIT_SUCCESS') || define('EXIT_SUCCESS', 0);

/** Erreur générique */
defined('EXIT_ERROR') || define('EXIT_ERROR', 1);

/** Erreur de configuration */
defined('EXIT_CONFIG') || define('EXIT_CONFIG', 3);

/** Fichier non trouvé */
defined('EXIT_UNKNOWN_FILE') || define('EXIT_UNKNOWN_FILE', 4);

/** Classe inconnue */
defined('EXIT_UNKNOWN_CLASS') || define('EXIT_UNKNOWN_CLASS', 5);

/** ?éthode inconnue */
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6);

/** Saisie utilisateur invalide */
defined('EXIT_USER_INPUT') || define('EXIT_USER_INPUT', 7);

/** Erreur de base de données */
defined('EXIT_DATABASE') || define('EXIT_DATABASE', 8);

/**  Code d'erreur automatique minimum */
defined('EXIT__AUTO_MIN') || define('EXIT__AUTO_MIN', 9);

/** Code d'erreur automatique maximum */
defined('EXIT__AUTO_MAX') || define('EXIT__AUTO_MAX', 125);
