<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

define('BASEPATH', dirname(SYST_PATH) . DS);

define('ROOTPATH', dirname(WEBROOT) . DS);

defined('DS') || define('DS', DIRECTORY_SEPARATOR);


/**
 * Chemin  vers le dossier des configurations
 */
defined('CONFIG_PATH')     || define('CONFIG_PATH', APP_PATH . 'Config' . DS);
/**
 * Chemin  vers le dossier des controllers
 */
defined('CONTROLLER_PATH') || define('CONTROLLER_PATH', APP_PATH . 'Controllers' . DS);


if (! defined('ENTITY_PATH')) {
    /**
     * Entites directory path
     */
    define('ENTITY_PATH', APP_PATH . 'entities' . DS);
}

if (! defined('HELPER_PATH')) {
    /**
     * Helpers directory path
     */
    define('HELPER_PATH', APP_PATH . 'helpers' . DS);
}

if (! defined('LIBRARY_PATH')) {
    /**
     * Libraries directory path
     */
    define('LIBRARY_PATH', APP_PATH . 'libraries' . DS);
}

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

if (! defined('VIEW_PATH')) {
    /**
     * Views directory path
     */
    define('VIEW_PATH', APP_PATH . 'views' . DS);
}

if (! defined('LAYOUT_PATH')) {
    /**
     * Layouts directory path
     */
    define('LAYOUT_PATH', VIEW_PATH . 'layouts' . DS);
}

if (! defined('VIEW_CACHE_PATH')) {
    /**
     * Views cache directory path
     */
    define('VIEW_CACHE_PATH', STORAGE_PATH . 'cache' . DS);
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
