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
 * Chemin vers le dossier de stockage des fichiers temporaires
 */
defined('TEMP_PATH') || define('TEMP_PATH', STORAGE_PATH . 'temp' . DS);

/**
 * Chemin vers le dossier de cache des vues
 */
defined('VIEW_CACHE_PATH') || define('VIEW_CACHE_PATH', STORAGE_PATH . 'cache' . DS);

/**
 * Chemin vers le dossier des vues
 */
defined('VIEW_PATH') || define('VIEW_PATH', APP_PATH . 'Views' . DS);

/**
 * Chemin vers le dossier des layouts
 */
defined('LAYOUT_PATH') || define('LAYOUT_PATH', VIEW_PATH . 'layouts' . DS);

if (! defined('MIDDLEWARE_PATH')) {
    /**
     * Middlewares directory path
     */
    define('MIDDLEWARE_PATH', APP_PATH . 'middlewares' . DS);
}

if (! defined('MODEL_PATH')) {
    /**
     * Models directory path
     */
    define('MODEL_PATH', APP_PATH . 'models' . DS);
}

if (! defined('RESOURCE_PATH')) {
    /**
     * Resources directory path
     */
    define('RESOURCE_PATH', APP_PATH . 'resources' . DS);
}

if (! defined('LANG_PATH')) {
    /**
     * File translation directory path
     */
    define('LANG_PATH', RESOURCE_PATH . 'lang' . DS);
}

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

if (! defined('SMARTY_CACHE_PATH')) {
    /**
     * Smarty views cache directory path
     */
    define('SMARTY_CACHE_PATH', STORAGE_PATH . 'smarty' . DS . 'cache' . DS);
}

if (! defined('SMARTY_COMPILES_PATH')) {
    /**
     * Smarty views compile directory path
     */
    define('SMARTY_COMPILES_PATH', STORAGE_PATH . 'smarty' . DS . 'compiles' . DS);
}

if (! defined('SMARTY_CONF_PATH')) {
    /**
     * Smarty views config directory path
     */
    define('SMARTY_CONF_PATH', STORAGE_PATH . 'smarty' . DS . 'conf' . DS);
}

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2592000);
defined('YEAR')   || define('YEAR', 31536000);
defined('DECADE') || define('DECADE', 315360000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code
