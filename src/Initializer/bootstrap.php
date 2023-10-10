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
use BlitzPHP\Core\Application;
use BlitzPHP\Loader\DotEnv;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

// Se rassurer que le dossier courant pointe sur le dossier du front controller
if (!defined('TEST_PATH')) {
    // On doit aussi verifier qu'on n'est pas en phase de test, sinon khalan ne trouvera pas le dossier des specs
    chdir(WEBROOT);
}

return function (array $paths, string $paths_config_file, bool $is_cli) {
    // Le chemin d'accès vers le dossier de l'application
    if (is_dir($paths['app'])) {
        if (($_temp = realpath($paths['app'])) !== false) {
            $paths['app'] = $_temp;
        } else {
            $paths['app'] = strtr(rtrim($paths['app'], '/\\'), '/\\', DS . DS);
        }
    } else {
        header('HTTP/1.1 503 Service Unavailable.', true, 503);
        echo 'Your application folder path does not appear to be set correctly. ';
        echo 'Please open the following file and correct this: "' . $paths_config_file . '"';

        exit(3); // EXIT_CONFIG
    }

    // Le chemin d'accès vers le dossier de stockage des fichiers
    if (is_dir($paths['storage'])) {
        if (($_temp = realpath($paths['storage'])) !== false) {
            $paths['storage'] = $_temp;
        } else {
            $paths['storage'] = strtr(rtrim($paths['storage'], '/\\'), '/\\', DS . DS);
        }
    } elseif (is_dir($paths['app'] . $paths['storage'] . DS)) {
        $paths['storage'] = $paths['app'] . strtr(trim($paths['storage'], '/\\'), '/\\', DS . DS);
    } else {
        header('HTTP/1.1 503 Service Unavailable.', true, 503);
        echo 'Your storage folder path does not appear to be set correctly. ';
        echo 'Please open the following file and correct this: "' . $paths_config_file . '"';

        exit(3); // EXIT_CONFIG
    }

    /**
     * Chemin vers le framework
     */
    defined('SYST_PATH') || define('SYST_PATH', dirname(__DIR__) . DS);

    /**
     * Chemin d'acces du dossier "vendor"
     */
    defined('VENDOR_PATH') || define('VENDOR_PATH', dirname(SYST_PATH, 3) . DS);

    /**
     * Chemin vers l'application
     */
    defined('APP_PATH') || define('APP_PATH', realpath($paths['app']) . DS);

    /**
     * Chemin vers le dossier de stockage
     */
    defined('STORAGE_PATH') || define('STORAGE_PATH', realpath($paths['storage']) . DS);

    if (file_exists(APP_PATH . 'Config' . DS . 'constants.php')) {
        require_once APP_PATH . 'Config' . DS . 'constants.php';
    }
    require_once SYST_PATH . 'Constants' . DS . 'constants.php';

    /**
     * On charge le helper `common` qui est utilisé par le framework et presque toutes les applications
     */
    require_once SYST_PATH . 'Helpers' . DS . 'common.php';

    /**
     * On initialise le parsing du fichier .env
     */
    DotEnv::init(ROOTPATH);

    // Initialise et enregistre le loader avec la pile SPL autoloader.
    Services::autoloader()->initialize()->register();
    Services::autoloader()->loadHelpers();

    $app = new Application();
    $app->init();

    /**
     * Initialisation de Kint
     */
    require_once __DIR__ . DS . 'kint.php';

    if (! $is_cli) {
        $app->run();
    }
};
