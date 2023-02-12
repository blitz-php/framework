<?php

use BlitzPHP\Core\Application;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

// Se rassurer que le dossier courant pointe sur le dossier du front controller
chdir(WEBROOT);

return function(array $paths, string $paths_config_file, bool $is_cli) 
{  
    // Le chemin d'accès vers le dossier de l'application
    if (is_dir($paths['app'])) {
        if (($_temp = realpath($paths['app'])) !== false) {
            $paths['app'] = $_temp;
        }
        else {
            $paths['app'] = strtr(rtrim($paths['app'], '/\\'), '/\\', DS.DS);
        }
    }
    else {
        header('HTTP/1.1 503 Service Unavailable.', true, 503);
        echo 'Your application folder path does not appear to be set correctly. ';
        echo 'Please open the following file and correct this: "' . $paths_config_file . '"';
        exit(3); // EXIT_CONFIG
    }

    // Le chemin d'accès vers le dossier de stockage des fichiers
    if (is_dir($paths['storage'])) {
        if (($_temp = realpath($paths['storage'])) !== false) {
            $paths['storage'] = $_temp;
        }
        else {
            $paths['storage'] = strtr(rtrim($paths['storage'], '/\\'), '/\\', DS.DS);
        }
    }
    elseif (is_dir($paths['app'].$paths['storage'].DS)) {
        $paths['storage'] = $paths['app'].strtr(trim($paths['storage'], '/\\'), '/\\', DS.DS);
    }
    else {
        header('HTTP/1.1 503 Service Unavailable.', true, 503);
        echo 'Your storage folder path does not appear to be set correctly. ';
        echo 'Please open the following file and correct this: "' . $paths_config_file . '"';
        exit(3); // EXIT_CONFIG
    }


    /**
     * Chemin vers l'application
     */
    define('APP_PATH', realpath($paths['app']) . DS);

    /**
     * Chemin vers le dossier de stockage
     */
    define('STORAGE_PATH', realpath($paths['storage']) . DS);


    if (file_exists(APP_PATH . 'Config' . DS . 'constants.php')) {
        require_once APP_PATH . 'Config' . DS . 'constants.php';
    }
    require_once SYST_PATH . 'Constants' . DS . 'constants.php';


    $app = new Application;
    $app->init();

    /**
     * Initialisation de Kint
     */
    require_once __DIR__ . DS . 'kint.php';
    
    if (! $is_cli) {
        $app->run();
    }
};
