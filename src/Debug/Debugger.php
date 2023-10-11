<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug;

use Spatie\Ignition\Ignition;
use Whoops\Run;

/**
 * Capture et affiche les erreurs et exceptions via whoops
 *
 * Necessite l'instalation de `flip/whoops` ou `spatie/ignition`
 */
class Debugger
{
    /**
     * Demarre le processus
     *
     * @return void
     */
    public static function init()
    {
        $config = config('exceptions');

        if (class_exists(Ignition::class)) {
            return self::initIgnition($config);
        }
        if (class_exists(Run::class)) {
            return self::initWhoops($config);
        }
    }

    /**
     * Initialisation du debugger a travers filp/Whoops
     */
    private static function initWhoops(array $config)
    {
        $debugger = new Run();

        $debugger = ExceptionManager::registerWhoopsHandler($debugger, $config);
        $debugger = ExceptionManager::registerHttpErrors($debugger, $config);
        $debugger = ExceptionManager::registerAppHandlers($debugger, $config);

        $debugger->register();
    }

    /**
     * Initialisation du debugger a travers spatie/ignition
     *
     * @todo customisation du debugger et log des erreurs
     */
    private static function initIgnition(array $config)
    {
        $debugger = Ignition::make();

        $debugger->applicationPath(ROOTPATH)
            ->shouldDisplayException(! on_prod())
            ->register();
    }
}
