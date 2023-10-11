<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\Services;

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Assurez-vous qu'il reconnaisse que nous testons.
$_SERVER['ENVIRONMENT'] = 'testing';
define('ENVIRONMENT', 'testing');
defined('DEBUG') || define('DEBUG', true);
defined('DS')    || define('DS', DIRECTORY_SEPARATOR);

// Souvent, ces constantes sont prédéfinis, mais interroger la structure actuelle du répertoire comme un repli
defined('HOME_PATH')     || define('HOME_PATH', realpath(rtrim(getcwd(), '\\/ ')) . DS);
defined('VENDOR_PATH')   || define('VENDOR_PATH', realpath(HOME_PATH . 'vendor') . DS);
defined('COMPOSER_PATH') || define('COMPOSER_PATH', realpath(VENDOR_PATH . 'autoload.php'));
if (! is_file(COMPOSER_PATH)) {
    echo 'Votre fichier autoload de Composer ne semble pas être défini correctement. ';
    echo 'Veuillez ouvrir le fichier suivant et pour corriger: "' . __FILE__ . '"';

    exit(3); // EXIT_CONFIG
}
require_once COMPOSER_PATH;

// Définir les constantes nécessaires au framework
defined('SYST_PATH')    || define('SYST_PATH', realpath(HOME_PATH . 'src') . DS);
defined('TEST_PATH')    || define('TEST_PATH', realpath(HOME_PATH . 'spec') . DS);
defined('SUPPORT_PATH') || define('SUPPORT_PATH', TEST_PATH . 'application' . DS);
defined('APP_PATH')     || define('APP_PATH', SUPPORT_PATH . 'app' . DS);
defined('STORAGE_PATH') || define('STORAGE_PATH', SUPPORT_PATH . 'storage' . DS);
defined('WEBROOT')      || define('WEBROOT', SUPPORT_PATH . 'public' . DS);

// Définissez des valeurs d'environnement qui empêcheraient autrement le cadre de fonctionner pendant les tests.
if (! isset($_SERVER['app.baseURL'])) {
    $_SERVER['app.baseURL'] = 'http://example.com/';
}
define('BASE_URL', $_SERVER['app.baseURL']);

/**
 * Chargement du fichier responsable du demarrage du systeme
 */
$bootstrap = require_once SYST_PATH . 'Initializer' . DS . 'bootstrap.php';

/**
 * Lancement de l'application
 *
 * Maintenant que tout est ok, on peut exeecuter l'application
 */
$bootstrap(['app' => APP_PATH, 'storage' => STORAGE_PATH], __FILE__, true);

Services::routes()->loadRoutes();
