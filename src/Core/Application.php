<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Core;

use BlitzPHP\Container\Services;
use BlitzPHP\Debug\ExceptionManager;
use BlitzPHP\Event\EventDiscover;
use BlitzPHP\Exceptions\ExceptionInterface;
use BlitzPHP\Router\Dispatcher;
use MirazMac\Requirements\Checker;
use ReflectionException;

class Application
{
    /**
     * Version du framework
     */
    public const VERSION = '1.0.0';

    /**
     * @var string Version de PHP minimale pour l'execution du framework
     */
    private const PHP_MIN_VERSION = '8.1.0';

    /**
     * @var array Liste des extensions requises pour le fonctionnement du framework
     */
    public const REQUIRED_EXTENSIONS = [
        'curl',
        'intl',
        'json',
        'mbstring',
        'reflection',
        'xml',
    ];

    /**
     * @throws ExceptionInterface
     * @throws ReflectionException
     */
    public function init(): self
    {
        /**
         * Verifie les exigences systeme
         */
        self::checkRequirements();

        /**
         * On configure quelques extensions
         */
        self::configureExt();

        /**
         * On initialise le conteneur d'injection de dependences
         */
        service('container')->initialize();

        /**
         * Lance la capture des exceptions et erreurs
         */
        service(ExceptionManager::class)->register();

        /**
         * Initialisation du gestionnaire d'evenement
         */
        Services::singleton(EventDiscover::class)->discove();
        service('event')->emit('app:init');

        return $this;
    }

    public function run(bool $return_response = false)
    {
        return Services::singleton(Dispatcher::class)->run(null, $return_response);
    }

    /**
     * Vérifie si la version PHP est compatible et si toutes les extensions nécessaires sont chargées.
     */
    private static function checkRequirements()
    {
        $checker = (new Checker())
            ->requirePhpVersion('>=' . self::PHP_MIN_VERSION)
            ->requirePhpExtensions(self::REQUIRED_EXTENSIONS)
            ->requireDirectory(SYST_PATH, Checker::CHECK_IS_READABLE)
            ->requireDirectory(APP_PATH, Checker::CHECK_IS_READABLE);

        $checker->check();
        if (! $checker->isSatisfied()) {
            echo '<h3>An error encourred</h3>';

            exit(implode('<br/> ', $checker->getErrors()));
        }
    }

    private static function configureExt()
    {
        $config = (object) config('app');

        // Définir les paramètres régionaux par défaut sur le serveur
        if (function_exists('locale_set_default')) {
            locale_set_default($config->language ?? 'en');
        }

        // Définir le fuseau horaire par défaut sur le serveur
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set($config->timezone ?? 'UTC');
        }

        /*
        * ------------------------------------------------------
        * Éléments importants liés au jeu de caractères
        * ------------------------------------------------------
        *
        * Configurez mbstring et/ou iconv s'ils sont activés
        * et définissez les constantes MB_ENABLED et ICONV_ENABLED, donc
        * que nous ne faisons pas à plusieurs reprises extension_loaded() ou
        * appels function_exists().
        *
        * Remarque : la classe UTF-8 en dépend. Cela se faisait
        * dans son constructeur, mais ce n'est _pas_ spécifique à une classe.
        *
        */
        $charset = strtoupper($config->charset);
        ini_set('default_charset', $charset);

        if (extension_loaded('mbstring')) {
            define('MB_ENABLED', true);
            mb_internal_encoding($charset);
            // Ceci est requis pour que mb_convert_encoding() supprime les caractères invalides.
            // C'est utilisé par CI_Utf8, mais c'est aussi fait pour la cohérence avec iconv.
            mb_substitute_character('none');
        } else {
            define('MB_ENABLED', false);
        }

        // Il y a une constante ICONV_IMPL, mais le manuel PHP dit que l'utilisation
        // Les constantes prédéfinies d'iconv sont "fortement déconseillées".
        if (extension_loaded('iconv')) {
            define('ICONV_ENABLED', true);
        } else {
            define('ICONV_ENABLED', false);
        }

        define('UTF8_ENABLED', defined('PREG_BAD_UTF8_ERROR') && (ICONV_ENABLED === true || MB_ENABLED === true) && $charset === 'UTF-8');
    }
}
