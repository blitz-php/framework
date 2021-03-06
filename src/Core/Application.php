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

use BlitzPHP\Config\Config;
use BlitzPHP\Exceptions\ExceptionInterface;
use BlitzPHP\Loader\DotEnv;
use BlitzPHP\Loader\FileLocator;
use BlitzPHP\Loader\Injector;
use BlitzPHP\Router\Dispatcher;
use Kint\Renderer\Renderer;
use Kint\Renderer\RichRenderer;
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
    private const PHP_MIN_VERSION = '8.0.0';

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
         * On charge le helper global
         */
        FileLocator::helper('common');

        /**
         * On initialise le parsing du fichier .env
         */
        DotEnv::init(ROOTPATH);

        /**
         * On configure quelques extensions
         */
        self::configureExt();

        /**
         * Initialise les configurations du systeme a partir des fichiers se trouvant dans /app/config
         */
        Config::init();

        /**
         * On initialise le conteneur d'injection de dependences
         */
        Injector::init();

        /**
         * Lance la capture des exceptions et erreurs
         */
        // Exception::init();

        /**
         * Demarre la session
         */
        // Session::start();

        /**
         * Autocharge les elements specifi??s par le dev a travers le fichier /app/config/autoload
         */
        // Load::init();

        /**
         * Initalise l'outil de debug Kint
         */
        self::initializeKint();

        return $this;
    }

    public function run(bool $return_response = false)
    {
        return Dispatcher::init($return_response);
    }

    /**
     * V??rifie si la version PHP est compatible et si toutes les extensions n??cessaires sont charg??es.
     */
    private static function checkRequirements()
    {
        $checker = (new Checker())
            // ->requirePhpVersion('>=' . self::PHP_MIN_VERSION)
            ->requirePhpExtensions(self::REQUIRED_EXTENSIONS)
            ->requireDirectory(SYST_PATH, Checker::CHECK_IS_READABLE)
            ->requireDirectory(APP_PATH, Checker::CHECK_IS_READABLE);

        $output = $checker->check();
        if (! $checker->isSatisfied()) {
            echo '<h3>An error encourred</h3>';

            exit(implode('<br/> ', $checker->getErrors()));
        }
    }

    private static function configureExt()
    {
        $config = (object) Config::get('app');

        // D??finir les param??tres r??gionaux par d??faut sur le serveur
        if (function_exists('locale_set_default')) {
            locale_set_default($config->language ?? 'en');
        }

        // D??finir le fuseau horaire par d??faut sur le serveur
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set($config->timezone ?? 'UTC');
        }

        /*
        * ------------------------------------------------------
        * ??l??ments importants li??s au jeu de caract??res
        * ------------------------------------------------------
        *
        * Configurez mbstring et/ou iconv s'ils sont activ??s
        * et d??finissez les constantes MB_ENABLED et ICONV_ENABLED, donc
        * que nous ne faisons pas ?? plusieurs reprises extension_loaded() ou
        * appels function_exists().
        *
        * Remarque : la classe UTF-8 en d??pend. Cela se faisait
        * dans son constructeur, mais ce n'est _pas_ sp??cifique ?? une classe.
        *
        */
        $charset = strtoupper($config->charset);
        ini_set('default_charset', $charset);

        if (extension_loaded('mbstring')) {
            define('MB_ENABLED', true);
            // mbstring.internal_encoding est obsol??te ?? partir de PHP 5.6
            // et son utilisation d??clenche des messages E_DEPRECATED.
            @ini_set('mbstring.internal_encoding', $charset);
            // Ceci est requis pour que mb_convert_encoding() supprime les caract??res invalides.
            // C'est utilis?? par CI_Utf8, mais c'est aussi fait pour la coh??rence avec iconv.
            mb_substitute_character('none');
        } else {
            define('MB_ENABLED', false);
        }

        // Il y a une constante ICONV_IMPL, mais le manuel PHP dit que l'utilisation
        // Les constantes pr??d??finies d'iconv sont "fortement d??conseill??es".
        if (extension_loaded('iconv')) {
            define('ICONV_ENABLED', true);
            // iconv.internal_encoding est obsol??te ?? partir de PHP 5.6
            // et son utilisation d??clenche des messages E_DEPRECATED.
            @ini_set('iconv.internal_encoding', $charset);
        } else {
            define('ICONV_ENABLED', false);
        }

        define('UTF8_ENABLED', defined('PREG_BAD_UTF8_ERROR') && (ICONV_ENABLED === true || MB_ENABLED === true) && $charset === 'UTF-8');
    }

    /**
     * Initializes Kint
     */
    private static function initializeKint()
    {
        RichRenderer::$folder = false;
        RichRenderer::$sort   = Renderer::SORT_FULL;
    }
}
