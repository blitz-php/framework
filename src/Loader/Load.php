<?php
namespace BlitzPHP\Loader;

use BlitzPHP\Exceptions\LoadException;
use InvalidArgumentException;

class Load
{
    /**
     * @var array
     */
    private static $loads = [
        'controllers' => [],
        'helpers'     => [],
        'langs'       => [],
        'libraries'   => [],
        'models'      => []
    ];


    /**
     * Recupere toutes les definitions des services a injecter dans le container
     *
     * @return array
     */
    public static function providers() : array
    {
        $providers = [];

        // services système
        $filename = SYST_PATH . 'Constants' . DS . 'providers.php';
        if (!file_exists($filename)) {
            throw LoadException::providersDefinitionDontExist($filename);
        }
        else if (!in_array($filename, get_included_files())) {
            $providers = array_merge($providers, require $filename);
        }

        // services de l'application
        $filename = CONFIG_PATH . 'providers.php';
        if (file_exists($filename) AND !in_array($filename, get_included_files())) {
            $providers = array_merge($providers, require $filename);
        }

        return $providers;
    }


    /**
     * Verifie si un element est chargé dans la liste des modules
     *
     * @param string $module
     * @param $element
     * @return bool
     */
    private static function is_loaded(string $module, $element) : bool
    {
        if (!isset(self::$loads[$module]) OR !is_array(self::$loads[$module]))
        {
            return false;
        }
        return (in_array($element, self::$loads[$module]));
    }
    /**
     * Ajoute un element aux elements chargés
     *
     * @param string $module
     * @param string $element
     * @param mixed|null $value
     * @return void
     */
    private static function loaded(string $module, $element, $value = null) : void
    {
        self::$loads[$module][$element] = $value;
    }
    /**
     * Renvoie un element chargé
     *
     * @param  string $module
     * @param  string $element
     * @return mixed
     */
    private static function get_loaded(string $module, $element)
    {
        return self::$loads[$module][$element] ?? null;
    }
}
