<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Initializer\Boot;

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Assurez-vous qu'il reconnaisse que nous testons.
$_SERVER['ENVIRONMENT'] = 'testing';
define('ENVIRONMENT', 'testing');
define('DEBUG', true);

// Souvent, ces constantes sont prédéfinis, mais interroger la structure actuelle du répertoire comme un repli
defined('HOME_PATH')     || define('HOME_PATH', realpath(rtrim(getcwd(), '\\/ ')) . DIRECTORY_SEPARATOR);
defined('VENDOR_PATH')   || define('VENDOR_PATH', realpath(HOME_PATH . 'vendor') . DIRECTORY_SEPARATOR);

if (! is_file($autoload_file = realpath(VENDOR_PATH . 'autoload.php')) ?: '') {
    echo 'Votre fichier autoload de Composer ne semble pas être défini correctement. ';
    echo 'Veuillez ouvrir le fichier suivant et pour corriger: "' . __FILE__ . '"';

    exit(3); // EXIT_CONFIG
}

// Définir les constantes nécessaires au framework
defined('SYST_PATH')        || define('SYST_PATH', realpath(HOME_PATH . 'src') . DIRECTORY_SEPARATOR);
defined('TEST_PATH')        || define('TEST_PATH', realpath(HOME_PATH . 'spec') . DIRECTORY_SEPARATOR);
defined('SUPPORT_PATH')     || define('SUPPORT_PATH', TEST_PATH . 'support' . DIRECTORY_SEPARATOR);
defined('APPLICATION_PATH') || define('APPLICATION_PATH', SUPPORT_PATH . 'application' . DIRECTORY_SEPARATOR);
defined('WEBROOT')          || define('WEBROOT', APPLICATION_PATH . 'public' . DIRECTORY_SEPARATOR);

// Définissez des valeurs d'environnement qui empêcheraient autrement le framework de fonctionner pendant les tests.
if (! isset($_SERVER['app.baseURL'])) {
    $_SERVER['app.baseURL'] = 'http://example.com/';
}
define('BASE_URL', $_SERVER['app.baseURL']);

require_once $autoload_file;
require_once SYST_PATH . 'Initializer' . DIRECTORY_SEPARATOR. 'Boot.php';

$paths = ['app' => APPLICATION_PATH . 'app', 'storage' => APPLICATION_PATH . 'storage', 'test' => TEST_PATH, 'composer' => VENDOR_PATH];
Boot::test($paths, __FILE__);

service('routes')->loadRoutes();
