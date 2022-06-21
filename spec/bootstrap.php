<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Config\Config;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Loader\DotEnv;
use BlitzPHP\Loader\Injector;
use BlitzPHP\Router\RouteCollection;

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Make sure it recognizes that we're testing.
$_SERVER['ENVIRONMENT'] = 'testing';
define('ENVIRONMENT', 'testing');
defined('DEBUG') || define('DEBUG', true);
defined('DS')    || define('DS', DIRECTORY_SEPARATOR);

// Often these constants are pre-defined, but query the current directory structure as a fallback
defined('HOME_PATH') || define('HOME_PATH', realpath(rtrim(getcwd(), '\\/ ')) . DS);

defined('COMPOSER_PATH') || define('COMPOSER_PATH', realpath(HOME_PATH . 'vendor/autoload.php'));
if (is_file(COMPOSER_PATH)) {
    require_once COMPOSER_PATH;
}
defined('VENDOR_PATH') || define('VENDOR_PATH', realpath(HOME_PATH . 'vendor') . DS);

// Define necessary framework path constants
defined('SYST_PATH')    || define('SYST_PATH', realpath(HOME_PATH . 'src') . DS);
defined('TEST_PATH')    || define('TEST_PATH', realpath(HOME_PATH . 'spec') . DS);
defined('APP_PATH')     || define('APP_PATH', TEST_PATH . 'TestApp' . DS);
defined('STORAGE_PATH') || define('STORAGE_PATH', TEST_PATH . 'storage' . DS);
defined('WEBROOT')      || define('WEBROOT', TEST_PATH . 'public' . DS);
defined('SUPPORT_PATH') || define('SUPPORT_PATH', TEST_PATH . '_support' . DS);

// Set environment values that would otherwise stop the framework from functioning during tests.
if (! isset($_SERVER['app.baseURL'])) {
    $_SERVER['app.baseURL'] = 'http://example.com/';
}
define('BASE_URL', $_SERVER['app.baseURL']);

if (file_exists(APP_PATH . 'Config' . DS . 'constants.php')) {
    require_once APP_PATH . 'Config' . DS . 'constants.php';
}
require_once SYST_PATH . 'Constants' . DS . 'constants.php';

if (file_exists(APP_PATH . 'Helpers' . DS . 'common.php')) {
    require_once APP_PATH . 'Helpers' . DS . 'common.php';
}
require_once SYST_PATH . 'Helpers' . DS . 'common.php';

// Load environment settings from .env files into $_SERVER and $_ENV
DotEnv::init(ROOTPATH);

// Always load the URL helper, it should be used in most of apps.
helper('url');

// Initialise les configurations du systeme a partir des fichiers se trouvant dans /app/config
Config::init();
// On initialise le conteneur d'injection de dependences
Injector::init();

if (file_exists(APP_PATH . 'Config' . DS . 'routes.php')) {
    require_once APP_PATH . 'Config' . DS . 'routes.php';
}
if (empty($routes) || ! ($routes instanceof RouteCollectionInterface)) {
    $routes = new RouteCollection();
}

/**
 * @var RouteCollection $routes
 */
$routes->getRoutes('*');
